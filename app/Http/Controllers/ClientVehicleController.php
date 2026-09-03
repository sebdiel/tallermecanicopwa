<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientVehicleRequest;
use App\Models\Client;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ClientVehicleController extends Controller
{
    /**
     * Formulario de registro de cliente + vehiculo.
     */
    public function create(): Response
    {
        return Inertia::render('Clientes/Registro', [
            'transmissions' => Vehicle::TRANSMISSIONS,
        ]);
    }

    /**
     * Registra el cliente y su vehiculo (RF-04). Si el CI/NIT ya existe se
     * reutiliza el cliente y se le agrega el vehiculo (1 cliente -> N vehiculos).
     */
    public function store(StoreClientVehicleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $vehicle = DB::transaction(function () use ($data): Vehicle {
            $client = Client::updateOrCreate(
                ['ci_nit' => $data['client']['ci_nit']],
                $data['client'],
            );

            return $client->vehicles()->create($data['vehicle']);
        });

        return redirect()
            ->route('clientes.buscar', ['q' => $vehicle->plate])
            ->with('success', "Vehiculo {$vehicle->plate} registrado correctamente.");
    }

    /**
     * Busca clientes/vehiculos por placa o por nombre del cliente (RF-06).
     */
    public function search(Request $request): Response
    {
        $term = trim((string) $request->query('q', ''));

        $vehicles = [];

        if ($term !== '') {
            $vehicles = Vehicle::query()
                ->with('client')
                ->where('plate', 'like', '%'.strtoupper(preg_replace('/\s+/', '', $term)).'%')
                ->orWhereRelation('client', 'full_name', 'like', "%{$term}%")
                ->orderBy('plate')
                ->limit(50)
                ->get()
                ->map(fn (Vehicle $vehicle): array => [
                    'id' => $vehicle->id,
                    'plate' => $vehicle->plate,
                    'brand' => $vehicle->brand,
                    'model' => $vehicle->model,
                    'year' => $vehicle->year,
                    'color' => $vehicle->color,
                    'transmission' => $vehicle->transmission,
                    'mileage' => $vehicle->mileage,
                    'client' => [
                        'id' => $vehicle->client->id,
                        'ci_nit' => $vehicle->client->ci_nit,
                        'full_name' => $vehicle->client->full_name,
                        'phone' => $vehicle->client->phone,
                        'address' => $vehicle->client->address,
                    ],
                ])
                ->all();
        }

        return Inertia::render('Clientes/Buscar', [
            'q' => $term,
            'results' => $vehicles,
        ]);
    }
}
