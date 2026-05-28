<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\TenantMismatchException;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Opt-in tenant ownership. A model that uses this trait is:
 *   - automatically filtered to the current tenant on every query
 *     (via TenantScope),
 *   - automatically stamped with the current tenant_id on create, so
 *     application code never sets tenant_id by hand, and
 *   - prevented from ever changing its tenant_id after creation.
 *
 * Together these make cross-tenant data access structurally impossible on
 * request paths, rather than a rule a developer must remember to follow.
 *
 * @phpstan-require-extends Model
 */
// @phpstan-ignore trait.unused (first app consumer is the Customer model next milestone; behaviour is exercised now by tests/Support TenantScopedTestModel)
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            if (app()->bound('currentTenantId') && empty($model->getAttribute('tenant_id'))) {
                $model->setAttribute('tenant_id', app('currentTenantId'));
            }
        });

        static::updating(function (Model $model): void {
            if ($model->isDirty('tenant_id') && $model->getOriginal('tenant_id') !== null) {
                throw TenantMismatchException::reassignment();
            }
        });
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Explicit, greppable, auditable escape hatch for the rare legitimate
     * cross-tenant query. The only supported way to bypass the scope.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithoutTenantScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForTenant(Builder $query, Tenant $tenant): Builder
    {
        return $query->withoutGlobalScope(TenantScope::class)
            ->where($this->getTable().'.tenant_id', $tenant->id);
    }
}
