<?php

namespace App\Domain\Workflow\StepHandlers;

use App\Domain\Workflow\Contracts\StepHandler;
use App\Domain\Workflow\Models\WorkflowStepRun;
use Illuminate\Support\Facades\Log;

class LogStepHandler implements StepHandler
{
    public function handle(WorkflowStepRun $stepRun): void
    {
        $message = $stepRun->workflowStep->payload['message'] ?? 'Workflow step executed';

        Log::info($message, [
            'workflow_run_id' => $stepRun->workflow_run_id,
            'trigger' => $stepRun->workflowRun->trigger_event,
        ]);

        $stepRun->output = ['logged' => $message];
    }
}
