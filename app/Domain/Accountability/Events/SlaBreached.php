<?php

namespace App\Domain\Accountability\Events;

final readonly class SlaBreached
{
    public function __construct(
        public int $tenantId,
        public int $slaRecordId,
        public int $vendorId,
        public string $title,
    ) {}
}
