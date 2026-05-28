<?php

declare(strict_types=1);

namespace Tests\Support\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A minimal tenant-owned model used solely to exercise the BelongsToTenant
 * trait and TenantScope in isolation, before any real business model adopts
 * them. Backed by the tenant_scoped_test_models table created in the
 * test-only migration. Never used in application code.
 */
class TenantScopedTestModel extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'tenant_scoped_test_models';

    /** @var list<string> */
    protected $fillable = [
        'tenant_id',
        'label',
    ];

    protected static function newFactory(): TenantScopedTestModelFactory
    {
        return TenantScopedTestModelFactory::new();
    }
}
