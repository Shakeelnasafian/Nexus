<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Tenancy\Providers\TenantServiceProvider::class,
    App\Domain\Billing\Providers\BillingServiceProvider::class,
    App\Domain\Vendor\Providers\VendorServiceProvider::class,
    App\Domain\Accountability\Providers\AccountabilityServiceProvider::class,
    App\Domain\Workflow\Providers\WorkflowServiceProvider::class,
];