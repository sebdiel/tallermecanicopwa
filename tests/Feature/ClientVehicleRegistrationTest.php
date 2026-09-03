<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ClientVehicleRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'client' => [
                'ci_nit' => '1234567',
                'full_name' => 'Juan Perez',
                'phone' => '70000000',
                'address' => 'Av. Siempre Viva 742',
            ],
            'vehicle' => [
                'plate' => 'ABC123',
                'brand' => 'Toyota',
                'model' => 'Corolla',
                'year' => 2018,
                'color' => 'Rojo',
                'transmission' => 'manual',
                'mileage' => 85000,
            ],
        ], $overrides);
    }

    public function test_the_registration_form_is_rendered(): void
    {
        $this->get(route('clientes.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Clientes/Registro', false)
                ->has('transmissions'));
    }

    public function test_it_registers_a_client_and_a_vehicle(): void
    {
        $response = $this->post(route('clientes.store'), $this->payload());

        $response->assertRedirect(route('clientes.buscar', ['q' => 'ABC123']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('clients', [
            'ci_nit' => '1234567',
            'full_name' => 'Juan Perez',
        ]);
        $this->assertDatabaseHas('vehicles', [
            'plate' => 'ABC123',
            'client_id' => Client::first()->id,
            'mileage' => 85000,
        ]);
    }

    public function test_it_rejects_a_duplicate_plate_from_the_backend(): void
    {
        Vehicle::factory()->create(['plate' => 'ABC123']);

        $response = $this->from(route('clientes.create'))
            ->post(route('clientes.store'), $this->payload());

        $response->assertRedirect(route('clientes.create'));
        $response->assertSessionHasErrors('vehicle.plate');

        $this->assertSame(1, Vehicle::where('plate', 'ABC123')->count());
    }

    public function test_a_duplicate_plate_is_rejected_even_with_different_casing_and_spaces(): void
    {
        Vehicle::factory()->create(['plate' => 'ABC123']);

        $response = $this->post(
            route('clientes.store'),
            $this->payload(['vehicle' => ['plate' => ' abc 123 ']]),
        );

        $response->assertSessionHasErrors('vehicle.plate');
        $this->assertSame(1, Vehicle::count());
    }

    public function test_an_existing_client_gets_the_new_vehicle_attached(): void
    {
        $this->post(route('clientes.store'), $this->payload());
        $this->post(route('clientes.store'), $this->payload([
            'vehicle' => ['plate' => 'XYZ789'],
        ]));

        $this->assertSame(1, Client::count());
        $this->assertSame(2, Client::first()->vehicles()->count());
    }

    public function test_a_failed_registration_does_not_persist_anything(): void
    {
        $this->post(route('clientes.store'), $this->payload([
            'vehicle' => ['transmission' => 'invalida'],
        ]));

        $this->assertDatabaseCount('clients', 0);
        $this->assertDatabaseCount('vehicles', 0);
    }

    public function test_search_by_plate_returns_the_vehicle(): void
    {
        Vehicle::factory()->create(['plate' => 'ABC123']);
        Vehicle::factory()->create(['plate' => 'ZZZ999']);

        $this->get(route('clientes.buscar', ['q' => 'abc123']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Clientes/Buscar', false)
                ->where('q', 'abc123')
                ->has('results', 1)
                ->where('results.0.plate', 'ABC123'));
    }

    public function test_search_by_client_name_returns_the_vehicle(): void
    {
        $client = Client::factory()->create(['full_name' => 'Maria Lopez']);
        Vehicle::factory()->for($client)->create(['plate' => 'MLP001']);
        Vehicle::factory()->create(['plate' => 'OTR002']);

        $this->get(route('clientes.buscar', ['q' => 'maria']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('results', 1)
                ->where('results.0.client.full_name', 'Maria Lopez'));
    }

    public function test_search_without_a_term_returns_no_results(): void
    {
        Vehicle::factory()->create();

        $this->get(route('clientes.buscar'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('q', '')
                ->has('results', 0));
    }
}
