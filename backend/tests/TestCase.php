<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Register the test-only migration path on the migrator immediately after
     * the application is (re)built, before RefreshDatabase migrates in
     * setUpTraits(). The path lives under tests/ and never ships to
     * production, so the support schema exists only during the test run.
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $this->app->make('migrator')->path(__DIR__.'/Support/migrations');
    }
}
