<?php

namespace App\Domain\Vendor\Events;

final readonly class VendorSuspended
{
    public function __construct(
        public int $tenantId,
        public int $vendorId,
        public string $vendorName,
    ) {}
}
