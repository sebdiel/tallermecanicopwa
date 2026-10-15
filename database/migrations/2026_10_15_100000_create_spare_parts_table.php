<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catalogo de repuestos e insumos del taller (HU3, RF-13).
        Schema::create('spare_parts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 120);
            $table->string('category', 60)->nullable();
            // Precio de venta al cliente; es el que se copia a la OT (HU4).
            $table->decimal('sale_price', 10, 2);
            // Costo de compra (referencial para el margen, OE3).
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('location', 60)->nullable();
            // Se descuenta automaticamente al usarse en una OT (RF-14 / RN-01).
            $table->unsignedInteger('stock')->default(0);
            // Umbral de alerta de stock critico (RF-15).
            $table->unsignedInteger('min_stock')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spare_parts');
    }
};
