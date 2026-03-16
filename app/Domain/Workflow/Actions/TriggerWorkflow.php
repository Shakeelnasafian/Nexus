<?php

namespace App\Domain\Workflow\Actions;

use App\Domain\Workflow\Enums\WorkflowRunStatus;
use App\Domain\Workflow\Jobs\RunWorkflowJob;
use App\Domain\Workflow\Models\Workflow;
use App\Domain\Workflow\Models\WorkflowRun;

class TriggerWorkflow
{
    /**
     * Find all active workflows for the given trigger and dispatch a run for each.
     *
     * This action bypasses the global tenant scope so it can be called safely from
     * queued or event-driven contexts where CurrentTenant is not set.
     *
     * @param array<string, mixed> $triggerPayload
     */
    public function execute(int $tenantId, string $triggerEvent, array $triggerPayload = []): void
    {
        $workflows = Workflow::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('trigger_event', $triggerEvent)
            ->where('is_active', true)
            ->get();

        foreach ($workflows as $workflow) {
            $run = (new WorkflowRun)->forceFill([
                'tenant_id' => $tenantId,
                'workflow_id' => $workflow->id,
                'trigger_event' => $triggerEvent,
                'trigger_payload' => $triggerPayload,
                'status' => WorkflowRunStatus::Pending,
            ]);
            $run->save();

            RunWorkflowJob::dispatch($run->id);
        }
    }
}
