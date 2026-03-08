<?php

namespace App\Domain\Vendor\Actions;

use App\Domain\Vendor\Events\VendorActivated;
use App\Domain\Vendor\Models\Vendor;
use App\Domain\Vendor\States\Vendor\Active;
use Illuminate\Support\Facades\Event;

class ActivateVendor
{
    public function execute(Vendor $vendor): void
    {
        $vendor->activated_at = now();
        $vendor->state->transitionTo(Active::class);

        Event::dispatch(new VendorActivated(
            tenantId: $vendor->tenant_id,
            vendorId: $vendor->id,
            vendorName: $vendor->name,
        ));
    }
}
