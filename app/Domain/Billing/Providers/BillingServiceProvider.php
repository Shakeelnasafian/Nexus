<?php

namespace App\Domain\Billing\Providers;

use Illuminate\Support\ServiceProvider;

class BillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Domain bindings and pipelines will be added in the Billing session.
    }

    public function boot(): void
    {
        // Domain event subscriptions will be added in the Billing session.
    }
}