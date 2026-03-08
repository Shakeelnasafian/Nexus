<?php

namespace App\Domain\Vendor\Actions;

use App\Domain\Vendor\Events\VendorTerminated;
use App\Domain\Vendor\Models\Vendor;
use App\Domain\Vendor\States\Vendor\Terminated;
use Illuminate\Support\Facades\Event;

class TerminateVendor
{
    public function execute(Vendor $vendor): void
    {
        $vendor->terminated_at = now();
        $vendor->state->transitionTo(Terminated::class);

        Event::dispatch(new VendorTerminated(
            tenantId: $vendor->tenant_id,
            vendorId: $vendor->id,
            vendorName: $vendor->name,
        ));
    }
}
