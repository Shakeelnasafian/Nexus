<?php

namespace App\Domain\Workflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowStep extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'workflow_id',
        'sort_order',
        'action_type',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'payload' => 'array',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function stepRuns(): HasMany
    {
        return $this->hasMany(WorkflowStepRun::class);
    }
}
