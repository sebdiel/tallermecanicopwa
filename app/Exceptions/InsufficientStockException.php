<?php

namespace App\Exceptions;

use App\Models\SparePart;
use DomainException;

/**
 * Se lanza al intentar usar en una OT mas unidades de un repuesto de las que
 * hay en stock (RN-01: el stock nunca queda negativo).
 */
class InsufficientStockException extends DomainException
{
    public static function for(SparePart $part, int $requested): self
    {
        return new self(sprintf(
            'Stock insuficiente de "%s": se pidieron %d y hay %d disponible(s).',
            $part->name,
            $requested,
            $part->stock,
        ));
    }
}
