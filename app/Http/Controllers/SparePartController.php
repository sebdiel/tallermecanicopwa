<?php

namespace App\Http\Controllers;

use App\Http\Requests\SparePartRequest;
use App\Models\SparePart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SparePartController extends Controller
{
    /**
     * Inventario con alerta visual de stock critico (RF-13, RF-15).
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $onlyLow = $request->boolean('bajo');

        $parts = SparePart::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%")))
            ->when($onlyLow, fn ($query) => $query->lowStock())
            ->orderBy('name')
            ->get()
            ->map(fn (SparePart $part): array => [
                ...$this->present($part),
                'is_low_stock' => $part->isLowStock(),
            ]);

        return Inertia::render('Inventario/Index', [
            'parts' => $parts,
            'lowStockCount' => SparePart::lowStock()->count(),
            'filters' => ['q' => $search, 'bajo' => $onlyLow],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Inventario/Form', ['part' => null]);
    }

    public function store(SparePartRequest $request): RedirectResponse
    {
        $part = SparePart::create($request->validated());

        return redirect()
            ->route('inventario.index')
            ->with('success', "Repuesto {$part->code} registrado.");
    }

    public function edit(SparePart $sparePart): Response
    {
        return Inertia::render('Inventario/Form', ['part' => $this->present($sparePart)]);
    }

    /**
     * Edicion de datos y ajuste manual de stock (reposicion).
     */
    public function update(SparePartRequest $request, SparePart $sparePart): RedirectResponse
    {
        $sparePart->update($request->validated());

        return redirect()
            ->route('inventario.index')
            ->with('success', "Repuesto {$sparePart->code} actualizado.");
    }

    /**
     * @return array<string, mixed>
     */
    private function present(SparePart $part): array
    {
        return [
            'id' => $part->id,
            'code' => $part->code,
            'name' => $part->name,
            'category' => $part->category,
            'sale_price' => $part->sale_price,
            'cost' => $part->cost,
            'location' => $part->location,
            'stock' => $part->stock,
            'min_stock' => $part->min_stock,
        ];
    }
}
