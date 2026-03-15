<?php

namespace App\Domain\Accountability\Actions;

use App\Domain\Accountability\Enums\ObjectiveStatus;
use App\Domain\Accountability\Models\Objective;
use Carbon\CarbonImmutable;

class CreateObjective
{
    public function execute(
        string $title,
        ?string $description = null,
        ?CarbonImmutable $dueDate = null,
    ): Objective {
        return Objective::create([
            'title' => $title,
            'description' => $description,
            'due_date' => $dueDate,
            'status' => ObjectiveStatus::Active,
        ]);
    }
}
