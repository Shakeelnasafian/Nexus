<?php

namespace App\Domain\Vendor\Events;

final readonly class ContractActivated
{
    public function __construct(
        public int $tenantId,
        public int $vendorId,
        public int $contractId,
        public string $contractTitle,
    ) {}
}
