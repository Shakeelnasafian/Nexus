<?php

namespace App\Domain\Accountability\Actions;

use App\Domain\Accountability\Enums\ObjectiveStatus;
use App\Domain\Accountability\Models\Objective;

class MissObjective
{
    public function execute(Objective $objective): void
    {
        $objective->status = ObjectiveStatus::Missed;
        $objective->missed_at = now();
        $objective->save();
    }
}
