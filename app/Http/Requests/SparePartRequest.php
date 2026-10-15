<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta y edicion de un repuesto del inventario (HU3, RF-13).
 */
class SparePartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required', 'string', 'max:30',
                Rule::unique('spare_parts', 'code')->ignore($this->route('sparePart')),
            ],
            'name' => ['required', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:60'],
            'sale_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'location' => ['nullable', 'string', 'max:60'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'min_stock' => ['required', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code' => 'codigo',
            'name' => 'nombre',
            'category' => 'categoria',
            'sale_price' => 'precio de venta',
            'cost' => 'costo',
            'location' => 'ubicacion',
            'stock' => 'stock',
            'min_stock' => 'stock minimo',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'Ya existe un repuesto con este codigo.',
        ];
    }
}
