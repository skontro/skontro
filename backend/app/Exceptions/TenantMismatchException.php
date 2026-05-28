<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when code attempts to reassign a record's tenant_id after creation,
 * or to create a tenant-owned record under a tenant other than the one bound
 * in the current request context. A record's tenant is immutable for its
 * lifetime — moving data across tenants is never a legitimate operation.
 */
class TenantMismatchException extends RuntimeException
{
    public static function reassignment(): self
    {
        return new self('A record\'s tenant_id is immutable and cannot be changed after creation.');
    }
}
