<?php

namespace App\Enums;

/**
 * Estados de una orden de trabajo, en secuencia estricta:
 * Pendiente -> En Proceso -> Completada -> Entregada (HU2, criterio 1).
 */
enum WorkOrderStatus: string
{
    case Pendiente = 'pendiente';
    case EnProceso = 'en_proceso';
    case Completada = 'completada';
    case Entregada = 'entregada';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::EnProceso => 'En Proceso',
            self::Completada => 'Completada',
            self::Entregada => 'Entregada',
        };
    }

    /**
     * Valores de todos los estados, en orden de secuencia.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status): string => $status->value, self::cases());
    }
}
