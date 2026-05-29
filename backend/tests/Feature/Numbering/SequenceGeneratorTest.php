<?php

declare(strict_types=1);

use App\Enums\DocumentType;
use App\Models\NumberSequence;
use App\Models\Tenant;
use App\Services\SequenceGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('numbers are gapless and sequential within a tenant/type/year', function () {
    $tenant = Tenant::factory()->create();
    $generator = new SequenceGenerator;

    $numbers = [];
    for ($i = 0; $i < 100; $i++) {
        $numbers[] = $generator->next($tenant, DocumentType::Customer, 2026);
    }

    // 100 calls -> 100 distinct numbers, no duplicates.
    expect($numbers)->toHaveCount(100)
        ->and(array_unique($numbers))->toHaveCount(100);

    // First and last follow the expected format and are consecutive.
    expect($numbers[0])->toBe('K-2026-00001')
        ->and($numbers[99])->toBe('K-2026-00100');
});

test('sequences are isolated per tenant', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $generator = new SequenceGenerator;

    $a1 = $generator->next($tenantA, DocumentType::Customer, 2026);
    $b1 = $generator->next($tenantB, DocumentType::Customer, 2026);
    $a2 = $generator->next($tenantA, DocumentType::Customer, 2026);

    // Each tenant has its own counter starting at 1.
    expect($a1)->toBe('K-2026-00001')
        ->and($b1)->toBe('K-2026-00001')
        ->and($a2)->toBe('K-2026-00002');
});

test('sequences are isolated per document type', function () {
    $tenant = Tenant::factory()->create();
    $generator = new SequenceGenerator;

    expect($generator->next($tenant, DocumentType::Customer, 2026))->toBe('K-2026-00001')
        ->and($generator->next($tenant, DocumentType::Invoice, 2026))->toBe('R-2026-00001')
        ->and($generator->next($tenant, DocumentType::Expense, 2026))->toBe('E-2026-00001');
});

test('sequences reset annually', function () {
    $tenant = Tenant::factory()->create();
    $generator = new SequenceGenerator;

    $g = fn (int $year) => $generator->next($tenant, DocumentType::Customer, $year);

    expect($g(2026))->toBe('K-2026-00001')
        ->and($g(2026))->toBe('K-2026-00002')
        ->and($g(2027))->toBe('K-2027-00001'); // new year, fresh counter
});

test('a unique constraint backs the application logic', function () {
    $tenant = Tenant::factory()->create();

    NumberSequence::create([
        'tenant_id' => $tenant->id,
        'document_type' => DocumentType::Customer->value,
        'year' => 2026,
        'last_number' => 5,
    ]);

    // A second row for the same (tenant, type, year) must be rejected by the
    // database, not merely by application logic.
    expect(fn () => NumberSequence::create([
        'tenant_id' => $tenant->id,
        'document_type' => DocumentType::Customer->value,
        'year' => 2026,
        'last_number' => 0,
    ]))->toThrow(QueryException::class);
});
