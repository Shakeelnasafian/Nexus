<?php

namespace App\Domain\Workflow\StepHandlers;

use App\Domain\Workflow\Contracts\StepHandler;
use App\Domain\Workflow\Jobs\SendWorkflowNotification;
use App\Domain\Workflow\Models\WorkflowNotification;
use App\Domain\Workflow\Models\WorkflowStepRun;

class NotifyStepHandler implements StepHandler
{
    public function handle(WorkflowStepRun $stepRun): void
    {
        $payload = $stepRun->workflowStep->payload ?? [];
        $run = $stepRun->workflowRun;

        $notification = (new WorkflowNotification)->forceFill([
            'tenant_id' => $run->tenant_id,
            'workflow_run_id' => $run->id,
            'workflow_step_run_id' => $stepRun->id,
            'channel' => $payload['channel'] ?? 'default',
            'message' => $payload['message'] ?? null,
            'payload' => $payload,
        ]);
        $notification->save();

        SendWorkflowNotification::dispatch($notification->id);

        $stepRun->output = [
            'notification_id' => $notification->id,
            'channel' => $notification->channel,
        ];
    }
}
