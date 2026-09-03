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

    /**
     * Siguiente estado en la secuencia, o null si es el ultimo.
     */
    public function next(): ?self
    {
        return match ($this) {
            self::Pendiente => self::EnProceso,
            self::EnProceso => self::Completada,
            self::Completada => self::Entregada,
            self::Entregada => null,
        };
    }

    /**
     * Solo se permite avanzar al estado inmediatamente siguiente (secuencia
     * estricta): se rechazan saltos y retrocesos.
     */
    public function canTransitionTo(self $target): bool
    {
        return $this->next() === $target;
    }

    /**
     * En Completada o Entregada la OT es inmutable en precios/repuestos
     * (RN-02, HU2 criterio 4).
     */
    public function isLocked(): bool
    {
        return in_array($this, [self::Completada, self::Entregada], true);
    }
}
