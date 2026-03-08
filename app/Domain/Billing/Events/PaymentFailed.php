<?php

namespace App\Domain\Billing\Events;

use Carbon\CarbonInterface;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class PaymentFailed extends ShouldBeStored
{
    public function __construct(
        public readonly int $tenantId,
        public readonly CarbonInterface $failedAt,
        public readonly ?string $reason = null,
    ) {
    }
}