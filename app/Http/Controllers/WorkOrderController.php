<?php

namespace App\Http\Controllers;

use App\Enums\WorkOrderStatus;
use App\Http\Requests\StoreWorkOrderRequest;
use App\Http\Requests\UpdateWorkOrderRequest;
use App\Http\Requests\UpdateWorkOrderStatusRequest;
use App\Models\SparePart;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WorkOrderController extends Controller
{
    /**
     * Listado de ordenes de trabajo.
     */
    public function index(): Response
    {
        $workOrders = WorkOrder::query()
            ->with(['vehicle', 'client', 'mechanic'])
            ->latest()
            ->get()
            ->map(fn (WorkOrder $workOrder): array => [
                'id' => $workOrder->id,
                'status' => $workOrder->status->value,
                'status_label' => $workOrder->status->label(),
                'entry_date' => $workOrder->entry_date->toDateTimeString(),
                'vehicle' => [
                    'plate' => $workOrder->vehicle->plate,
                    'brand' => $workOrder->vehicle->brand,
                    'model' => $workOrder->vehicle->model,
                ],
                'client_name' => $workOrder->client->full_name,
                'mechanic_name' => $workOrder->mechanic->name,
            ]);

        return Inertia::render('OrdenesTrabajo/Index', [
            'workOrders' => $workOrders,
        ]);
    }

    /**
     * Formulario para abrir una OT sobre un vehiculo ya registrado (RF-07).
     */
    public function create(): Response
    {
        return Inertia::render('OrdenesTrabajo/Abrir', [
            'vehicles' => Vehicle::query()
                ->with('client:id,full_name')
                ->orderBy('plate')
                ->get(['id', 'plate', 'brand', 'model', 'client_id'])
                ->map(fn (Vehicle $vehicle): array => [
                    'id' => $vehicle->id,
                    'plate' => $vehicle->plate,
                    'label' => "{$vehicle->plate} — {$vehicle->brand} {$vehicle->model} ({$vehicle->client->full_name})",
                ]),
            'mechanics' => User::orderBy('name')->get(['id', 'name'])
                ->map(fn (User $user): array => ['value' => $user->id, 'label' => $user->name]),
        ]);
    }

    /**
     * Abre la OT en estado Pendiente y registra diagnostico y notas de
     * repuestos (RF-07, HU2 criterio 3). Los repuestos del catalogo, con
     * descuento de stock, se agregan luego desde el detalle (HU3).
     */
    public function store(StoreWorkOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);

        $workOrder = WorkOrder::create([
            'client_id' => $vehicle->client_id,
            'vehicle_id' => $vehicle->id,
            'mechanic_id' => $data['mechanic_id'],
            'entry_date' => $data['entry_date'],
            'diagnosis' => $data['diagnosis'],
            'parts_notes' => $data['parts_notes'] ?? null,
            'status' => WorkOrderStatus::Pendiente,
        ]);

        return redirect()
            ->route('ordenes.show', $workOrder)
            ->with('success', 'Orden de trabajo abierta correctamente.');
    }

    /**
     * Detalle de la OT con su historial de auditoria (RF-08) y los
     * repuestos usados (HU3).
     */
    public function show(WorkOrder $workOrder): Response
    {
        $workOrder->load([
            'vehicle',
            'client',
            'mechanic',
            'items' => fn ($query) => $query->oldest('id'),
            'statusChanges' => fn ($query) => $query->with('changedBy:id,name')->oldest(),
        ]);

        return Inertia::render('OrdenesTrabajo/Show', [
            'workOrder' => [
                'id' => $workOrder->id,
                'status' => $workOrder->status->value,
                'status_label' => $workOrder->status->label(),
                'entry_date' => $workOrder->entry_date->toDateTimeString(),
                'delivered_at' => $workOrder->delivered_at?->toDateTimeString(),
                'diagnosis' => $workOrder->diagnosis,
                'parts_notes' => $workOrder->parts_notes,
                'is_locked' => $workOrder->isLocked(),
                'next_status' => $workOrder->status->next()?->value,
                'next_status_label' => $workOrder->status->next()?->label(),
                'vehicle' => [
                    'plate' => $workOrder->vehicle->plate,
                    'brand' => $workOrder->vehicle->brand,
                    'model' => $workOrder->vehicle->model,
                    'year' => $workOrder->vehicle->year,
                ],
                'client' => [
                    'full_name' => $workOrder->client->full_name,
                    'phone' => $workOrder->client->phone,
                ],
                'mechanic' => ['name' => $workOrder->mechanic->name],
                'items' => $workOrder->items->map(fn (WorkOrderItem $item): array => [
                    'id' => $item->id,
                    'type' => $item->type->value,
                    'type_label' => $item->type->label(),
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                ]),
                'status_changes' => $workOrder->statusChanges->map(fn ($change): array => [
                    'id' => $change->id,
                    'from_status_label' => $change->from_status?->label(),
                    'to_status_label' => $change->to_status->label(),
                    'changed_by' => $change->changedBy?->name,
                    'notes' => $change->notes,
                    'changed_at' => $change->created_at->toDateTimeString(),
                ]),
            ],
            'mechanics' => User::orderBy('name')->get(['id', 'name'])
                ->map(fn (User $user): array => ['value' => $user->id, 'label' => $user->name]),
            // Catalogo para registrar repuestos usados (HU3); solo con stock.
            'spareParts' => $workOrder->isLocked() ? [] : SparePart::query()
                ->where('stock', '>', 0)
                ->orderBy('name')
                ->get()
                ->map(fn (SparePart $part): array => [
                    'value' => $part->id,
                    'label' => "{$part->code} — {$part->name} (stock {$part->stock}, Bs. {$part->sale_price})",
                ]),
        ]);
    }

    /**
     * Edita diagnostico / repuestos. RN-02: se rechaza si la OT esta
     * Completada o Entregada (HU2 criterio 4).
     */
    public function update(UpdateWorkOrderRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        if ($workOrder->isLocked()) {
            return back()->with('error', 'La OT esta '.$workOrder->status->label().': no se admiten cambios en diagnostico ni repuestos.');
        }

        $workOrder->update($request->validated());

        return back()->with('success', 'Orden de trabajo actualizada.');
    }

    /**
     * Avanza el estado de la OT en secuencia estricta (RF-08).
     */
    public function updateStatus(UpdateWorkOrderStatusRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        $data = $request->validated();
        $actor = isset($data['changed_by']) ? User::find($data['changed_by']) : $workOrder->mechanic;

        $workOrder->transitionTo(
            WorkOrderStatus::from($data['status']),
            $actor,
            $data['notes'] ?? null,
        );

        return back()->with('success', "Estado actualizado a {$workOrder->status->label()}.");
    }
}
