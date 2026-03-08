<?php

namespace App\Domain\Workflow\Enums;

enum WorkflowStepStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
}
