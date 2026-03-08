<?php

namespace App\Domain\Vendor\Providers;

use Illuminate\Support\ServiceProvider;

class VendorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Domain bindings and pipelines will be added in the Vendor session.
    }

    public function boot(): void
    {
        // Domain event subscriptions will be added in the Vendor session.
    }
}