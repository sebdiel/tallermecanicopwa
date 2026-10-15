<?php

namespace Tests\Feature;

use App\Enums\WorkOrderItemType;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\WorkOrderLockedException;
use App\Models\SparePart;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU3 - Descuento automatico de stock al usar repuestos (RF-14, RN-01).
 */
class StockDiscountTest extends TestCase
{
    use RefreshDatabase;

    public function test_using_a_part_discounts_its_stock_and_freezes_the_price(): void
    {
        $workOrder = WorkOrder::factory()->create();
        $part = SparePart::factory()->withStock(10)->create(['sale_price' => 45.50]);

        $item = $workOrder->addPart($part, 3);

        $this->assertSame(7, $part->fresh()->stock);
        $this->assertSame(WorkOrderItemType::Part, $item->type);
        $this->assertSame($part->id, $item->spare_part_id);
        $this->assertSame('45.50', $item->unit_price);

        // Un cambio posterior de precio en el catalogo no altera la OT.
        $part->update(['sale_price' => 60]);
        $this->assertSame('45.50', $item->fresh()->unit_price);
    }

    public function test_it_rejects_using_more_units_than_available_and_persists_nothing(): void
    {
        $workOrder = WorkOrder::factory()->create();
        $part = SparePart::factory()->withStock(2)->create();

        try {
            $workOrder->addPart($part, 3);
            $this->fail('Se esperaba InsufficientStockException.');
        } catch (InsufficientStockException) {
            // esperado
        }

        $this->assertSame(2, $part->fresh()->stock);
        $this->assertDatabaseCount('work_order_items', 0);
    }

    public function test_the_whole_stock_can_be_used_but_never_goes_negative(): void
    {
        $workOrder = WorkOrder::factory()->create();
        $part = SparePart::factory()->withStock(2)->create();

        $workOrder->addPart($part, 2);

        $this->assertSame(0, $part->fresh()->stock);
        $this->expectException(InsufficientStockException::class);
        $workOrder->addPart($part, 1);
    }

    public function test_removing_a_part_returns_the_units_to_stock(): void
    {
        $workOrder = WorkOrder::factory()->create();
        $part = SparePart::factory()->withStock(5)->create();
        $item = $workOrder->addPart($part, 4);

        $workOrder->removeItem($item);

        $this->assertSame(5, $part->fresh()->stock);
        $this->assertDatabaseCount('work_order_items', 0);
    }

    public function test_a_completed_order_rejects_new_parts_without_touching_stock(): void
    {
        $workOrder = WorkOrder::factory()->completed()->create();
        $part = SparePart::factory()->withStock(5)->create();

        try {
            $workOrder->addPart($part, 1);
            $this->fail('Se esperaba WorkOrderLockedException.');
        } catch (WorkOrderLockedException) {
            // esperado (RN-02)
        }

        $this->assertSame(5, $part->fresh()->stock);
    }

    public function test_items_of_a_closed_order_cannot_be_removed(): void
    {
        $workOrder = WorkOrder::factory()->inProcess()->create();
        $part = SparePart::factory()->withStock(5)->create();
        $item = $workOrder->addPart($part, 1);
        $workOrder->update(['status' => 'completada']);

        $this->expectException(WorkOrderLockedException::class);
        $workOrder->fresh()->removeItem($item);
    }

    public function test_the_endpoint_discounts_stock(): void
    {
        $workOrder = WorkOrder::factory()->create();
        $part = SparePart::factory()->withStock(10, 2)->create();

        $this->from(route('ordenes.show', $workOrder))
            ->post(route('ordenes.items.part', $workOrder), ['spare_part_id' => $part->id, 'quantity' => 4])
            ->assertRedirect(route('ordenes.show', $workOrder))
            ->assertSessionHas('success')
            ->assertSessionMissing('warning');

        $this->assertSame(6, $part->fresh()->stock);
    }

    public function test_the_endpoint_warns_when_stock_reaches_the_minimum(): void
    {
        $workOrder = WorkOrder::factory()->create();
        $part = SparePart::factory()->withStock(5, 3)->create();

        $this->from(route('ordenes.show', $workOrder))
            ->post(route('ordenes.items.part', $workOrder), ['spare_part_id' => $part->id, 'quantity' => 2])
            ->assertSessionHas('warning');

        $this->assertTrue($part->fresh()->isLowStock());
    }

    public function test_the_endpoint_shows_an_error_when_stock_is_insufficient(): void
    {
        $workOrder = WorkOrder::factory()->create();
        $part = SparePart::factory()->withStock(1)->create();

        $this->from(route('ordenes.show', $workOrder))
            ->post(route('ordenes.items.part', $workOrder), ['spare_part_id' => $part->id, 'quantity' => 5])
            ->assertRedirect(route('ordenes.show', $workOrder))
            ->assertSessionHas('error');

        $this->assertSame(1, $part->fresh()->stock);
    }

    public function test_the_endpoint_validates_quantity(): void
    {
        $workOrder = WorkOrder::factory()->create();
        $part = SparePart::factory()->create();

        $this->from(route('ordenes.show', $workOrder))
            ->post(route('ordenes.items.part', $workOrder), ['spare_part_id' => $part->id, 'quantity' => 0])
            ->assertSessionHasErrors('quantity');
    }

    public function test_deleting_an_item_through_another_order_is_not_found(): void
    {
        $part = SparePart::factory()->withStock(5)->create();
        $item = WorkOrder::factory()->create()->addPart($part, 1);
        $otherOrder = WorkOrder::factory()->create();

        $this->delete(route('ordenes.items.destroy', [$otherOrder, $item]))->assertNotFound();

        $this->assertSame(4, $part->fresh()->stock);
    }
}
