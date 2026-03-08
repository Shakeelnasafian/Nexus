<?php

namespace App\Domain\Billing\Events;

use Carbon\CarbonInterface;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class SubscriptionStarted extends ShouldBeStored
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $planId,
        public readonly CarbonInterface $startedAt,
        public readonly CarbonInterface $currentPeriodEndsAt,
    ) {
    }
}