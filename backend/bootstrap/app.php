<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Treat same-top-level-domain SPA requests as stateful so they
        // authenticate via the session cookie (with CSRF protection) rather
        // than bearer tokens. Wires Sanctum's stateful guard correctly on
        // Laravel 11 — the older EnsureFrontendRequestsAreStateful entry is
        // not needed.
        $middleware->statefulApi();

        $middleware->alias([
            'tenant' => ResolveTenant::class,
            'role' => EnsureRole::class,
        ]);

        // Resolve the tenant before route-model binding runs. Implicit binding
        // (SubstituteBindings) looks up {customer} through the tenant global
        // scope, so the tenant must be bound first — otherwise the lookup is
        // unscoped and a cross-tenant fetch would succeed instead of 404ing.
        // Forcing ResolveTenant ahead of SubstituteBindings in the priority
        // list is what makes the 404-not-403 contract automatic.
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: ResolveTenant::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
