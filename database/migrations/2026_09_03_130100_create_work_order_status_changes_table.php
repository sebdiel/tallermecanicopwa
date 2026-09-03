<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Auditoria: una fila por cada cambio de estado de una OT, con fecha/hora
        // (created_at) y quien lo hizo (HU2 criterio 2). Filas inmutables.
        Schema::create('work_order_status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('work_order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_status_changes');
    }
};
