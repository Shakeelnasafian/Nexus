<?php

namespace App\Domain\Workflow\Jobs;

use App\Domain\Workflow\Actions\ExecuteWorkflowRun;
use App\Domain\Workflow\Models\WorkflowRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunWorkflowJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $workflowRunId) {}

    public function handle(ExecuteWorkflowRun $action): void
    {
        $run = WorkflowRun::withoutGlobalScopes()->findOrFail($this->workflowRunId);

        $action->execute($run);
    }
}
