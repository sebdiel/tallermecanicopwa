import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import FormField from '@/Components/FormField';

function localNow() {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    return now.toISOString().slice(0, 16);
}

export default function Abrir({ vehicles, mechanics }) {
    const { data, setData, post, processing, errors } = useForm({
        vehicle_id: '',
        mechanic_id: '',
        entry_date: localNow(),
        diagnosis: '',
        parts_notes: '',
    });

    const set = (field) => (value) => setData(field, value);

    const submit = (e) => {
        e.preventDefault();
        post('/ordenes-trabajo');
    };

    return (
        <AppLayout title="Abrir orden de trabajo">
            <Head title="Abrir OT" />

            {vehicles.length === 0 && (
                <p className="mb-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    No hay vehículos registrados. Registra un cliente y su vehículo antes de abrir una OT.
                </p>
            )}

            <form onSubmit={submit} className="space-y-6">
                <fieldset className="rounded-lg border border-gray-200 bg-white p-4 sm:p-6">
                    <legend className="px-2 text-sm font-semibold text-gray-900">Recepción</legend>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <FormField
                            label="Vehículo"
                            name="vehicle_id"
                            value={data.vehicle_id}
                            onChange={set('vehicle_id')}
                            error={errors.vehicle_id}
                            options={vehicles.map((v) => ({ value: v.id, label: v.label }))}
                            required
                        />
                        <FormField
                            label="Mecánico responsable"
                            name="mechanic_id"
                            value={data.mechanic_id}
                            onChange={set('mechanic_id')}
                            error={errors.mechanic_id}
                            options={mechanics}
                            required
                        />
                        <FormField
                            label="Fecha y hora de ingreso"
                            name="entry_date"
                            type="datetime-local"
                            value={data.entry_date}
                            onChange={set('entry_date')}
                            error={errors.entry_date}
                            required
                        />
                    </div>
                </fieldset>

                <fieldset className="rounded-lg border border-gray-200 bg-white p-4 sm:p-6">
                    <legend className="px-2 text-sm font-semibold text-gray-900">
                        Diagnóstico técnico
                    </legend>
                    <div className="grid gap-4">
                        <FormField
                            label="Diagnóstico"
                            name="diagnosis"
                            multiline
                            value={data.diagnosis}
                            onChange={set('diagnosis')}
                            error={errors.diagnosis}
                            maxLength={5000}
                            required
                        />
                        <FormField
                            label="Repuestos usados (texto libre)"
                            name="parts_notes"
                            multiline
                            rows={3}
                            value={data.parts_notes}
                            onChange={set('parts_notes')}
                            error={errors.parts_notes}
                            maxLength={5000}
                            placeholder="El control de stock y costos se agrega en HU3/HU4."
                        />
                    </div>
                </fieldset>

                <div className="flex justify-end">
                    <button
                        type="submit"
                        disabled={processing || vehicles.length === 0}
                        className="w-full rounded-md bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50 sm:w-auto"
                    >
                        {processing ? 'Abriendo…' : 'Abrir OT'}
                    </button>
                </div>
            </form>
        </AppLayout>
    );
}
