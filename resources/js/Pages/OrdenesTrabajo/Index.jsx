import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import StatusBadge from '@/Components/StatusBadge';

export default function Index({ workOrders }) {
    return (
        <AppLayout title="Órdenes de trabajo">
            <Head title="Órdenes de trabajo" />

            <div className="mb-4 flex justify-end">
                <Link
                    href="/ordenes-trabajo/abrir"
                    className="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                >
                    Abrir OT
                </Link>
            </div>

            {workOrders.length === 0 ? (
                <p className="text-sm text-gray-500">Todavía no hay órdenes de trabajo.</p>
            ) : (
                <ul className="space-y-3">
                    {workOrders.map((wo) => (
                        <li key={wo.id}>
                            <Link
                                href={`/ordenes-trabajo/${wo.id}`}
                                className="block rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:border-blue-300 hover:shadow"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="text-base font-semibold text-gray-900">
                                            {wo.vehicle.plate} · {wo.vehicle.brand} {wo.vehicle.model}
                                        </p>
                                        <p className="text-sm text-gray-600">
                                            {wo.client_name} — {wo.mechanic_name}
                                        </p>
                                        <p className="mt-1 text-xs text-gray-400">
                                            Ingreso: {wo.entry_date}
                                        </p>
                                    </div>
                                    <StatusBadge status={wo.status} label={wo.status_label} />
                                </div>
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </AppLayout>
    );
}
