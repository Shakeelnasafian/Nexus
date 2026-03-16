<?php

namespace App\Domain\Accountability\Actions;

use App\Domain\Accountability\Enums\ObjectiveStatus;
use App\Domain\Accountability\Events\ObjectiveCompleted;
use App\Domain\Accountability\Models\Objective;
use Illuminate\Support\Facades\Event;

class CompleteObjective
{
    public function execute(Objective $objective): void
    {
        $objective->status = ObjectiveStatus::Completed;
        $objective->completed_at = now();
        $objective->save();

        Event::dispatch(new ObjectiveCompleted(
            tenantId: $objective->tenant_id,
            objectiveId: $objective->id,
            title: $objective->title,
        ));
    }
}
