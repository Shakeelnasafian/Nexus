<?php

namespace App\Domain\Billing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendDunningNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $subscriptionUuid,
        public readonly int $tenantId,
        public readonly ?string $reason = null,
    ) {
    }

    public function handle(): void
    {
        // Notification delivery is introduced in a later Billing session.
    }
}