<?php

namespace App\Models;

use Database\Factories\SparePartFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Repuesto o insumo del inventario del taller (HU3, RF-13).
 */
class SparePart extends Model
{
    /** @use HasFactory<SparePartFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'sale_price',
        'cost',
        'location',
        'stock',
        'min_stock',
    ];

    protected function casts(): array
    {
        return [
            'sale_price' => 'decimal:2',
            'cost' => 'decimal:2',
            'stock' => 'integer',
            'min_stock' => 'integer',
        ];
    }

    /**
     * Stock critico: igual o por debajo del minimo (RF-15).
     */
    public function isLowStock(): bool
    {
        return $this->stock <= $this->min_stock;
    }

    /**
     * @param  Builder<SparePart>  $query
     */
    public function scopeLowStock(Builder $query): void
    {
        $query->whereColumn('stock', '<=', 'min_stock');
    }

    /**
     * Consumos de este repuesto en ordenes de trabajo.
     *
     * @return HasMany<WorkOrderItem, $this>
     */
    public function workOrderItems(): HasMany
    {
        return $this->hasMany(WorkOrderItem::class);
    }
}
