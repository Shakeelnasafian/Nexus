<?php

namespace App\Domain\Vendor\Actions;

use App\Domain\Vendor\Events\ContractActivated;
use App\Domain\Vendor\Models\Contract;
use App\Domain\Vendor\States\Contract\Active;
use Illuminate\Support\Facades\Event;

class ActivateContract
{
    public function execute(Contract $contract): void
    {
        $contract->activated_at = now();
        $contract->state->transitionTo(Active::class);

        Event::dispatch(new ContractActivated(
            tenantId: $contract->tenant_id,
            vendorId: $contract->vendor_id,
            contractId: $contract->id,
            contractTitle: $contract->title,
        ));
    }
}
