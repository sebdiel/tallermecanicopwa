<?php

namespace App\Models;

use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    /**
     * Tipos de transmision admitidos.
     *
     * @var list<string>
     */
    public const TRANSMISSIONS = ['manual', 'automatica'];

    protected $fillable = [
        'client_id',
        'plate',
        'brand',
        'model',
        'year',
        'color',
        'transmission',
        'mileage',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'mileage' => 'integer',
        ];
    }

    /**
     * Cliente propietario del vehiculo.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Historial de ordenes de trabajo del vehiculo (HU2).
     *
     * @return HasMany<WorkOrder, $this>
     */
    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    /**
     * Normaliza la placa a mayusculas y sin espacios al asignarla.
     */
    public function setPlateAttribute(?string $value): void
    {
        $this->attributes['plate'] = $value === null
            ? null
            : strtoupper(preg_replace('/\s+/', '', $value));
    }
}
