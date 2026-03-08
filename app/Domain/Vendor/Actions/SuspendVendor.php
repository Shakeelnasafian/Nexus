<?php

namespace App\Domain\Vendor\Actions;

use App\Domain\Vendor\Events\VendorSuspended;
use App\Domain\Vendor\Models\Vendor;
use App\Domain\Vendor\States\Vendor\Suspended;
use Illuminate\Support\Facades\Event;

class SuspendVendor
{
    public function execute(Vendor $vendor): void
    {
        $vendor->suspended_at = now();
        $vendor->state->transitionTo(Suspended::class);

        Event::dispatch(new VendorSuspended(
            tenantId: $vendor->tenant_id,
            vendorId: $vendor->id,
            vendorName: $vendor->name,
        ));
    }
}
