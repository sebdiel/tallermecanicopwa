function normalizeOption(opt) {
    if (opt !== null && typeof opt === 'object') {
        return { value: String(opt.value), label: opt.label };
    }
    return { value: opt, label: opt.charAt(0).toUpperCase() + opt.slice(1) };
}

export default function FormField({
    label,
    name,
    value,
    onChange,
    error,
    type = 'text',
    required = false,
    options = null,
    multiline = false,
    rows = 4,
    inputMode,
    placeholder,
    maxLength,
}) {
    const base =
        'mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 ' +
        (error
            ? 'border-red-400 focus:border-red-500 focus:ring-red-200'
            : 'border-gray-300 focus:border-blue-500 focus:ring-blue-200');

    return (
        <label className="block">
            <span className="text-sm font-medium text-gray-700">
                {label}
                {required && <span className="text-red-500"> *</span>}
            </span>

            {options ? (
                <select
                    name={name}
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    className={base}
                >
                    <option value="">Seleccionar…</option>
                    {options.map(normalizeOption).map((opt) => (
                        <option key={opt.value} value={opt.value}>
                            {opt.label}
                        </option>
                    ))}
                </select>
            ) : multiline ? (
                <textarea
                    name={name}
                    value={value}
                    rows={rows}
                    placeholder={placeholder}
                    maxLength={maxLength}
                    onChange={(e) => onChange(e.target.value)}
                    className={base}
                />
            ) : (
                <input
                    type={type}
                    name={name}
                    value={value}
                    inputMode={inputMode}
                    placeholder={placeholder}
                    maxLength={maxLength}
                    onChange={(e) => onChange(e.target.value)}
                    className={base}
                />
            )}

            {error && <span className="mt-1 block text-xs text-red-600">{error}</span>}
        </label>
    );
}
