<?php

namespace App\Domain\Accountability\Actions;

use App\Domain\Accountability\Enums\SlaStatus;
use App\Domain\Accountability\Models\SlaRecord;

class CloseSlaRecordsForVendor
{
    public function execute(int $tenantId, int $vendorId): void
    {
        SlaRecord::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('vendor_id', $vendorId)
            ->where('status', SlaStatus::Active)
            ->get()
            ->each(function (SlaRecord $record): void {
                $record->status = SlaStatus::Closed;
                $record->closed_at = now();
                $record->save();
            });
    }
}
