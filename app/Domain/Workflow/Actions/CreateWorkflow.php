<?php

namespace App\Domain\Workflow\Actions;

use App\Domain\Workflow\Enums\WorkflowTrigger;
use App\Domain\Workflow\Models\Workflow;

class CreateWorkflow
{
    public function execute(string $name, WorkflowTrigger $trigger, bool $isActive = true): Workflow
    {
        return Workflow::create([
            'name' => $name,
            'trigger_event' => $trigger,
            'is_active' => $isActive,
        ]);
    }
}
