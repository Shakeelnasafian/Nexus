<?php

namespace App\Domain\Vendor\Actions;

use App\Domain\Vendor\Models\Contract;
use App\Domain\Vendor\States\Contract\Expired;

class ExpireContract
{
    public function execute(Contract $contract): void
    {
        $contract->expired_at = now();
        $contract->state->transitionTo(Expired::class);
    }
}
