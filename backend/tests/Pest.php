<?php

use App\Models\Tenant;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature', 'Unit');

// The cookie-auth endpoints rely on the session middleware that Sanctum's
// statefulApi() attaches only to requests it recognises as coming from the
// SPA frontend (matched by Origin/Referer against the stateful domains). The
// real SPA always sends that header; these tests simulate it so the
// register/login/logout session calls run as they do in the browser.
pest()->beforeEach(function () {
    $this->withHeader('Origin', config('app.url'));
})->in('Feature/Auth', 'Feature/Tenancy');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Bind a tenant into the request context the way ResolveTenant will at runtime,
 * so the TenantScope global scope filters subsequent queries to it.
 */
function actAsTenant(Tenant $tenant): void
{
    app()->instance('currentTenant', $tenant);
    app()->instance('currentTenantId', $tenant->id);
}

function clearTenantContext(): void
{
    app()->forgetInstance('currentTenant');
    app()->forgetInstance('currentTenantId');
}
