<?php

namespace App\Models;

use App\Enums\WorkOrderStatus;
use Database\Factories\WorkOrderFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrder extends Model
{
    /** @use HasFactory<WorkOrderFactory> */
    use HasFactory, HasUuids;

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
}
