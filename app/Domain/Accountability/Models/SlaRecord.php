<?php

namespace App\Domain\Accountability\Models;

use App\Domain\Accountability\Enums\SlaStatus;
use App\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlaRecord extends Model
{
    use BelongsToTenant;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'contract_id',
        'title',
        'status',
        'started_at',
        'breached_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SlaStatus::class,
            'started_at' => 'immutable_datetime',
            'breached_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Vendor\Models\Vendor::class)->withoutGlobalScopes();
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Vendor\Models\Contract::class)->withoutGlobalScopes();
    }
}
