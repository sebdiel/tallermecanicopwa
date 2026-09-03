<?php

namespace App\Http\Requests;

use App\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkOrderStatusRequest extends FormRequest
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
            'status' => ['required', Rule::enum(WorkOrderStatus::class), $this->sequenceRule()],
            'changed_by' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Rechaza en el backend cualquier salto o retroceso de estado (HU2 criterio 1).
     */
    private function sequenceRule(): ValidationRule
    {
        /** @var WorkOrder $workOrder */
        $workOrder = $this->route('workOrder');

        return new class($workOrder) implements ValidationRule
        {
            public function __construct(private readonly WorkOrder $workOrder) {}

            public function validate(string $attribute, mixed $value, \Closure $fail): void
            {
                $target = WorkOrderStatus::tryFrom((string) $value);

                if ($target === null || ! $this->workOrder->status->canTransitionTo($target)) {
                    $fail(sprintf(
                        'La orden esta en "%s"; solo puede avanzar a "%s".',
                        $this->workOrder->status->label(),
                        $this->workOrder->status->next()?->label() ?? 'ningun estado (ya fue entregada)',
                    ));
                }
            }
        };
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'estado',
            'changed_by' => 'mecanico',
            'notes' => 'nota',
        ];
    }
}
