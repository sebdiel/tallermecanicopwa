<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'plate' => strtoupper(fake()->unique()->bothify('###???')),
            'brand' => fake()->randomElement(['Toyota', 'Nissan', 'Hyundai', 'Kia', 'Suzuki', 'Volkswagen']),
            'model' => fake()->randomElement(['Corolla', 'Sentra', 'Accent', 'Rio', 'Swift', 'Gol']),
            'year' => fake()->numberBetween(2000, (int) date('Y')),
            'color' => fake()->safeColorName(),
            'transmission' => fake()->randomElement(Vehicle::TRANSMISSIONS),
            'mileage' => fake()->numberBetween(0, 250_000),
        ];
    }
}
