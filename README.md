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
