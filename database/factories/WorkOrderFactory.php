<?php

namespace Database\Factories;

use App\Enums\WorkOrderStatus;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkOrder>
 */
class WorkOrderFactory extends Factory
{
    public function definition(): array
    {
        $vehicle = Vehicle::factory()->create();

        return [
            'client_id' => $vehicle->client_id,
            'vehicle_id' => $vehicle->id,
            'mechanic_id' => User::factory(),
            'entry_date' => now(),
            'diagnosis' => fake()->sentence(),
            'parts_notes' => null,
            'status' => WorkOrderStatus::Pendiente,
            'delivered_at' => null,
        ];
    }

    /**
     * Asocia la OT a un vehiculo concreto (y a su cliente).
     */
    public function forVehicle(Vehicle $vehicle): static
    {
        return $this->state(fn (): array => [
            'client_id' => $vehicle->client_id,
            'vehicle_id' => $vehicle->id,
        ]);
    }

    public function inProcess(): static
    {
        return $this->state(fn (): array => ['status' => WorkOrderStatus::EnProceso]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => ['status' => WorkOrderStatus::Completada]);
    }

    public function delivered(): static
    {
        return $this->state(fn (): array => [
            'status' => WorkOrderStatus::Entregada,
            'delivered_at' => now(),
        ]);
    }
}
