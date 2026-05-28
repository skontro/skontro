<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level role gate. Usage: ->middleware('role:admin'). Passes when the
 * authenticated user's role meets or exceeds the required role, by ordinal
 * rank, so an owner satisfies role:admin automatically.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $required = Role::from($role);
        $user = $request->user();

        if ($user === null || ! $user->hasRole($required)) {
            abort(Response::HTTP_FORBIDDEN, 'Insufficient role for this action.');
        }

        return $next($request);
    }
}
