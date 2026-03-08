<?php

namespace App\Tenancy;

use App\Tenancy\Models\Tenant;

class CurrentTenant
{
    public function __construct(
        private ?Tenant $tenant = null,
    ) {
    }

    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): int|string|null
    {
        return $this->tenant?->getKey();
    }

    public function has(): bool
    {
        return $this->tenant !== null;
    }
}