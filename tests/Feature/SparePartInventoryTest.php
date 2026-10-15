<?php

namespace Tests\Feature;

use App\Models\SparePart;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * HU3 - Inventario de repuestos y alerta de stock critico (RF-13, RF-15).
 */
class SparePartInventoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_replace([
            'code' => 'fil-ace-01',
            'name' => 'Filtro de aceite',
            'category' => 'Motor',
            'sale_price' => '45.00',
            'cost' => '30.00',
            'location' => 'Estante A1',
            'stock' => 10,
            'min_stock' => 3,
        ], $overrides);
    }

    public function test_it_registers_a_spare_part_with_an_uppercase_code(): void
    {
        $this->post(route('inventario.store'), $this->payload())
            ->assertRedirect(route('inventario.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('spare_parts', ['code' => 'FIL-ACE-01', 'stock' => 10]);
    }

    public function test_the_code_must_be_unique(): void
    {
        SparePart::factory()->create(['code' => 'FIL-ACE-01']);

        $this->from(route('inventario.create'))
            ->post(route('inventario.store'), $this->payload())
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('spare_parts', 1);
    }

    public function test_stock_cannot_be_negative(): void
    {
        $this->from(route('inventario.create'))
            ->post(route('inventario.store'), $this->payload(['stock' => -1]))
            ->assertSessionHasErrors('stock');
    }

    public function test_it_updates_a_part_and_keeps_its_own_code(): void
    {
        $part = SparePart::factory()->create(['code' => 'FIL-ACE-01']);

        $this->put(route('inventario.update', $part), $this->payload(['stock' => 25]))
            ->assertRedirect(route('inventario.index'));

        $this->assertSame(25, $part->fresh()->stock);
    }

    public function test_low_stock_is_flagged_at_or_below_the_minimum(): void
    {
        $this->assertTrue(SparePart::factory()->withStock(3, 3)->make()->isLowStock());
        $this->assertTrue(SparePart::factory()->withStock(0, 0)->make()->isLowStock());
        $this->assertFalse(SparePart::factory()->withStock(4, 3)->make()->isLowStock());
    }

    public function test_index_lists_parts_and_counts_low_stock(): void
    {
        SparePart::factory()->withStock(10, 2)->create();
        SparePart::factory()->withStock(1, 2)->create();

        $this->get(route('inventario.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Inventario/Index')
                ->has('parts', 2)
                ->where('lowStockCount', 1));

        $this->get(route('inventario.index', ['bajo' => 1]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('parts', 1)
                ->where('parts.0.is_low_stock', true));
    }

    public function test_the_order_detail_only_offers_parts_with_stock(): void
    {
        $workOrder = WorkOrder::factory()->create();
        SparePart::factory()->withStock(5)->create();
        SparePart::factory()->withStock(0, 0)->create();

        $this->get(route('ordenes.show', $workOrder))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('spareParts', 1));
    }
}
