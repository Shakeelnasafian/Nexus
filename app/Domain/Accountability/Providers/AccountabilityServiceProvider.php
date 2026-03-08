<?php

namespace App\Domain\Accountability\Providers;

use Illuminate\Support\ServiceProvider;

class AccountabilityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Domain bindings and pipelines will be added in the Accountability session.
    }

    public function boot(): void
    {
        // Domain event subscriptions will be added in the Accountability session.
    }
}