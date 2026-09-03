<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ci_nit' => (string) fake()->unique()->numberBetween(1_000_000, 9_999_999),
            'full_name' => fake()->name(),
            'phone' => fake()->numerify('7#######'),
            'address' => fake()->streetAddress(),
        ];
    }
}
