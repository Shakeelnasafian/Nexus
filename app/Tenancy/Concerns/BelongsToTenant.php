<?php

namespace App\Tenancy\Concerns;

use App\Tenancy\CurrentTenant;
use App\Tenancy\Models\Tenant;
use App\Tenancy\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(app(TenantScope::class));

        static::creating(function (Model $model): void {
            $tenantId = app(CurrentTenant::class)->id();

            if ($tenantId === null || $model->getAttribute($model->getTenantForeignKey()) !== null) {
                return;
            }

            $model->setAttribute($model->getTenantForeignKey(), $tenantId);
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, $this->getTenantForeignKey());
    }

    public function getTenantForeignKey(): string
    {
        return 'tenant_id';
    }
}