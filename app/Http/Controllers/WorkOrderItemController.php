<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkOrderPartRequest;
use App\Models\SparePart;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Illuminate\Http\RedirectResponse;

/**
 * Repuestos usados en una orden de trabajo (HU3). Las reglas
 * RN-01 y RN-02 viven en el modelo WorkOrder; sus excepciones se muestran
 * como mensaje de error (bootstrap/app.php).
 */
class WorkOrderItemController extends Controller
{
    /**
     * Registra el uso de un repuesto y descuenta el stock (RF-14 / RN-01).
     */
    public function storePart(StoreWorkOrderPartRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        $data = $request->validated();
        $part = SparePart::findOrFail($data['spare_part_id']);

        $workOrder->addPart($part, (int) $data['quantity']);

        $response = back()->with('success', "Se registraron {$data['quantity']} x {$part->name}. Stock restante: {$part->stock}.");

        // RF-15: alerta de stock critico tras el consumo.
        if ($part->isLowStock()) {
            $response->with('warning', "Stock critico de {$part->name}: quedan {$part->stock} (minimo {$part->min_stock}). Reponer.");
        }

        return $response;
    }

    /**
     * Quita un item; si es repuesto, devuelve las unidades al stock.
     */
    public function destroy(WorkOrder $workOrder, WorkOrderItem $item): RedirectResponse
    {
        $workOrder->removeItem($item);

        return back()->with('success', 'Item eliminado de la orden.');
    }
}
