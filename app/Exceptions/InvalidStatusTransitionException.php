<?php

namespace App\Exceptions;

use App\Enums\WorkOrderStatus;
use DomainException;

class InvalidStatusTransitionException extends DomainException
{
    public static function between(WorkOrderStatus $from, WorkOrderStatus $to): self
    {
        return new self(sprintf(
            'No se puede pasar de "%s" a "%s": la orden de trabajo debe avanzar en secuencia.',
            $from->label(),
            $to->label(),
        ));
    }
}
