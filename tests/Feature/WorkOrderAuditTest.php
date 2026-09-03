<?php

namespace Tests\Feature;

use App\Enums\WorkOrderStatus;
use App\Exceptions\WorkOrderLockedException;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_a_work_order_records_the_initial_audit_row(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $this->assertCount(1, $workOrder->statusChanges);
        $change = $workOrder->statusChanges->first();
        $this->assertNull($change->from_status);
        $this->assertSame(WorkOrderStatus::Pendiente, $change->to_status);
        $this->assertSame($workOrder->mechanic_id, $change->changed_by);
        $this->assertNotNull($change->created_at);
    }

    public function test_each_transition_is_audited_with_time_and_actor(): void
    {
        $actor = User::factory()->create();
        $workOrder = WorkOrder::factory()->create();

        $workOrder->transitionTo(WorkOrderStatus::EnProceso, $actor);
        $workOrder->transitionTo(WorkOrderStatus::Completada, $actor, 'Trabajo terminado');

        $changes = $workOrder->statusChanges()->orderBy('id')->get();
        $this->assertCount(3, $changes);

        $this->assertSame(WorkOrderStatus::Pendiente, $changes[1]->from_status);
        $this->assertSame(WorkOrderStatus::EnProceso, $changes[1]->to_status);
        $this->assertSame($actor->id, $changes[1]->changed_by);

        $this->assertSame('Trabajo terminado', $changes[2]->notes);
        $this->assertNotNull($changes[2]->created_at);
    }

    public function test_a_completed_work_order_rejects_edits_to_diagnosis_or_parts(): void
    {
        $workOrder = WorkOrder::factory()->completed()->create([
            'diagnosis' => 'Original',
            'parts_notes' => 'Filtro de aceite',
        ]);

        try {
            $workOrder->update(['diagnosis' => 'Modificado']);
            $this->fail('Se esperaba WorkOrderLockedException.');
        } catch (WorkOrderLockedException) {
            // esperado
        }

        $this->assertSame('Original', $workOrder->fresh()->diagnosis);
    }

    public function test_a_delivered_work_order_rejects_edits_to_parts(): void
    {
        $workOrder = WorkOrder::factory()->delivered()->create(['parts_notes' => 'Bujias']);

        $this->expectException(WorkOrderLockedException::class);

        $workOrder->update(['parts_notes' => 'Otra cosa']);
    }

    public function test_a_locked_work_order_still_advances_from_completada_to_entregada(): void
    {
        $workOrder = WorkOrder::factory()->completed()->create();

        $workOrder->transitionTo(WorkOrderStatus::Entregada);

        $this->assertSame(WorkOrderStatus::Entregada, $workOrder->fresh()->status);
        $this->assertNotNull($workOrder->fresh()->delivered_at);
    }

    public function test_editing_is_allowed_while_pendiente_or_en_proceso(): void
    {
        $workOrder = WorkOrder::factory()->inProcess()->create();

        $workOrder->update(['diagnosis' => 'Diagnostico ampliado']);

        $this->assertSame('Diagnostico ampliado', $workOrder->fresh()->diagnosis);
    }
}
