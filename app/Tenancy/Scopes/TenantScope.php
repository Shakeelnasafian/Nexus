<?php

namespace App\Tenancy\Scopes;

use App\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = $this->currentTenant->id();

        if ($tenantId === null) {
            return;
        }

        $tenantColumn = method_exists($model, 'getTenantForeignKey')
            ? $model->getTenantForeignKey()
            : 'tenant_id';

        $builder->where($model->qualifyColumn($tenantColumn), $tenantId);
    }
}