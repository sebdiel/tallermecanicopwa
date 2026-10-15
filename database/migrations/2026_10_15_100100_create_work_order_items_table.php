<?php

use App\Enums\WorkOrderItemType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Items de la OT: repuestos del catalogo (HU3) y mano de obra (HU4).
        // La suma de los subtotales es el costo total de la orden (RF-09).
        Schema::create('work_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('work_order_id')->constrained()->cascadeOnDelete();
            // Solo los items de tipo repuesto referencian el catalogo.
            $table->foreignId('spare_part_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('type')->default(WorkOrderItemType::Part->value)->index();
            $table->string('description', 255);
            // Unidades (repuesto) u horas (mano de obra).
            $table->decimal('quantity', 8, 2);
            // Precio unitario congelado al momento de registrar el item.
            $table->decimal('unit_price', 10, 2);
            // quantity * unit_price, calculado en el backend.
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_items');
    }
};
