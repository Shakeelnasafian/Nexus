<?php

namespace App\Domain\Workflow\Models;

use App\Domain\Workflow\Enums\WorkflowTrigger;
use App\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    use BelongsToTenant;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'trigger_event',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'trigger_event' => WorkflowTrigger::class,
            'is_active' => 'boolean',
        ];
    }

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('sort_order');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(WorkflowRun::class);
    }
}
