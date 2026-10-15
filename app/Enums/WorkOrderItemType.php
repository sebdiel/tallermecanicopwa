<?php

namespace App\Enums;

/**
 * Tipo de item de una orden de trabajo: repuesto del catalogo (HU3) o mano
 * de obra (HU4).
 */
enum WorkOrderItemType: string
{
    case Part = 'part';
    case Labor = 'labor';

    public function label(): string
    {
        return match ($this) {
            self::Part => 'Repuesto',
            self::Labor => 'Mano de obra',
        };
    }
}
