<?php

namespace App\Domain\Accountability\Actions;

use App\Domain\Accountability\Enums\SlaStatus;
use App\Domain\Accountability\Events\SlaBreached;
use App\Domain\Accountability\Models\SlaRecord;
use Illuminate\Support\Facades\Event;

class BreachSlaRecord
{
    public function execute(SlaRecord $record): void
    {
        $record->status = SlaStatus::Breached;
        $record->breached_at = now();
        $record->save();

        Event::dispatch(new SlaBreached(
            tenantId: $record->tenant_id,
            slaRecordId: $record->id,
            vendorId: $record->vendor_id,
            title: $record->title,
        ));
    }
}
