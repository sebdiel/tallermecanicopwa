<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Los roles (mecanico/recepcionista) son RF-01/RF-02, otra parte de la Iteracion 1.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'mechanic_id' => ['required', 'integer', 'exists:users,id'],
            'entry_date' => ['required', 'date', 'before_or_equal:now'],
            'diagnosis' => ['required', 'string', 'max:5000'],
            // Repuestos usados anotados al abrir (texto libre; el stock es HU3).
            'parts_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'vehicle_id' => 'vehiculo',
            'mechanic_id' => 'mecanico',
            'entry_date' => 'fecha de ingreso',
            'diagnosis' => 'diagnostico tecnico',
            'parts_notes' => 'repuestos usados',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'entry_date.before_or_equal' => 'La fecha de ingreso no puede ser futura.',
        ];
    }
}
