<?php

namespace App\Http\Requests;

use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autenticacion/roles (RF-01/RF-02) se agregan en otra parte de la Iteracion 1.
        return true;
    }

    /**
     * Normaliza la entrada antes de validar para que la comprobacion de unicidad
     * de la placa use exactamente el mismo formato que se guardara en la BD.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'client' => [
                ...$this->input('client', []),
                'ci_nit' => trim((string) $this->input('client.ci_nit')),
                'full_name' => trim((string) $this->input('client.full_name')),
            ],
            'vehicle' => [
                ...$this->input('vehicle', []),
                'plate' => strtoupper(preg_replace('/\s+/', '', (string) $this->input('vehicle.plate'))),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $nextYear = (int) date('Y') + 1;

        return [
            'client.ci_nit' => ['required', 'string', 'max:20'],
            'client.full_name' => ['required', 'string', 'max:255'],
            'client.phone' => ['required', 'string', 'max:30'],
            'client.address' => ['nullable', 'string', 'max:255'],

            // Criterio de aceptacion 2: la placa es unica en el sistema. Se rechaza
            // el registro si ya existe un vehiculo con esa placa (validacion en backend).
            'vehicle.plate' => ['required', 'string', 'max:15', Rule::unique('vehicles', 'plate')],
            'vehicle.brand' => ['required', 'string', 'max:100'],
            'vehicle.model' => ['required', 'string', 'max:100'],
            'vehicle.year' => ['required', 'integer', 'min:1900', "max:{$nextYear}"],
            'vehicle.color' => ['required', 'string', 'max:50'],
            'vehicle.transmission' => ['required', Rule::in(Vehicle::TRANSMISSIONS)],
            'vehicle.mileage' => ['required', 'integer', 'min:0', 'max:9999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'client.ci_nit' => 'CI/NIT',
            'client.full_name' => 'nombre completo',
            'client.phone' => 'telefono',
            'client.address' => 'direccion',
            'vehicle.plate' => 'placa',
            'vehicle.brand' => 'marca',
            'vehicle.model' => 'modelo',
            'vehicle.year' => 'anio',
            'vehicle.color' => 'color',
            'vehicle.transmission' => 'tipo de transmision',
            'vehicle.mileage' => 'kilometraje',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'vehicle.plate.unique' => 'Ya existe un vehiculo registrado con la placa :input.',
            'vehicle.transmission.in' => 'El tipo de transmision debe ser manual o automatica.',
        ];
    }
}
