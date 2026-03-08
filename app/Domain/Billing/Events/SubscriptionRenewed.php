<?php

namespace App\Domain\Billing\Events;

use Carbon\CarbonInterface;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class SubscriptionRenewed extends ShouldBeStored
{
    public function __construct(
        public readonly int $tenantId,
        public readonly CarbonInterface $renewedAt,
        public readonly CarbonInterface $currentPeriodEndsAt,
    ) {
    }
}