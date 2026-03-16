<?php

namespace App\Domain\Accountability\Models;

use App\Domain\Accountability\Enums\ObjectiveStatus;
use App\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Objective extends Model
{
    use BelongsToTenant;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'due_date',
        'status',
        'completed_at',
        'missed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ObjectiveStatus::class,
            'due_date' => 'immutable_date',
            'completed_at' => 'immutable_datetime',
            'missed_at' => 'immutable_datetime',
        ];
    }
}
