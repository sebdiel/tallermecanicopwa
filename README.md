# Taller Mecánico PWA

Sistema web progresivo para la gestión operativa de talleres mecánicos.

## Stack

- **Backend:** Laravel 12 · PHP 8.3
- **Frontend:** React 18 + Inertia.js + Vite 7 + Tailwind CSS 4
- **Base de datos:** SQLite en desarrollo · MySQL 8 en producción (ver `.env.example`)
- Modo offline (Service Worker + IndexedDB): pendiente, no aplica a la iteración actual.

## Requisitos

- PHP >= 8.2 con extensiones `pdo`, `mbstring`, `openssl`, `tokenizer`
- Composer 2
- Node.js >= 20 y npm

## Puesta en marcha

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # BD local SQLite
php artisan migrate
npm run build                    # o: npm run dev
php artisan serve
```

### Usar MySQL 8 en lugar de SQLite

En `.env`, comentar `DB_CONNECTION=sqlite` y descomentar el bloque `DB_CONNECTION=mysql`
con los datos de conexión. Luego `php artisan migrate:fresh`.

## Calidad

```bash
vendor/bin/pint --test   # estilo de código (PHP)
php artisan test         # pruebas
npm run build            # compilación del frontend
```

## Estado de las historias de usuario

### HU1 — Registro de cliente y vehículo (Iteración 1 / OE1)

Registrar cliente (CI/NIT, nombre, teléfono, dirección) y su vehículo (placa, marca,
modelo, año, color, transmisión, kilometraje) al momento del ingreso; placa única
validada en backend; búsqueda por placa o por nombre de cliente; relación
1 cliente → N vehículos con `vehicle_id` preparado para las órdenes de trabajo de la
Iteración 2.

Requerimientos: RF-04, RF-05, RF-06, RNF-07 (mobile-first).

### HU2 — Registro y control del estado de la orden de trabajo (Iteración 1 / OE1)

Orden de trabajo (`work_orders`, PK uuid) vinculada al cliente/vehículo de HU1 y a
un mecánico (`users`). Estados en secuencia estricta **Pendiente → En Proceso →
Completada → Entregada** (`App\Enums\WorkOrderStatus`); los saltos y retrocesos se
rechazan en el backend. Cada cambio de estado se audita en
`work_order_status_changes` (fecha/hora, de/a, quién). Al abrir la OT se registra el
diagnóstico técnico y los repuestos usados (texto libre; el control de stock es HU3).
Una vez **Completada** o **Entregada** la OT es inmutable en diagnóstico/repuestos
(RN-02), aplicado en el modelo y en el controlador.

Rutas: `/ordenes-trabajo` (listado), `/ordenes-trabajo/abrir`, `GET|PATCH
/ordenes-trabajo/{ot}`, `PATCH /ordenes-trabajo/{ot}/estado`.

Requerimientos: RF-07, RF-08, RF-09 (parcial: mano de obra/costos es HU4), RNF-07.

Mecánicos de ejemplo para desarrollo: `php artisan db:seed --class=MechanicSeeder`.
