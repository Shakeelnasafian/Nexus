<?php

namespace App\Tenancy\Providers;

use App\Tenancy\CurrentTenant;
use Illuminate\Support\ServiceProvider;

class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CurrentTenant::class, fn (): CurrentTenant => new CurrentTenant());
    }

    public function boot(): void
    {
        // Tenant resolution hooks are added as the application gains auth and request context.
    }
}