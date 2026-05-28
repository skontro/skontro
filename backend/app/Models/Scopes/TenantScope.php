<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Filters every query on a tenant-owned model to the tenant bound in the
 * current request context (the `currentTenantId` container binding set by the
 * ResolveTenant middleware).
 *
 * If no tenant is bound — console commands, seeders, the registration flow
 * that creates the very first tenant, or jobs that legitimately operate across
 * tenants — the scope is a deliberate no-op. The scope protects HTTP request
 * paths; trusted server-side code runs unscoped by design. The middleware is
 * what binds the tenant for requests, so within a request this scope is always
 * active.
 *
 * The column is table-qualified so the scope is safe under joins (an
 * unqualified `tenant_id` would be ambiguous when two scoped tables join).
 */
class TenantScope implements Scope
{
    /**
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (! app()->bound('currentTenantId')) {
            return;
        }

        $builder->where(
            $model->getTable().'.tenant_id',
            app('currentTenantId')
        );
    }
}
