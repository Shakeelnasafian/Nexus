<?php

namespace App\Domain\Accountability\Providers;

use App\Domain\Accountability\Actions\CloseSlaRecordsForVendor;
use App\Domain\Accountability\Actions\OpenSlaRecord;
use App\Domain\Vendor\Events\ContractActivated;
use App\Domain\Vendor\Events\VendorTerminated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AccountabilityServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Event::listen(ContractActivated::class, function (ContractActivated $event): void {
            app(OpenSlaRecord::class)->execute(
                tenantId: $event->tenantId,
                vendorId: $event->vendorId,
                title: "Contract SLA: {$event->contractTitle}",
                contractId: $event->contractId,
            );
        });

        Event::listen(VendorTerminated::class, function (VendorTerminated $event): void {
            app(CloseSlaRecordsForVendor::class)->execute(
                tenantId: $event->tenantId,
                vendorId: $event->vendorId,
            );
        });
    }
}
