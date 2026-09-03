<?php

namespace App\Exceptions;

use DomainException;

/**
 * Se lanza al intentar editar precios/repuestos de una OT que ya esta
 * Completada o Entregada (RN-02, HU2 criterio 4).
 */
class WorkOrderLockedException extends DomainException
{
    public static function make(): self
    {
        return new self('La orden de trabajo esta completada o entregada y no admite cambios en el diagnostico ni en los repuestos.');
    }
}
