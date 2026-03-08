<?php

namespace App\Domain\Billing\Exceptions;

use DomainException;

class InvalidSubscriptionTransition extends DomainException
{
    public static function alreadyStarted(): self
    {
        return new self('Subscriptions can only be started once.');
    }

    public static function notStarted(string $action): self
    {
        return new self("Subscriptions must be started before they can be {$action}.");
    }

    public static function cancelled(string $action): self
    {
        return new self("Cancelled subscriptions cannot be {$action}.");
    }

    public static function alreadyCancelled(): self
    {
        return new self('Subscriptions can only be cancelled once.');
    }
}