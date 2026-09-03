<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Edicion del diagnostico / repuestos de una OT. La regla de inmutabilidad
 * (RN-02) se aplica en el controlador y en el modelo, no aqui.
 */
class UpdateWorkOrderRequest extends FormRequest
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
            'diagnosis' => ['required', 'string', 'max:5000'],
            'parts_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'diagnosis' => 'diagnostico tecnico',
            'parts_notes' => 'repuestos usados',
        ];
    }
}
