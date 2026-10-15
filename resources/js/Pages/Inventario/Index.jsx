import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';

export default function Index({ parts, lowStockCount, filters }) {
    const [q, setQ] = useState(filters.q ?? '');

    const search = (e) => {
        e.preventDefault();
        router.get('/inventario', { q, bajo: filters.bajo ? 1 : undefined }, { preserveState: true });
    };

    const toggleLow = () => {
        router.get('/inventario', { q: q || undefined, bajo: filters.bajo ? undefined : 1 });
    };

    return (
        <AppLayout title="Inventario de repuestos">
            <Head title="Inventario" />

            {lowStockCount > 0 && (
                <div className="mb-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <strong>{lowStockCount}</strong> repuesto(s) en stock crítico.{' '}
                    <button type="button" onClick={toggleLow} className="font-semibold underline">
                        {filters.bajo ? 'Ver todos' : 'Ver solo críticos'}
                    </button>
                </div>
            )}

            <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <form onSubmit={search} className="flex flex-1 gap-2">
                    <input
                        type="search"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Código, nombre o categoría"
                        className="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                    />
                    <button
                        type="submit"
                        className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        Buscar
                    </button>
                </form>
                <Link
                    href="/inventario/nuevo"
                    className="rounded-md bg-blue-600 px-4 py-2 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                >
                    Nuevo repuesto
                </Link>
            </div>

            {parts.length === 0 ? (
                <p className="text-sm text-gray-500">No hay repuestos que coincidan.</p>
            ) : (
                <ul className="space-y-3">
                    {parts.map((part) => (
                        <li key={part.id}>
                            <Link
                                href={`/inventario/${part.id}/editar`}
                                className={
                                    'block rounded-lg border bg-white p-4 shadow-sm transition hover:shadow ' +
                                    (part.is_low_stock
                                        ? 'border-amber-300 hover:border-amber-400'
                                        : 'border-gray-200 hover:border-blue-300')
                                }
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="text-base font-semibold text-gray-900">
                                            {part.name}
                                        </p>
                                        <p className="text-sm text-gray-600">
                                            {part.code}
                                            {part.category && ` · ${part.category}`}
                                            {part.location && ` · ${part.location}`}
                                        </p>
                                        <p className="mt-1 text-xs text-gray-400">
                                            Precio de venta: Bs. {part.sale_price}
                                        </p>
                                    </div>
                                    <div className="text-right">
                                        <p
                                            className={
                                                'text-lg font-bold ' +
                                                (part.is_low_stock ? 'text-amber-700' : 'text-gray-900')
                                            }
                                        >
                                            {part.stock}
                                        </p>
                                        <p className="text-xs text-gray-400">mín. {part.min_stock}</p>
                                        {part.is_low_stock && (
                                            <span className="mt-1 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">
                                                Stock crítico
                                            </span>
                                        )}
                                    </div>
                                </div>
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </AppLayout>
    );
}
