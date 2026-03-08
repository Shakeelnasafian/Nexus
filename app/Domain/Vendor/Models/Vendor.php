<?php

namespace App\Domain\Vendor\Models;

use App\Domain\Vendor\States\Vendor\VendorState;
use App\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\ModelStates\HasStates;

class Vendor extends Model
{
    use BelongsToTenant;
    use HasStates;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'state',
        'contact_name',
        'contact_email',
        'notes',
        'activated_at',
        'suspended_at',
        'terminated_at',
    ];

    protected function casts(): array
    {
        return [
            'state' => VendorState::class,
            'activated_at' => 'immutable_datetime',
            'suspended_at' => 'immutable_datetime',
            'terminated_at' => 'immutable_datetime',
        ];
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}
