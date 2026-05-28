<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs after authentication on tenant-scoped routes. Resolves the
 * authenticated user's tenant and binds it into the container so the
 * TenantScope global scope filters every subsequent query in this request to
 * that tenant.
 *
 * Binding both the model (currentTenant) and its id (currentTenantId): the
 * scope only needs the id (cheaper, no hydration), while controllers and
 * resources often want the full model.
 */
class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Defense in depth: the non-nullable FK makes a tenantless user
        // impossible, but never trust that assumption at a trust boundary.
        if ($user === null || $user->tenant === null) {
            abort(Response::HTTP_FORBIDDEN, 'No tenant context for this request.');
        }

        app()->instance('currentTenant', $user->tenant);
        app()->instance('currentTenantId', $user->tenant->id);

        return $next($request);
    }
}
