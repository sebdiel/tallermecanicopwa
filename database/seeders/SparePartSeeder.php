<?php

namespace Database\Seeders;

use App\Models\SparePart;
use Illuminate\Database\Seeder;

/**
 * Catalogo de repuestos de ejemplo para desarrollo (HU3). Incluye uno en
 * stock critico para ver la alerta (RF-15).
 */
class SparePartSeeder extends Seeder
{
    public function run(): void
    {
        $parts = [
            ['code' => 'FIL-ACE-01', 'name' => 'Filtro de aceite', 'category' => 'Motor', 'sale_price' => 45, 'cost' => 30, 'location' => 'Estante A1', 'stock' => 24, 'min_stock' => 5],
            ['code' => 'ACE-15W40', 'name' => 'Aceite 15W-40 (litro)', 'category' => 'Lubricantes', 'sale_price' => 38, 'cost' => 26, 'location' => 'Estante A2', 'stock' => 40, 'min_stock' => 10],
            ['code' => 'PAS-FRE-DEL', 'name' => 'Pastillas de freno delanteras', 'category' => 'Frenos', 'sale_price' => 180, 'cost' => 120, 'location' => 'Estante B1', 'stock' => 8, 'min_stock' => 2],
            ['code' => 'BUJ-NGK-01', 'name' => 'Bujia NGK', 'category' => 'Electrico', 'sale_price' => 35, 'cost' => 22, 'location' => 'Estante C1', 'stock' => 16, 'min_stock' => 8],
            ['code' => 'ROT-SUP-01', 'name' => 'Rotula de suspension', 'category' => 'Suspension', 'sale_price' => 150, 'cost' => 95, 'location' => 'Estante D1', 'stock' => 2, 'min_stock' => 2],
        ];

        foreach ($parts as $part) {
            SparePart::firstOrCreate(['code' => $part['code']], $part);
        }
    }
}
