<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Registro de un repuesto usado en la OT. El control de stock (RN-01) y de
 * inmutabilidad (RN-02) se aplica en WorkOrder::addPart().
 */
class StoreWorkOrderPartRequest extends FormRequest
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
            'spare_part_id' => ['required', 'integer', 'exists:spare_parts,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'spare_part_id' => 'repuesto',
            'quantity' => 'cantidad',
        ];
    }
}
