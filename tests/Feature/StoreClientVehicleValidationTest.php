<?php

namespace Tests\Feature;

use App\Http\Requests\StoreClientVehicleRequest;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreClientVehicleValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
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

    private function validate(array $payload): \Illuminate\Validation\Validator
    {
        return Validator::make($payload, (new StoreClientVehicleRequest)->rules());
    }

    public function test_a_complete_payload_passes(): void
    {
        $this->assertTrue($this->validate($this->validPayload())->passes());
    }

    public function test_plate_must_be_unique_against_the_database(): void
    {
        Vehicle::factory()->create(['plate' => 'ABC123']);

        $validator = $this->validate($this->validPayload(['vehicle' => ['plate' => 'ABC123']]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('vehicle.plate', $validator->errors()->toArray());
    }

    public function test_required_client_and_vehicle_fields_are_enforced(): void
    {
        $validator = $this->validate([
            'client' => ['address' => null],
            'vehicle' => [],
        ]);

        $errors = $validator->errors()->toArray();

        $this->assertArrayHasKey('client.ci_nit', $errors);
        $this->assertArrayHasKey('client.full_name', $errors);
        $this->assertArrayHasKey('client.phone', $errors);
        $this->assertArrayHasKey('vehicle.plate', $errors);
        $this->assertArrayHasKey('vehicle.brand', $errors);
        $this->assertArrayHasKey('vehicle.model', $errors);
        $this->assertArrayHasKey('vehicle.year', $errors);
        $this->assertArrayHasKey('vehicle.color', $errors);
        $this->assertArrayHasKey('vehicle.transmission', $errors);
        $this->assertArrayHasKey('vehicle.mileage', $errors);
    }

    public function test_address_is_optional(): void
    {
        $validator = $this->validate($this->validPayload(['client' => ['address' => null]]));

        $this->assertTrue($validator->passes());
    }

    public function test_transmission_only_accepts_known_values(): void
    {
        $validator = $this->validate($this->validPayload(['vehicle' => ['transmission' => 'semiautomatica']]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('vehicle.transmission', $validator->errors()->toArray());
    }

    public function test_year_cannot_be_in_the_far_future(): void
    {
        $validator = $this->validate($this->validPayload(['vehicle' => ['year' => (int) date('Y') + 5]]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('vehicle.year', $validator->errors()->toArray());
    }

    public function test_mileage_cannot_be_negative(): void
    {
        $validator = $this->validate($this->validPayload(['vehicle' => ['mileage' => -1]]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('vehicle.mileage', $validator->errors()->toArray());
    }

    public function test_plate_mutator_normalizes_to_uppercase_without_spaces(): void
    {
        $vehicle = Vehicle::factory()->create(['plate' => '  abc 123 ']);

        $this->assertSame('ABC123', $vehicle->fresh()->plate);
    }
}
