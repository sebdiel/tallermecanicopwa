<?php

namespace App\Models;

use App\Enums\WorkOrderStatus;
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
