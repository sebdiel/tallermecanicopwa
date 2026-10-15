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
1 cliente → N vehículos con `vehicle_id` usado por las órdenes de trabajo de
HU2.

Requerimientos: RF-04, RF-05, RF-06, RNF-07 (mobile-first).

### HU2 — Registro y control del estado de la orden de trabajo (Iteración 1 / OE1)

Orden de trabajo (`work_orders`, PK uuid) vinculada al cliente/vehículo de HU1 y a
un mecánico (`users`). Estados en secuencia estricta **Pendiente → En Proceso →
Completada → Entregada** (`App\Enums\WorkOrderStatus`); los saltos y retrocesos se
rechazan en el backend. Cada cambio de estado se audita en
`work_order_status_changes` (fecha/hora, de/a, quién). Al abrir la OT se registra el
diagnóstico técnico y notas de repuestos (los repuestos del catálogo son HU3).
Una vez **Completada** o **Entregada** la OT es inmutable en diagnóstico, repuestos y precios
(RN-02), aplicado en el modelo y en el controlador.

Rutas: `/ordenes-trabajo` (listado), `/ordenes-trabajo/abrir`, `GET|PATCH
/ordenes-trabajo/{ot}`, `PATCH /ordenes-trabajo/{ot}/estado`.

Requerimientos: RF-07, RF-08, RF-09 (completado en HU4), RNF-07.

Mecánicos de ejemplo para desarrollo: `php artisan db:seed --class=MechanicSeeder`.

### HU3 — Descuento automático de stock al usar repuestos (Iteración 2 / OE2)

Inventario de repuestos (`spare_parts`: código único, nombre, categoría, precio de
venta, costo, ubicación, stock, stock mínimo) en `/inventario`, con alta, edición y
ajuste manual de stock. Desde el detalle de la OT se registran los repuestos usados
(`work_order_items` de tipo `part`) y el stock se descuenta **en la misma transacción**
(RN-01, `WorkOrder::addPart()`); el descuento es atómico y se rechaza si no hay stock
suficiente, de modo que el stock nunca queda negativo. El precio de venta se congela en
el ítem. Quitar un repuesto de una OT abierta devuelve las unidades al stock. Cuando un
repuesto queda en `stock <= min_stock` se muestra la alerta de stock crítico tras el
consumo y en el inventario (filtro "solo críticos"). Con la OT Completada o Entregada
no se pueden agregar ni quitar ítems (RN-02, en el modelo y en el controlador).

Rutas: `GET /inventario`, `GET /inventario/nuevo`, `POST /inventario`,
`GET /inventario/{repuesto}/editar`, `PUT /inventario/{repuesto}`,
`POST /ordenes-trabajo/{ot}/repuestos`, `DELETE /ordenes-trabajo/{ot}/items/{item}`.

Requerimientos: RF-13, RF-14, RF-15, RN-01, RN-02, RNF-07.

Repuestos de ejemplo para desarrollo: `php artisan db:seed --class=SparePartSeeder`.

### HU4 — Cálculo automático del costo total de la orden (Iteración 2 / OE2)

La mano de obra se registra en la OT como ítem de tipo `labor` (descripción, horas,
tarifa por hora). El subtotal de cada ítem (`cantidad × precio unitario`) lo calcula
siempre el backend al guardar, nunca el cliente. El detalle de la OT muestra el
desglose **repuestos + mano de obra = total** (`WorkOrder::partsTotal()`,
`laborTotal()`, `total()`, sumados en centavos para evitar errores de redondeo) y el
listado de órdenes muestra el total de cada una. Con la OT cerrada, ítems y precios son
inmutables (RN-02).

Rutas: `POST /ordenes-trabajo/{ot}/mano-de-obra`, `DELETE /ordenes-trabajo/{ot}/items/{item}`.

Requerimientos: RF-09, RF-14, RN-02, RNF-07.
