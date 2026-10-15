import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import FormField from '@/Components/FormField';
import StatusBadge from '@/Components/StatusBadge';

function AdvanceStatus({ workOrder, mechanics }) {
    const { data, setData, patch, processing, errors } = useForm({
        status: workOrder.next_status ?? '',
        changed_by: '',
        notes: '',
    });

    if (!workOrder.next_status) {
        return (
            <p className="text-sm text-gray-500">
                La OT fue entregada. No hay más estados por avanzar.
            </p>
        );
    }

    const submit = (e) => {
        e.preventDefault();
        patch(`/ordenes-trabajo/${workOrder.id}/estado`, { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <p className="text-sm text-gray-600">
                Siguiente estado:{' '}
                <span className="font-semibold text-gray-900">
                    {workOrder.next_status_label}
                </span>
            </p>
            <div className="grid gap-4 sm:grid-cols-2">
                <FormField
                    label="Realizado por"
                    name="changed_by"
                    value={data.changed_by}
                    onChange={(v) => setData('changed_by', v)}
                    error={errors.changed_by}
                    options={mechanics}
                />
                <FormField
                    label="Nota (opcional)"
                    name="notes"
                    value={data.notes}
                    onChange={(v) => setData('notes', v)}
                    error={errors.notes}
                    maxLength={500}
                />
            </div>
            {errors.status && (
                <p className="text-xs text-red-600">{errors.status}</p>
            )}
            <button
                type="submit"
                disabled={processing}
                className="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50"
            >
                Avanzar a {workOrder.next_status_label}
            </button>
        </form>
    );
}

function EditDetails({ workOrder }) {
    const { data, setData, patch, processing, errors } = useForm({
        diagnosis: workOrder.diagnosis ?? '',
        parts_notes: workOrder.parts_notes ?? '',
    });

    if (workOrder.is_locked) {
        return (
            <div className="rounded-md border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                La OT está <strong>{workOrder.status_label}</strong>: el diagnóstico y los
                repuestos quedaron inmutables (RN-02).
            </div>
        );
    }

    const submit = (e) => {
        e.preventDefault();
        patch(`/ordenes-trabajo/${workOrder.id}`, { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <FormField
                label="Diagnóstico"
                name="diagnosis"
                multiline
                value={data.diagnosis}
                onChange={(v) => setData('diagnosis', v)}
                error={errors.diagnosis}
                maxLength={5000}
                required
            />
            <FormField
                label="Notas de repuestos (opcional)"
                name="parts_notes"
                multiline
                rows={3}
                value={data.parts_notes}
                onChange={(v) => setData('parts_notes', v)}
                error={errors.parts_notes}
                maxLength={5000}
            />
            <button
                type="submit"
                disabled={processing}
                className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 disabled:opacity-50"
            >
                Guardar cambios
            </button>
        </form>
    );
}

const money = (value) =>
    'Bs. ' +
    Number(value).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

function AddPart({ workOrder, spareParts }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        spare_part_id: '',
        quantity: 1,
    });

    const submit = (e) => {
        e.preventDefault();
        post(`/ordenes-trabajo/${workOrder.id}/repuestos`, {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    if (spareParts.length === 0) {
        return (
            <p className="text-sm text-gray-500">
                No hay repuestos con stock disponible.{' '}
                <Link href="/inventario" className="text-blue-600 hover:underline">
                    Ir al inventario
                </Link>
            </p>
        );
    }

    return (
        <form onSubmit={submit} className="grid gap-3 sm:grid-cols-[1fr_6rem_auto] sm:items-end">
            <FormField
                label="Repuesto"
                name="spare_part_id"
                value={data.spare_part_id}
                onChange={(v) => setData('spare_part_id', v)}
                error={errors.spare_part_id}
                options={spareParts}
                required
            />
            <FormField
                label="Cantidad"
                name="quantity"
                type="number"
                inputMode="numeric"
                value={data.quantity}
                onChange={(v) => setData('quantity', v)}
                error={errors.quantity}
                required
            />
            <button
                type="submit"
                disabled={processing}
                className="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50"
            >
                Agregar
            </button>
        </form>
    );
}

function AddLabor({ workOrder }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        description: '',
        hours: 1,
        rate: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(`/ordenes-trabajo/${workOrder.id}/mano-de-obra`, {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <form onSubmit={submit} className="grid gap-3 sm:grid-cols-[1fr_5rem_7rem_auto] sm:items-end">
            <FormField
                label="Trabajo realizado"
                name="description"
                value={data.description}
                onChange={(v) => setData('description', v)}
                error={errors.description}
                maxLength={255}
                placeholder="Ej. Cambio de pastillas"
                required
            />
            <FormField
                label="Horas"
                name="hours"
                type="number"
                inputMode="decimal"
                value={data.hours}
                onChange={(v) => setData('hours', v)}
                error={errors.hours}
                required
            />
            <FormField
                label="Bs. / hora"
                name="rate"
                type="number"
                inputMode="decimal"
                value={data.rate}
                onChange={(v) => setData('rate', v)}
                error={errors.rate}
                required
            />
            <button
                type="submit"
                disabled={processing}
                className="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50"
            >
                Agregar
            </button>
        </form>
    );
}

function ItemsAndCosts({ workOrder, spareParts }) {
    const remove = (item) => {
        const message =
            item.type === 'part'
                ? `¿Quitar "${item.description}"? Las unidades vuelven al stock.`
                : `¿Quitar "${item.description}"?`;
        if (confirm(message)) {
            router.delete(`/ordenes-trabajo/${workOrder.id}/items/${item.id}`, {
                preserveScroll: true,
            });
        }
    };

    return (
        <div className="space-y-5">
            {workOrder.items.length === 0 ? (
                <p className="text-sm text-gray-500">Todavía no hay repuestos ni mano de obra.</p>
            ) : (
                <ul className="divide-y divide-gray-100">
                    {workOrder.items.map((item) => (
                        <li key={item.id} className="flex items-start justify-between gap-3 py-2 text-sm">
                            <div>
                                <p className="text-gray-900">{item.description}</p>
                                <p className="text-xs text-gray-500">
                                    {item.type_label} · {Number(item.quantity)}{' '}
                                    {item.type === 'labor' ? 'h' : 'u.'} × {money(item.unit_price)}
                                </p>
                            </div>
                            <div className="flex items-center gap-3">
                                <span className="font-medium text-gray-900">{money(item.subtotal)}</span>
                                {!workOrder.is_locked && (
                                    <button
                                        type="button"
                                        onClick={() => remove(item)}
                                        className="text-xs text-red-600 hover:underline"
                                    >
                                        Quitar
                                    </button>
                                )}
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            <dl className="space-y-1 rounded-md bg-gray-50 px-4 py-3 text-sm">
                <div className="flex justify-between">
                    <dt className="text-gray-500">Repuestos</dt>
                    <dd className="text-gray-900">{money(workOrder.totals.parts)}</dd>
                </div>
                <div className="flex justify-between">
                    <dt className="text-gray-500">Mano de obra</dt>
                    <dd className="text-gray-900">{money(workOrder.totals.labor)}</dd>
                </div>
                <div className="flex justify-between border-t border-gray-200 pt-1 text-base font-semibold">
                    <dt className="text-gray-900">Total</dt>
                    <dd className="text-gray-900">{money(workOrder.totals.total)}</dd>
                </div>
            </dl>

            {workOrder.is_locked ? (
                <p className="text-xs text-gray-500">
                    La OT está {workOrder.status_label}: los ítems y precios son inmutables (RN-02).
                </p>
            ) : (
                <>
                    <div>
                        <h3 className="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Agregar repuesto (descuenta stock)
                        </h3>
                        <AddPart workOrder={workOrder} spareParts={spareParts} />
                    </div>
                    <div>
                        <h3 className="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Agregar mano de obra
                        </h3>
                        <AddLabor workOrder={workOrder} />
                    </div>
                </>
            )}
        </div>
    );
}

function Section({ title, children }) {
    return (
        <section className="rounded-lg border border-gray-200 bg-white p-4 sm:p-6">
            <h2 className="mb-3 text-sm font-semibold text-gray-900">{title}</h2>
            {children}
        </section>
    );
}

export default function Show({ workOrder, mechanics, spareParts }) {
    return (
        <AppLayout title={`OT · ${workOrder.vehicle.plate}`}>
            <Head title={`OT ${workOrder.vehicle.plate}`} />

            <div className="mb-4">
                <Link href="/ordenes-trabajo" className="text-sm text-blue-600 hover:underline">
                    ← Volver a órdenes
                </Link>
            </div>

            <div className="space-y-6">
                <Section title="Datos de la OT">
                    <div className="flex items-center justify-between">
                        <StatusBadge status={workOrder.status} label={workOrder.status_label} />
                        <span className="text-xs text-gray-400">
                            Ingreso: {workOrder.entry_date}
                            {workOrder.delivered_at && ` · Entrega: ${workOrder.delivered_at}`}
                        </span>
                    </div>
                    <dl className="mt-3 grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                        <dt className="text-gray-500">Vehículo</dt>
                        <dd className="text-gray-900">
                            {workOrder.vehicle.plate} — {workOrder.vehicle.brand}{' '}
                            {workOrder.vehicle.model} ({workOrder.vehicle.year})
                        </dd>
                        <dt className="text-gray-500">Cliente</dt>
                        <dd className="text-gray-900">
                            {workOrder.client.full_name} · {workOrder.client.phone}
                        </dd>
                        <dt className="text-gray-500">Mecánico</dt>
                        <dd className="text-gray-900">{workOrder.mechanic.name}</dd>
                    </dl>
                </Section>

                <Section title="Diagnóstico">
                    <EditDetails workOrder={workOrder} />
                </Section>

                <Section title="Repuestos, mano de obra y costo total">
                    <ItemsAndCosts workOrder={workOrder} spareParts={spareParts} />
                </Section>

                <Section title="Avanzar estado">
                    <AdvanceStatus workOrder={workOrder} mechanics={mechanics} />
                </Section>

                <Section title="Historial de estados (auditoría)">
                    <ol className="space-y-3">
                        {workOrder.status_changes.map((change) => (
                            <li key={change.id} className="flex gap-3 text-sm">
                                <span className="text-gray-400">{change.changed_at}</span>
                                <span className="text-gray-900">
                                    {change.from_status_label
                                        ? `${change.from_status_label} → ${change.to_status_label}`
                                        : `Apertura (${change.to_status_label})`}
                                    {change.changed_by && (
                                        <span className="text-gray-500"> · {change.changed_by}</span>
                                    )}
                                    {change.notes && (
                                        <span className="block text-xs text-gray-500">
                                            {change.notes}
                                        </span>
                                    )}
                                </span>
                            </li>
                        ))}
                    </ol>
                </Section>
            </div>
        </AppLayout>
    );
}
