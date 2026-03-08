<?php

namespace App\Domain\Vendor\Providers;

use App\Domain\Vendor\Actions\ActivateContract;
use App\Domain\Vendor\Actions\ActivateVendor;
use App\Domain\Vendor\Actions\CreateContract;
use App\Domain\Vendor\Actions\CreateVendor;
use App\Domain\Vendor\Actions\ExpireContract;
use App\Domain\Vendor\Actions\SuspendVendor;
use App\Domain\Vendor\Actions\TerminateContract;
use App\Domain\Vendor\Actions\TerminateVendor;
use Illuminate\Support\ServiceProvider;

class VendorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CreateVendor::class);
        $this->app->bind(ActivateVendor::class);
        $this->app->bind(SuspendVendor::class);
        $this->app->bind(TerminateVendor::class);
        $this->app->bind(CreateContract::class);
        $this->app->bind(ActivateContract::class);
        $this->app->bind(ExpireContract::class);
        $this->app->bind(TerminateContract::class);
    }

    public function boot(): void
    {
        // Cross-domain event listeners will be wired here in the integration session.
    }
}
