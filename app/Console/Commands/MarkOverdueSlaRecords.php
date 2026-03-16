<?php

namespace App\Console\Commands;

use App\Domain\Accountability\Actions\BreachSlaRecord;
use App\Domain\Accountability\Enums\SlaStatus;
use App\Domain\Accountability\Models\SlaRecord;
use Illuminate\Console\Command;

class MarkOverdueSlaRecords extends Command
{
    protected $signature = 'sla:mark-overdue
                            {--days=30 : Number of days after which an active SLA record is considered overdue}';

    protected $description = 'Breach all active SLA records that have been open longer than the configured number of days';

    public function handle(BreachSlaRecord $action): int
    {
        $cutoff = now()->subDays((int) $this->option('days'));

        $records = SlaRecord::withoutGlobalScopes()
            ->where('status', SlaStatus::Active)
            ->where('started_at', '<=', $cutoff)
            ->get();

        foreach ($records as $record) {
            $action->execute($record);
            $this->line("Breached SLA record #{$record->id}: {$record->title}");
        }

        $this->info("Done. {$records->count()} SLA record(s) breached.");

        return self::SUCCESS;
    }
}
