<?php

namespace App\Domain\Workflow\Enums;

enum WorkflowRunStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
}
