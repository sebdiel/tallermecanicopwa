import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Welcome() {
    return (
        <AppLayout title="Gestión operativa del taller">
            <Head title="Inicio" />

            <p className="text-sm text-gray-600">
                Registra clientes y vehículos al momento del ingreso para mantener un
                historial técnico centralizado por vehículo.
            </p>

            <div className="mt-6 grid gap-3 sm:grid-cols-2">
                <Link
                    href="/clientes/registro"
                    className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:border-blue-300 hover:shadow"
                >
                    <span className="block text-sm font-semibold text-gray-900">
                        Registrar cliente y vehículo
                    </span>
                    <span className="mt-1 block text-xs text-gray-500">
                        Alta de CI/NIT, datos de contacto y ficha del vehículo.
                    </span>
                </Link>
                <Link
                    href="/clientes/buscar"
                    className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:border-blue-300 hover:shadow"
                >
                    <span className="block text-sm font-semibold text-gray-900">
                        Buscar
                    </span>
                    <span className="mt-1 block text-xs text-gray-500">
                        Por placa del vehículo o por nombre del cliente.
                    </span>
                </Link>
                <Link
                    href="/ordenes-trabajo"
                    className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:border-blue-300 hover:shadow"
                >
                    <span className="block text-sm font-semibold text-gray-900">
                        Órdenes de trabajo
                    </span>
                    <span className="mt-1 block text-xs text-gray-500">
                        Estado, diagnóstico y repuestos usados.
                    </span>
                </Link>
                <Link
                    href="/inventario"
                    className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:border-blue-300 hover:shadow"
                >
                    <span className="block text-sm font-semibold text-gray-900">
                        Inventario
                    </span>
                    <span className="mt-1 block text-xs text-gray-500">
                        Stock de repuestos con alerta de stock crítico.
                    </span>
                </Link>
            </div>
        </AppLayout>
    );
}
