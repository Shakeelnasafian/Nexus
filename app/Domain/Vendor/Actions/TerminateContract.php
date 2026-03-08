<?php

namespace App\Domain\Vendor\Actions;

use App\Domain\Vendor\Models\Contract;
use App\Domain\Vendor\States\Contract\Terminated;

class TerminateContract
{
    public function execute(Contract $contract): void
    {
        $contract->terminated_at = now();
        $contract->state->transitionTo(Terminated::class);
    }
}
