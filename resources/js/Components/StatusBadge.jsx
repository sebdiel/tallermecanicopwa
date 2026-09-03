const styles = {
    pendiente: 'bg-gray-100 text-gray-700',
    en_proceso: 'bg-blue-100 text-blue-700',
    completada: 'bg-amber-100 text-amber-800',
    entregada: 'bg-green-100 text-green-700',
};

export default function StatusBadge({ status, label }) {
    return (
        <span
            className={
                'inline-flex rounded-full px-2.5 py-1 text-xs font-medium ' +
                (styles[status] ?? 'bg-gray-100 text-gray-700')
            }
        >
            {label}
        </span>
    );
}
