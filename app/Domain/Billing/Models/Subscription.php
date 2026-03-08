<?php

namespace App\Domain\Billing\Models;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use BelongsToTenant;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'plan_id',
        'status',
        'started_at',
        'renewed_at',
        'cancelled_at',
        'payment_failed_at',
        'current_period_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'started_at' => 'immutable_datetime',
            'renewed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'payment_failed_at' => 'immutable_datetime',
            'current_period_ends_at' => 'immutable_datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}