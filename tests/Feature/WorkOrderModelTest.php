<?php

namespace Tests\Feature;

use App\Enums\WorkOrderStatus;
use App\Models\Client;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_work_order_uses_a_uuid_primary_key(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $this->assertIsString($workOrder->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $workOrder->id,
        );
    }

    public function test_it_defaults_to_pendiente_and_casts_the_status_enum(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $this->assertInstanceOf(WorkOrderStatus::class, $workOrder->status);
        $this->assertSame(WorkOrderStatus::Pendiente, $workOrder->status);
    }

    public function test_it_is_linked_to_client_vehicle_and_mechanic(): void
    {
        $vehicle = Vehicle::factory()->create();
        $mechanic = User::factory()->create();

        $workOrder = WorkOrder::factory()
            ->forVehicle($vehicle)
            ->create(['mechanic_id' => $mechanic->id]);

        $this->assertTrue($workOrder->vehicle->is($vehicle));
        $this->assertTrue($workOrder->client->is($vehicle->client));
        $this->assertTrue($workOrder->mechanic->is($mechanic));
    }

    public function test_relations_expose_the_work_order_history(): void
    {
        $vehicle = Vehicle::factory()->create();
        WorkOrder::factory()->forVehicle($vehicle)->count(2)->create();

        $this->assertCount(2, $vehicle->fresh()->workOrders);
        $this->assertCount(2, $vehicle->client->fresh()->workOrders);
    }

    public function test_status_enum_exposes_ordered_values_and_labels(): void
    {
        $this->assertSame(
            ['pendiente', 'en_proceso', 'completada', 'entregada'],
            WorkOrderStatus::values(),
        );
        $this->assertSame('En Proceso', WorkOrderStatus::EnProceso->label());
    }

    public function test_deleting_a_client_cascades_to_its_work_orders(): void
    {
        $client = Client::factory()->create();
        $vehicle = Vehicle::factory()->for($client)->create();
        WorkOrder::factory()->forVehicle($vehicle)->create();

        $client->delete();

        $this->assertDatabaseCount('work_orders', 0);
    }
}
