<?php

namespace App\Domain\Billing\Models;

use App\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use BelongsToTenant;

    protected $table = 'billing_plans';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'price_amount',
        'currency',
        'billing_interval',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'price_amount' => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}