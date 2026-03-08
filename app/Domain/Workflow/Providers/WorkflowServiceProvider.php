<?php

namespace App\Domain\Workflow\Providers;

use Illuminate\Support\ServiceProvider;

class WorkflowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Domain bindings and pipelines will be added in the Workflow session.
    }

    public function boot(): void
    {
        // Domain event subscriptions will be added in the Workflow session.
    }
}