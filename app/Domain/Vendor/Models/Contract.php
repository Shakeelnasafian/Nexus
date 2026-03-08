<?php

namespace App\Domain\Vendor\Models;

use App\Domain\Vendor\States\Contract\ContractState;
use App\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\ModelStates\HasStates;

class Contract extends Model
{
    use BelongsToTenant;
    use HasStates;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'vendor_id',
        'title',
        'state',
        'value_amount',
        'currency',
        'starts_at',
        'ends_at',
        'activated_at',
        'expired_at',
        'terminated_at',
    ];

    protected function casts(): array
    {
        return [
            'state' => ContractState::class,
            'value_amount' => 'integer',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'activated_at' => 'immutable_datetime',
            'expired_at' => 'immutable_datetime',
            'terminated_at' => 'immutable_datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
