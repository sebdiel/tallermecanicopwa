<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Mecanicos de ejemplo para desarrollo. Mientras no exista el modulo de roles
 * (RF-01/RF-02) un mecanico es simplemente un registro en `users`.
 */
class MechanicSeeder extends Seeder
{
    public function run(): void
    {
        $mechanics = [
            ['name' => 'Carlos Quispe', 'email' => 'carlos.quispe@taller.test'],
            ['name' => 'Luis Mamani', 'email' => 'luis.mamani@taller.test'],
            ['name' => 'Andres Vargas', 'email' => 'andres.vargas@taller.test'],
        ];

        foreach ($mechanics as $mechanic) {
            User::firstOrCreate(
                ['email' => $mechanic['email']],
                [...$mechanic, 'password' => Hash::make('password')],
            );
        }
    }
}
