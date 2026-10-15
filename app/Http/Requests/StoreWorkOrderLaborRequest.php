<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Registro de mano de obra en la OT (HU4). El subtotal lo calcula el backend.
 */
class StoreWorkOrderLaborRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'hours' => ['required', 'numeric', 'min:0.25', 'max:999'],
            'rate' => ['required', 'numeric', 'min:0', 'max:99999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'description' => 'descripcion del trabajo',
            'hours' => 'horas',
            'rate' => 'tarifa por hora',
        ];
    }
}
