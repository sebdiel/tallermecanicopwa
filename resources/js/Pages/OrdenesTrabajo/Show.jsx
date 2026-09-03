import { Head, Link, useForm } from '@inertiajs/react';
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
                label="Repuestos usados"
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

function Section({ title, children }) {
    return (
        <section className="rounded-lg border border-gray-200 bg-white p-4 sm:p-6">
            <h2 className="mb-3 text-sm font-semibold text-gray-900">{title}</h2>
            {children}
        </section>
    );
}

export default function Show({ workOrder, mechanics }) {
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

                <Section title="Diagnóstico y repuestos">
                    <EditDetails workOrder={workOrder} />
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
