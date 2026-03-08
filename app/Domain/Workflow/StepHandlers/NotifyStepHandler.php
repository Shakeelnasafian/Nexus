<?php

namespace App\Domain\Workflow\StepHandlers;

use App\Domain\Workflow\Contracts\StepHandler;
use App\Domain\Workflow\Models\WorkflowStepRun;

class NotifyStepHandler implements StepHandler
{
    public function handle(WorkflowStepRun $stepRun): void
    {
        // Notification delivery will be implemented in the Accountability integration session.
        // For now the step records its intent so the run history is complete.
        $stepRun->output = [
            'channel' => $stepRun->workflowStep->payload['channel'] ?? 'default',
            'deferred' => true,
        ];
    }
}
