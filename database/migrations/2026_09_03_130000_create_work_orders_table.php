<?php

use App\Enums\WorkOrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Vinculo con cliente y vehiculo registrados en HU1.
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            // Mecanico que abre la OT (FK a users; los roles son RF-01/RF-02).
            $table->foreignId('mechanic_id')->constrained('users')->restrictOnDelete();

            $table->dateTime('entry_date');
            $table->text('diagnosis');
            // Repuestos usados anotados al abrir la OT (texto libre). El modulo
            // estructurado de repuestos con descuento de stock es HU3.
            $table->text('parts_notes')->nullable();

            // pendiente -> en_proceso -> completada -> entregada
            // (App\Enums\WorkOrderStatus, secuencia estricta).
            $table->string('status')->default(WorkOrderStatus::Pendiente->value)->index();
            $table->dateTime('delivered_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
