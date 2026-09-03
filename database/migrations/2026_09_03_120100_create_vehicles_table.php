<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            // La placa es la clave natural del vehiculo: unica en todo el sistema
            // (criterio de aceptacion 2 de HU1, validada tambien en backend).
            $table->string('plate')->unique();
            $table->string('brand');
            $table->string('model');
            $table->unsignedSmallInteger('year');
            $table->string('color');
            // 'manual' | 'automatica' (ver App\Models\Vehicle::TRANSMISSIONS)
            $table->string('transmission');
            $table->unsignedInteger('mileage')->default(0);
            $table->timestamps();
        });

        // Nota (Iteracion 2 / HU2): el modulo de ordenes de trabajo agregara una
        // tabla `work_orders` con `vehicle_id` como FK hacia esta tabla. El id de
        // `vehicles` ya queda estable para esa relacion, sin necesidad de refactor.
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
