import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import FormField from '@/Components/FormField';

export default function Registro({ transmissions }) {
    const { data, setData, post, processing, errors } = useForm({
        client: { ci_nit: '', full_name: '', phone: '', address: '' },
        vehicle: {
            plate: '',
            brand: '',
            model: '',
            year: '',
            color: '',
            transmission: '',
            mileage: '',
        },
    });

    const setClient = (field) => (value) =>
        setData('client', { ...data.client, [field]: value });
    const setVehicle = (field) => (value) =>
        setData('vehicle', { ...data.vehicle, [field]: value });

    const submit = (e) => {
        e.preventDefault();
        post('/clientes');
    };

    return (
        <AppLayout title="Registrar cliente y vehículo">
            <Head title="Registro" />

            <form onSubmit={submit} className="space-y-8">
                <fieldset className="rounded-lg border border-gray-200 bg-white p-4 sm:p-6">
                    <legend className="px-2 text-sm font-semibold text-gray-900">
                        Cliente
                    </legend>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <FormField
                            label="CI / NIT"
                            name="ci_nit"
                            value={data.client.ci_nit}
                            onChange={setClient('ci_nit')}
                            error={errors['client.ci_nit']}
                            inputMode="numeric"
                            maxLength={20}
                            required
                        />
                        <FormField
                            label="Nombre completo"
                            name="full_name"
                            value={data.client.full_name}
                            onChange={setClient('full_name')}
                            error={errors['client.full_name']}
                            required
                        />
                        <FormField
                            label="Teléfono"
                            name="phone"
                            value={data.client.phone}
                            onChange={setClient('phone')}
                            error={errors['client.phone']}
                            inputMode="tel"
                            maxLength={30}
                            required
                        />
                        <FormField
                            label="Dirección"
                            name="address"
                            value={data.client.address}
                            onChange={setClient('address')}
                            error={errors['client.address']}
                            maxLength={255}
                        />
                    </div>
                </fieldset>

                <fieldset className="rounded-lg border border-gray-200 bg-white p-4 sm:p-6">
                    <legend className="px-2 text-sm font-semibold text-gray-900">
                        Vehículo
                    </legend>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <FormField
                            label="Placa"
                            name="plate"
                            value={data.vehicle.plate}
                            onChange={setVehicle('plate')}
                            error={errors['vehicle.plate']}
                            maxLength={15}
                            placeholder="Ej. ABC123"
                            required
                        />
                        <FormField
                            label="Marca"
                            name="brand"
                            value={data.vehicle.brand}
                            onChange={setVehicle('brand')}
                            error={errors['vehicle.brand']}
                            maxLength={100}
                            required
                        />
                        <FormField
                            label="Modelo"
                            name="model"
                            value={data.vehicle.model}
                            onChange={setVehicle('model')}
                            error={errors['vehicle.model']}
                            maxLength={100}
                            required
                        />
                        <FormField
                            label="Año"
                            name="year"
                            type="number"
                            value={data.vehicle.year}
                            onChange={setVehicle('year')}
                            error={errors['vehicle.year']}
                            inputMode="numeric"
                            required
                        />
                        <FormField
                            label="Color"
                            name="color"
                            value={data.vehicle.color}
                            onChange={setVehicle('color')}
                            error={errors['vehicle.color']}
                            maxLength={50}
                            required
                        />
                        <FormField
                            label="Tipo de transmisión"
                            name="transmission"
                            value={data.vehicle.transmission}
                            onChange={setVehicle('transmission')}
                            error={errors['vehicle.transmission']}
                            options={transmissions}
                            required
                        />
                        <FormField
                            label="Kilometraje"
                            name="mileage"
                            type="number"
                            value={data.vehicle.mileage}
                            onChange={setVehicle('mileage')}
                            error={errors['vehicle.mileage']}
                            inputMode="numeric"
                            required
                        />
                    </div>
                </fieldset>

                <div className="flex justify-end">
                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full rounded-md bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50 sm:w-auto"
                    >
                        {processing ? 'Guardando…' : 'Registrar'}
                    </button>
                </div>
            </form>
        </AppLayout>
    );
}
