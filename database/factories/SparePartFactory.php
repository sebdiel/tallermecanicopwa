<?php

namespace Database\Factories;

use App\Models\SparePart;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SparePart>
 */
class SparePartFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('REP-####')),
            'name' => fake()->randomElement(['Filtro de aceite', 'Pastillas de freno', 'Bujia', 'Rotula', 'Correa de distribucion', 'Amortiguador']),
            'category' => fake()->randomElement(['Motor', 'Frenos', 'Suspension', 'Electrico']),
            'sale_price' => fake()->randomFloat(2, 20, 800),
            'cost' => fake()->randomFloat(2, 10, 500),
            'location' => 'Estante '.fake()->randomLetter(),
            'stock' => 20,
            'min_stock' => 3,
        ];
    }

    public function withStock(int $stock, int $minStock = 3): static
    {
        return $this->state(fn (): array => ['stock' => $stock, 'min_stock' => $minStock]);
    }
}
