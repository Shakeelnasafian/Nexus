<?php

namespace App\Domain\Vendor\Events;

final readonly class VendorTerminated
{
    public function __construct(
        public int $tenantId,
        public int $vendorId,
        public string $vendorName,
    ) {}
}
