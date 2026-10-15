<?php

namespace Tests\Feature;

use App\Enums\WorkOrderItemType;
use App\Exceptions\WorkOrderLockedException;
use App\Models\SparePart;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * HU4 - Calculo automatico del costo total de la orden (RF-09).
 */
class WorkOrderCostTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_subtotal_is_computed_by_the_backend(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $item = $workOrder->addLabor('Cambio de pastillas', 1.5, 80);

        $this->assertSame(WorkOrderItemType::Labor, $item->type);
        $this->assertSame('120.00', $item->fresh()->subtotal);
    }

    public function test_a_tampered_subtotal_is_recomputed(): void
    {
        $workOrder = WorkOrder::factory()->create();
        $item = $workOrder->addLabor('Diagnostico', 1, 50);

        $item->forceFill(['subtotal' => 1])->save();

        $this->assertSame('50.00', $item->fresh()->subtotal);
    }

    public function test_the_total_adds_parts_and_labor(): void
    {
        $workOrder = WorkOrder::factory()->create();
        $part = SparePart::factory()->withStock(10)->create(['sale_price' => 45.10]);

        $workOrder->addPart($part, 3);                  // 135.30
        $workOrder->addLabor('Mano de obra', 2, 70.35); // 140.70

        $workOrder->load('items');

        $this->assertSame(135.3, $workOrder->partsTotal());
        $this->assertSame(140.7, $workOrder->laborTotal());
        $this->assertSame(276.0, $workOrder->total());
    }

    public function test_an_order_without_items_costs_zero(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $this->assertSame(0.0, $workOrder->total());
    }

    public function test_a_completed_order_rejects_labor(): void
    {
        $workOrder = WorkOrder::factory()->completed()->create();

        $this->expectException(WorkOrderLockedException::class);
        $workOrder->addLabor('Extra', 1, 10);
    }

    public function test_items_of_a_completed_order_cannot_be_repriced(): void
    {
        $workOrder = WorkOrder::factory()->inProcess()->create();
        $item = $workOrder->addLabor('Alineado', 1, 60);
        $workOrder->update(['status' => 'completada']);

        $this->expectException(WorkOrderLockedException::class);
        WorkOrderItem::find($item->id)->update(['unit_price' => 1]);
    }

    public function test_the_labor_endpoint_registers_labor(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $this->from(route('ordenes.show', $workOrder))
            ->post(route('ordenes.items.labor', $workOrder), [
                'description' => 'Cambio de aceite',
                'hours' => '0.5',
                'rate' => '60',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('work_order_items', [
            'work_order_id' => $workOrder->id,
            'type' => 'labor',
            'subtotal' => 30,
        ]);
    }

    public function test_the_labor_endpoint_validates_input(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $this->from(route('ordenes.show', $workOrder))
            ->post(route('ordenes.items.labor', $workOrder), ['description' => '', 'hours' => 0, 'rate' => -5])
            ->assertSessionHasErrors(['description', 'hours', 'rate']);
    }

    public function test_the_labor_endpoint_rejects_a_delivered_order(): void
    {
        $workOrder = WorkOrder::factory()->delivered()->create();

        $this->from(route('ordenes.show', $workOrder))
            ->post(route('ordenes.items.labor', $workOrder), ['description' => 'x', 'hours' => 1, 'rate' => 10])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('work_order_items', 0);
    }

    public function test_show_returns_the_cost_breakdown(): void
    {
        $workOrder = WorkOrder::factory()->create();
        $workOrder->addPart(SparePart::factory()->withStock(5)->create(['sale_price' => 100]), 2);
        $workOrder->addLabor('Mano de obra', 1, 50);

        $this->get(route('ordenes.show', $workOrder))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('workOrder.items', 2)
                ->where('workOrder.totals.parts', 200)
                ->where('workOrder.totals.labor', 50)
                ->where('workOrder.totals.total', 250));
    }

    public function test_index_shows_the_total_of_each_order(): void
    {
        $workOrder = WorkOrder::factory()->create();
        $workOrder->addLabor('Mano de obra', 2, 40);

        $this->get(route('ordenes.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('workOrders.0.total', 80));
    }
}
