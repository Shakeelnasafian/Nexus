<?php

namespace App\Domain\Billing\Reactors;

use App\Domain\Billing\Events\PaymentFailed;
use App\Domain\Billing\Jobs\SendDunningNotification;
use Spatie\EventSourcing\EventHandlers\Reactors\Reactor;

class SubscriptionPaymentFailureReactor extends Reactor
{
    public function onPaymentFailed(PaymentFailed $event): void
    {
        SendDunningNotification::dispatch(
            subscriptionUuid: (string) $event->aggregateRootUuid(),
            tenantId: $event->tenantId,
            reason: $event->reason,
        );
    }
}