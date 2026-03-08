<?php

namespace App\Domain\Vendor\Events;

final readonly class VendorActivated
{
    public function __construct(
        public int $tenantId,
        public int $vendorId,
        public string $vendorName,
    ) {}
}
