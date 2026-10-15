import { Link, usePage } from '@inertiajs/react';

const navItems = [
    { label: 'Registro', href: '/clientes/registro' },
    { label: 'Buscar', href: '/clientes/buscar' },
    { label: 'Órdenes', href: '/ordenes-trabajo' },
    { label: 'Inventario', href: '/inventario' },
];

export default function AppLayout({ title, children }) {
    const { url, props } = usePage();
    const flash = props.flash ?? {};

    return (
        <div className="min-h-screen bg-gray-50">
            <header className="bg-white border-b border-gray-200">
                <div className="mx-auto max-w-3xl px-4">
                    <div className="flex h-14 items-center justify-between">
                        <Link href="/" className="text-base font-semibold text-gray-900">
                            Taller Mecánico
                        </Link>
                        <nav className="-mr-2 flex gap-1 overflow-x-auto pl-2 pr-2">
                            {navItems.map((item) => {
                                const active = url.startsWith(item.href);
                                return (
                                    <Link
                                        key={item.href}
                                        href={item.href}
                                        className={
                                            'shrink-0 rounded-md px-3 py-1.5 text-sm font-medium transition ' +
                                            (active
                                                ? 'bg-blue-600 text-white'
                                                : 'text-gray-600 hover:bg-gray-100')
                                        }
                                    >
                                        {item.label}
                                    </Link>
                                );
                            })}
                        </nav>
                    </div>
                </div>
            </header>

            <main className="mx-auto max-w-3xl px-4 py-6">
                {title && (
                    <h1 className="mb-4 text-xl font-bold text-gray-900 sm:text-2xl">
                        {title}
                    </h1>
                )}

                {flash.success && (
                    <div className="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}
                {flash.warning && (
                    <div className="mb-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        {flash.warning}
                    </div>
                )}
                {flash.error && (
                    <div className="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        {flash.error}
                    </div>
                )}

                {children}
            </main>
        </div>
    );
}
