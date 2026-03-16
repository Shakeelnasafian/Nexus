<?php

namespace App\Domain\Accountability\Actions;

use App\Domain\Accountability\Enums\SlaStatus;
use App\Domain\Accountability\Models\SlaRecord;

class OpenSlaRecord
{
    public function execute(
        int $tenantId,
        int $vendorId,
        string $title,
        ?int $contractId = null,
    ): SlaRecord {
        return SlaRecord::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'vendor_id' => $vendorId,
            'contract_id' => $contractId,
            'title' => $title,
            'status' => SlaStatus::Active,
            'started_at' => now(),
        ]);
    }
}
