<?php

namespace App\Domain\Workflow\Models;

use App\Domain\Workflow\Enums\WorkflowStepStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStepRun extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'workflow_run_id',
        'workflow_step_id',
        'sort_order',
        'status',
        'output',
        'ran_at',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'status' => WorkflowStepStatus::class,
            'output' => 'array',
            'ran_at' => 'immutable_datetime',
        ];
    }

    public function workflowRun(): BelongsTo
    {
        return $this->belongsTo(WorkflowRun::class);
    }

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class);
    }
}
