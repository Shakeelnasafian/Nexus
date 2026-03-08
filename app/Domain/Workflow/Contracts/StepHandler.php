<?php

namespace App\Domain\Workflow\Contracts;

use App\Domain\Workflow\Models\WorkflowStepRun;

interface StepHandler
{
    public function handle(WorkflowStepRun $stepRun): void;
}
