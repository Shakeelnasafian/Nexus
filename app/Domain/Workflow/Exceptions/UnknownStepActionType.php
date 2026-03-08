<?php

namespace App\Domain\Workflow\Exceptions;

use RuntimeException;

class UnknownStepActionType extends RuntimeException
{
    public static function for(string $actionType): self
    {
        return new self("No step handler is registered for action type '{$actionType}'.");
    }
}
