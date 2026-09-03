<?php

namespace Tests\Feature;

use App\Enums\WorkOrderStatus;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class WorkOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        $vehicle = Vehicle::factory()->create();
        $mechanic = User::factory()->create();

        return array_replace([
            'vehicle_id' => $vehicle->id,
            'mechanic_id' => $mechanic->id,
            'entry_date' => now()->subHour()->format('Y-m-d\TH:i'),
            'diagnosis' => 'Ruido en el tren delantero.',
            'parts_notes' => 'Rótulas, bujes de parrilla.',
        ], $overrides);
    }

    public function test_the_open_form_lists_vehicles_and_mechanics(): void
    {
        Vehicle::factory()->create();
        User::factory()->create();

        $this->get(route('ordenes.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('OrdenesTrabajo/Abrir')
                ->has('vehicles', 1)
                ->has('mechanics'));
    }

    public function test_it_opens_a_work_order_and_audits_the_opening(): void
    {
        $payload = $this->payload();

        $response = $this->post(route('ordenes.store'), $payload);

        $workOrder = WorkOrder::firstOrFail();
        $response->assertRedirect(route('ordenes.show', $workOrder));
        $response->assertSessionHas('success');

        $this->assertSame(WorkOrderStatus::Pendiente, $workOrder->status);
        $this->assertSame(Vehicle::find($payload['vehicle_id'])->client_id, $workOrder->client_id);
        $this->assertDatabaseHas('work_order_status_changes', [
            'work_order_id' => $workOrder->id,
            'from_status' => null,
            'to_status' => 'pendiente',
        ]);
    }

    public function test_opening_requires_a_diagnosis_and_a_non_future_entry_date(): void
    {
        $this->from(route('ordenes.create'))
            ->post(route('ordenes.store'), $this->payload(['diagnosis' => '']))
            ->assertSessionHasErrors('diagnosis');

        $this->from(route('ordenes.create'))
            ->post(route('ordenes.store'), $this->payload(['entry_date' => now()->addDay()->format('Y-m-d\TH:i')]))
            ->assertSessionHasErrors('entry_date');

        $this->assertDatabaseCount('work_orders', 0);
    }

    public function test_show_returns_the_work_order_with_its_audit_history(): void
    {
        $workOrder = WorkOrder::factory()->create();
        $workOrder->transitionTo(WorkOrderStatus::EnProceso);

        $this->get(route('ordenes.show', $workOrder))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('OrdenesTrabajo/Show')
                ->where('workOrder.status', 'en_proceso')
                ->where('workOrder.next_status', 'completada')
                ->has('workOrder.status_changes', 2));
    }

    public function test_it_advances_the_status_one_step_and_audits_it(): void
    {
        $workOrder = WorkOrder::factory()->create();
        $mechanic = User::factory()->create();

        $response = $this->from(route('ordenes.show', $workOrder))->patch(
            route('ordenes.status', $workOrder),
            ['status' => 'en_proceso', 'changed_by' => $mechanic->id, 'notes' => 'Ingresa a taller'],
        );

        $response->assertSessionHas('success');
        $this->assertSame(WorkOrderStatus::EnProceso, $workOrder->fresh()->status);
        $this->assertDatabaseHas('work_order_status_changes', [
            'work_order_id' => $workOrder->id,
            'to_status' => 'en_proceso',
            'changed_by' => $mechanic->id,
            'notes' => 'Ingresa a taller',
        ]);
    }

    public function test_it_rejects_an_out_of_sequence_status_change(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $this->from(route('ordenes.show', $workOrder))
            ->patch(route('ordenes.status', $workOrder), ['status' => 'entregada'])
            ->assertSessionHasErrors('status');

        $this->assertSame(WorkOrderStatus::Pendiente, $workOrder->fresh()->status);
        $this->assertDatabaseCount('work_order_status_changes', 1);
    }

    public function test_details_can_be_edited_while_the_order_is_open(): void
    {
        $workOrder = WorkOrder::factory()->inProcess()->create();

        $this->from(route('ordenes.show', $workOrder))
            ->patch(route('ordenes.update', $workOrder), [
                'diagnosis' => 'Diagnóstico corregido',
                'parts_notes' => 'Otra lista',
            ])
            ->assertSessionHas('success');

        $this->assertSame('Diagnóstico corregido', $workOrder->fresh()->diagnosis);
    }

    public function test_a_completed_or_delivered_order_rejects_detail_edits(): void
    {
        $workOrder = WorkOrder::factory()->completed()->create(['diagnosis' => 'Original']);

        $this->from(route('ordenes.show', $workOrder))
            ->patch(route('ordenes.update', $workOrder), [
                'diagnosis' => 'Intento de cambio',
                'parts_notes' => 'x',
            ])
            ->assertSessionHas('error');

        $this->assertSame('Original', $workOrder->fresh()->diagnosis);
    }

    public function test_index_lists_work_orders(): void
    {
        WorkOrder::factory()->count(2)->create();

        $this->get(route('ordenes.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('OrdenesTrabajo/Index')
                ->has('workOrders', 2));
    }
}
