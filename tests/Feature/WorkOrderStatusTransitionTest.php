<?php

namespace Tests\Feature;

use App\Enums\WorkOrderStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_full_sequence_is_allowed_step_by_step(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $workOrder->transitionTo(WorkOrderStatus::EnProceso);
        $workOrder->transitionTo(WorkOrderStatus::Completada);
        $workOrder->transitionTo(WorkOrderStatus::Entregada);

        $this->assertSame(WorkOrderStatus::Entregada, $workOrder->fresh()->status);
    }

    public function test_it_rejects_skipping_a_step(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $this->expectException(InvalidStatusTransitionException::class);

        $workOrder->transitionTo(WorkOrderStatus::Entregada);
    }

    public function test_it_rejects_going_backwards(): void
    {
        $workOrder = WorkOrder::factory()->completed()->create();

        $this->expectException(InvalidStatusTransitionException::class);

        $workOrder->transitionTo(WorkOrderStatus::EnProceso);
    }

    public function test_a_rejected_transition_does_not_persist_a_change(): void
    {
        $workOrder = WorkOrder::factory()->create();

        try {
            $workOrder->transitionTo(WorkOrderStatus::Completada);
        } catch (InvalidStatusTransitionException) {
            // esperado
        }

        $this->assertSame(WorkOrderStatus::Pendiente, $workOrder->fresh()->status);
    }

    public function test_delivered_at_is_stamped_only_when_reaching_entregada(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $workOrder->transitionTo(WorkOrderStatus::EnProceso);
        $this->assertNull($workOrder->fresh()->delivered_at);

        $workOrder->transitionTo(WorkOrderStatus::Completada);
        $workOrder->transitionTo(WorkOrderStatus::Entregada);
        $this->assertNotNull($workOrder->fresh()->delivered_at);
    }

    public function test_entregada_has_no_next_state(): void
    {
        $this->assertNull(WorkOrderStatus::Entregada->next());
        $this->assertSame(WorkOrderStatus::EnProceso, WorkOrderStatus::Pendiente->next());
    }
}
