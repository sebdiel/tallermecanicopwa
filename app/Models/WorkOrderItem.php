<?php

namespace App\Models;

use App\Enums\WorkOrderItemType;
use App\Exceptions\WorkOrderLockedException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Item de una orden de trabajo: repuesto consumido (HU3) o mano de obra (HU4).
 * Se crea y elimina a traves de WorkOrder::addPart(), addLabor() y
 * removeItem(), que mantienen el stock sincronizado.
 */
class WorkOrderItem extends Model
{
    protected $fillable = [
        'work_order_id',
        'spare_part_id',
        'type',
        'description',
        'quantity',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'type' => WorkOrderItemType::class,
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        // El subtotal siempre lo calcula el backend (HU4), nunca el cliente.
        static::saving(function (WorkOrderItem $item): void {
            $item->subtotal = round((float) $item->quantity * (float) $item->unit_price, 2);
        });

        // RN-02: una OT Completada o Entregada no admite cambios en sus items.
        $guard = function (WorkOrderItem $item): void {
            if ($item->workOrder()->first()?->isLocked()) {
                throw WorkOrderLockedException::make();
            }
        };

        static::creating($guard);
        static::updating($guard);
        static::deleting($guard);
    }

    /**
     * @return BelongsTo<WorkOrder, $this>
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * @return BelongsTo<SparePart, $this>
     */
    public function sparePart(): BelongsTo
    {
        return $this->belongsTo(SparePart::class);
    }
}
