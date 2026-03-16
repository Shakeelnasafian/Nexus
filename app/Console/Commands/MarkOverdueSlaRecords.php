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
        $days = (int) $this->option('days');

        if ($days <= 0) {
            $this->error('The --days option must be a positive integer.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $breached = 0;

        SlaRecord::withoutGlobalScopes()
            ->where('status', SlaStatus::Active)
            ->where('started_at', '<=', $cutoff)
            ->chunk(200, function ($records) use ($action, &$breached): void {
                foreach ($records as $record) {
                    $action->execute($record);
                    $this->line("Breached SLA record #{$record->id}: {$record->title}");
                    $breached++;
                }
            });

        $this->info("Done. {$breached} SLA record(s) breached.");

        return self::SUCCESS;
    }
}
