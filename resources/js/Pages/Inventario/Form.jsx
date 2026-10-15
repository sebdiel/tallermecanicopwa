import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import FormField from '@/Components/FormField';

export default function Form({ part }) {
    const editing = part !== null;
    const { data, setData, post, put, processing, errors } = useForm({
        code: part?.code ?? '',
        name: part?.name ?? '',
        category: part?.category ?? '',
        sale_price: part?.sale_price ?? '',
        cost: part?.cost ?? '',
        location: part?.location ?? '',
        stock: part?.stock ?? 0,
        min_stock: part?.min_stock ?? 0,
    });

    const set = (field) => (value) => setData(field, value);

    const submit = (e) => {
        e.preventDefault();
        editing ? put(`/inventario/${part.id}`) : post('/inventario');
    };

    const title = editing ? `Editar ${part.code}` : 'Nuevo repuesto';

    return (
        <AppLayout title={title}>
            <Head title={title} />

            <div className="mb-4">
                <Link href="/inventario" className="text-sm text-blue-600 hover:underline">
                    ← Volver al inventario
                </Link>
            </div>

            <form
                onSubmit={submit}
                className="space-y-4 rounded-lg border border-gray-200 bg-white p-4 sm:p-6"
            >
                <div className="grid gap-4 sm:grid-cols-2">
                    <FormField label="Código" name="code" value={data.code} onChange={set('code')} error={errors.code} maxLength={30} required />
                    <FormField label="Nombre" name="name" value={data.name} onChange={set('name')} error={errors.name} maxLength={120} required />
                    <FormField label="Categoría" name="category" value={data.category} onChange={set('category')} error={errors.category} maxLength={60} />
                    <FormField label="Ubicación" name="location" value={data.location} onChange={set('location')} error={errors.location} maxLength={60} placeholder="Ej. Estante A1" />
                    <FormField label="Precio de venta (Bs.)" name="sale_price" type="number" inputMode="decimal" value={data.sale_price} onChange={set('sale_price')} error={errors.sale_price} required />
                    <FormField label="Costo de compra (Bs.)" name="cost" type="number" inputMode="decimal" value={data.cost} onChange={set('cost')} error={errors.cost} />
                    <FormField label="Stock actual" name="stock" type="number" inputMode="numeric" value={data.stock} onChange={set('stock')} error={errors.stock} required />
                    <FormField label="Stock mínimo (alerta)" name="min_stock" type="number" inputMode="numeric" value={data.min_stock} onChange={set('min_stock')} error={errors.min_stock} required />
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50 sm:w-auto"
                >
                    {editing ? 'Guardar cambios' : 'Registrar repuesto'}
                </button>
            </form>
        </AppLayout>
    );
}
