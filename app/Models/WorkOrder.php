<?php

namespace App\Models;

use App\Enums\WorkOrderItemType;
use App\Enums\WorkOrderStatus;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidStatusTransitionException;
use App\Exceptions\WorkOrderLockedException;
use Database\Factories\WorkOrderFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class WorkOrder extends Model
{
    /** @use HasFactory<WorkOrderFactory> */
    use HasFactory, HasUuids;

    /**
     * Campos que quedan congelados cuando la OT esta Completada o Entregada
     * (RN-02). El estado y delivered_at si pueden seguir avanzando.
     *
     * @var list<string>
     */
    private const IMMUTABLE_WHEN_LOCKED = [
        'client_id',
        'vehicle_id',
        'mechanic_id',
        'entry_date',
        'diagnosis',
        'parts_notes',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'client_id',
        'vehicle_id',
        'mechanic_id',
        'entry_date',
        'diagnosis',
        'parts_notes',
        'status',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'datetime',
            'delivered_at' => 'datetime',
            'status' => WorkOrderStatus::class,
        ];
    }

    protected static function booted(): void
    {
        // Registro de auditoria de la apertura de la OT (from = null).
        static::created(function (WorkOrder $workOrder): void {
            $workOrder->statusChanges()->create([
                'from_status' => null,
                'to_status' => $workOrder->status,
                'changed_by' => $workOrder->mechanic_id,
            ]);
        });

        // RN-02: una OT Completada o Entregada no admite cambios en
        // diagnostico/repuestos ni en sus vinculos.
        static::updating(function (WorkOrder $workOrder): void {
            $original = $workOrder->getOriginal('status');
            $originalStatus = $original instanceof WorkOrderStatus
                ? $original
                : WorkOrderStatus::from($original);

            if ($originalStatus->isLocked()
                && array_intersect(self::IMMUTABLE_WHEN_LOCKED, array_keys($workOrder->getDirty()))) {
                throw WorkOrderLockedException::make();
            }
        });
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Mecanico responsable de la OT.
     *
     * @return BelongsTo<User, $this>
     */
    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }

    /**
     * Historial de auditoria de cambios de estado (HU2 criterio 2).
     *
     * @return HasMany<WorkOrderStatusChange, $this>
     */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(WorkOrderStatusChange::class);
    }

    /**
     * Repuestos y mano de obra de la OT (HU3 / HU4).
     *
     * @return HasMany<WorkOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(WorkOrderItem::class);
    }

    /**
     * Registra el uso de un repuesto del catalogo y descuenta su stock en la
     * misma transaccion (RF-14 / RN-01). El precio de venta se congela en el
     * item. Rechaza la operacion si no hay stock suficiente o si la OT esta
     * cerrada (RN-02).
     *
     * @throws InsufficientStockException
     * @throws WorkOrderLockedException
     */
    public function addPart(SparePart $part, int $quantity): WorkOrderItem
    {
        $this->ensureEditable();

        return DB::transaction(function () use ($part, $quantity): WorkOrderItem {
            // Descuento atomico: la condicion evita que dos consumos
            // simultaneos dejen el stock en negativo.
            $discounted = SparePart::whereKey($part->id)
                ->where('stock', '>=', $quantity)
                ->decrement('stock', $quantity);

            $part->refresh();

            if ($discounted === 0) {
                throw InsufficientStockException::for($part, $quantity);
            }

            return $this->items()->create([
                'spare_part_id' => $part->id,
                'type' => WorkOrderItemType::Part,
                'description' => "{$part->code} — {$part->name}",
                'quantity' => $quantity,
                'unit_price' => $part->sale_price,
            ]);
        });
    }

    /**
     * Registra mano de obra aplicada (horas x tarifa) en la OT (HU4).
     *
     * @throws WorkOrderLockedException
     */
    public function addLabor(string $description, float $hours, float $rate): WorkOrderItem
    {
        $this->ensureEditable();

        return $this->items()->create([
            'type' => WorkOrderItemType::Labor,
            'description' => $description,
            'quantity' => $hours,
            'unit_price' => $rate,
        ]);
    }

    /**
     * Quita un item de la OT; si es un repuesto, devuelve las unidades al
     * stock (correccion de un registro erroneo).
     *
     * @throws WorkOrderLockedException
     */
    public function removeItem(WorkOrderItem $item): void
    {
        $this->ensureEditable();

        DB::transaction(function () use ($item): void {
            if ($item->type === WorkOrderItemType::Part && $item->spare_part_id) {
                SparePart::whereKey($item->spare_part_id)->increment('stock', (int) $item->quantity);
            }

            $item->delete();
        });
    }

    /**
     * Total en repuestos (HU4).
     */
    public function partsTotal(): float
    {
        return $this->sumItems(WorkOrderItemType::Part);
    }

    /**
     * Total en mano de obra (HU4).
     */
    public function laborTotal(): float
    {
        return $this->sumItems(WorkOrderItemType::Labor);
    }

    /**
     * Costo total de la OT: repuestos + mano de obra (HU4, RF-09).
     */
    public function total(): float
    {
        return $this->sumItems();
    }

    /**
     * Suma subtotales en centavos para no acumular errores de punto flotante.
     */
    private function sumItems(?WorkOrderItemType $type = null): float
    {
        $cents = $this->items
            ->when($type, fn ($items) => $items->where('type', $type))
            ->sum(fn (WorkOrderItem $item): int => (int) round((float) $item->subtotal * 100));

        return $cents / 100;
    }

    /**
     * @throws WorkOrderLockedException
     */
    private function ensureEditable(): void
    {
        if ($this->isLocked()) {
            throw WorkOrderLockedException::make();
        }
    }

    /**
     * Indica si la OT ya no admite cambios en precios/repuestos (RN-02).
     */
    public function isLocked(): bool
    {
        return $this->status->isLocked();
    }

    /**
     * Avanza la OT al siguiente estado de la secuencia estricta
     * (Pendiente -> En Proceso -> Completada -> Entregada). Rechaza saltos y
     * retrocesos, sella delivered_at al entregar y deja registro de auditoria.
     *
     * @throws InvalidStatusTransitionException
     */
    public function transitionTo(WorkOrderStatus $target, ?User $actor = null, ?string $notes = null): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw InvalidStatusTransitionException::between($this->status, $target);
        }

        $from = $this->status;

        DB::transaction(function () use ($target, $from, $actor, $notes): void {
            $this->status = $target;

            if ($target === WorkOrderStatus::Entregada) {
                $this->delivered_at = now();
            }

            $this->save();

            $this->statusChanges()->create([
                'from_status' => $from,
                'to_status' => $target,
                'changed_by' => $actor?->id,
                'notes' => $notes,
            ]);
        });
    }
}
