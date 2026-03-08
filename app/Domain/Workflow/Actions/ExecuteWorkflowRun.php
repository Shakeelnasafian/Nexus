<?php

namespace App\Domain\Workflow\Actions;

use App\Domain\Workflow\Enums\WorkflowRunStatus;
use App\Domain\Workflow\Enums\WorkflowStepStatus;
use App\Domain\Workflow\Models\WorkflowRun;
use App\Domain\Workflow\Models\WorkflowStep;
use App\Domain\Workflow\Models\WorkflowStepRun;
use App\Domain\Workflow\StepHandlerRegistry;
use Throwable;

class ExecuteWorkflowRun
{
    public function __construct(private readonly StepHandlerRegistry $registry) {}

    public function execute(WorkflowRun $run): void
    {
        $run->status = WorkflowRunStatus::Running;
        $run->started_at = now();
        $run->save();

        $steps = WorkflowStep::where('workflow_id', $run->workflow_id)
            ->orderBy('sort_order')
            ->get();

        foreach ($steps as $step) {
            $stepRun = WorkflowStepRun::create([
                'workflow_run_id' => $run->id,
                'workflow_step_id' => $step->id,
                'sort_order' => $step->sort_order,
                'status' => WorkflowStepStatus::Pending,
            ]);

            try {
                $handler = $this->registry->resolve($step->action_type);
                $handler->handle($stepRun);

                $stepRun->status = WorkflowStepStatus::Completed;
                $stepRun->ran_at = now();
                $stepRun->save();
            } catch (Throwable $e) {
                $stepRun->status = WorkflowStepStatus::Failed;
                $stepRun->ran_at = now();
                $stepRun->save();

                $run->status = WorkflowRunStatus::Failed;
                $run->failed_at = now();
                $run->failure_reason = $e->getMessage();
                $run->save();

                return;
            }
        }

        $run->status = WorkflowRunStatus::Completed;
        $run->completed_at = now();
        $run->save();
    }
}
