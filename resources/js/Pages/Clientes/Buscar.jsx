import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

function VehicleCard({ vehicle }) {
    return (
        <li className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-base font-semibold text-gray-900">
                        {vehicle.plate}
                    </p>
                    <p className="text-sm text-gray-600">
                        {vehicle.brand} {vehicle.model} · {vehicle.year} · {vehicle.color}
                    </p>
                </div>
                <span className="shrink-0 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 capitalize">
                    {vehicle.transmission}
                </span>
            </div>

            <dl className="mt-3 grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                <dt className="text-gray-500">Kilometraje</dt>
                <dd className="text-gray-900">{vehicle.mileage.toLocaleString('es-BO')} km</dd>
                <dt className="text-gray-500">Cliente</dt>
                <dd className="text-gray-900">{vehicle.client.full_name}</dd>
                <dt className="text-gray-500">CI / NIT</dt>
                <dd className="text-gray-900">{vehicle.client.ci_nit}</dd>
                <dt className="text-gray-500">Teléfono</dt>
                <dd className="text-gray-900">{vehicle.client.phone}</dd>
                {vehicle.client.address && (
                    <>
                        <dt className="text-gray-500">Dirección</dt>
                        <dd className="text-gray-900">{vehicle.client.address}</dd>
                    </>
                )}
            </dl>
        </li>
    );
}

export default function Buscar({ q, results }) {
    const [term, setTerm] = useState(q ?? '');

    const submit = (e) => {
        e.preventDefault();
        router.get('/clientes/buscar', { q: term }, { preserveState: true, replace: true });
    };

    return (
        <AppLayout title="Buscar cliente o vehículo">
            <Head title="Buscar" />

            <form onSubmit={submit} className="flex flex-col gap-2 sm:flex-row">
                <input
                    type="search"
                    value={term}
                    onChange={(e) => setTerm(e.target.value)}
                    placeholder="Placa o nombre del cliente"
                    className="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                />
                <button
                    type="submit"
                    className="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                >
                    Buscar
                </button>
            </form>

            <div className="mt-6">
                {q === '' ? (
                    <p className="text-sm text-gray-500">
                        Escribe una placa o el nombre de un cliente para buscar.
                    </p>
                ) : results.length === 0 ? (
                    <p className="text-sm text-gray-500">
                        Sin resultados para <span className="font-medium">“{q}”</span>.
                    </p>
                ) : (
                    <>
                        <p className="mb-3 text-xs text-gray-500">
                            {results.length}{' '}
                            {results.length === 1 ? 'resultado' : 'resultados'}
                        </p>
                        <ul className="space-y-3">
                            {results.map((vehicle) => (
                                <VehicleCard key={vehicle.id} vehicle={vehicle} />
                            ))}
                        </ul>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
