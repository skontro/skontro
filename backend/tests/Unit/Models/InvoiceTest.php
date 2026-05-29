<?php

declare(strict_types=1);

use App\Enums\InvoiceState;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    clearTenantContext();
});

test('recalculateTotals computes line-level totals and stores them', function () {
    $tenant = Tenant::factory()->create();
    actAsTenant($tenant);
    $customer = Customer::factory()->forTenant($tenant)->create();

    $invoice = Invoice::factory()->forTenant($tenant)->create(['customer_id' => $customer->id]);
    InvoiceLine::factory()->for($invoice)->create([
        'quantity' => '2', 'unit_price_cents' => 10000, 'vat_rate' => 19, 'position' => 1,
    ]);
    InvoiceLine::factory()->for($invoice)->create([
        'quantity' => '1', 'unit_price_cents' => 5000, 'vat_rate' => 7, 'position' => 2,
    ]);

    $invoice->recalculateTotals();
    $invoice->refresh();

    // 19% line: net 20000, vat 3800. 7% line: net 5000, vat 350.
    expect($invoice->subtotal_cents)->toBe(25000)
        ->and($invoice->total_vat_cents)->toBe(4150)
        ->and($invoice->total_cents)->toBe(29150);

    // Per-line computed amounts persisted.
    $first = $invoice->lines()->where('position', 1)->first();
    expect($first->line_net_cents)->toBe(20000)->and($first->line_vat_cents)->toBe(3800);
});

test('paidCents sums payments', function () {
    $tenant = Tenant::factory()->create();
    actAsTenant($tenant);
    $invoice = Invoice::factory()->forTenant($tenant)->create([
        'customer_id' => Customer::factory()->forTenant($tenant)->create()->id,
    ]);

    Payment::factory()->forTenant($tenant)->create(['invoice_id' => $invoice->id, 'amount_cents' => 3000]);
    Payment::factory()->forTenant($tenant)->create(['invoice_id' => $invoice->id, 'amount_cents' => 2000]);

    expect($invoice->paidCents())->toBe(5000);
});

test('payment terms precedence: invoice over customer over tenant default', function () {
    $tenant = Tenant::factory()->create();
    $customerWithTerms = Customer::factory()->forTenant($tenant)->create(['payment_terms_days' => 30]);
    $customerNoTerms = Customer::factory()->forTenant($tenant)->create(['payment_terms_days' => null]);

    // invoice-specific wins
    expect(Invoice::resolvePaymentTerms(7, $customerWithTerms))->toBe(7)
        // customer default when no invoice value
        ->and(Invoice::resolvePaymentTerms(null, $customerWithTerms))->toBe(30)
        // tenant default when neither
        ->and(Invoice::resolvePaymentTerms(null, $customerNoTerms))->toBe(14);
});

test('invoices are tenant-scoped — no cross-tenant leakage', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    Invoice::factory()->forTenant($tenantA)->count(2)->create([
        'customer_id' => Customer::factory()->forTenant($tenantA)->create()->id,
    ]);
    Invoice::factory()->forTenant($tenantB)->count(3)->create([
        'customer_id' => Customer::factory()->forTenant($tenantB)->create()->id,
    ]);

    actAsTenant($tenantA);
    expect(Invoice::count())->toBe(2);

    actAsTenant($tenantB);
    expect(Invoice::count())->toBe(3);
});

test('a draft has no number; an issued invoice does', function () {
    $tenant = Tenant::factory()->create();
    actAsTenant($tenant);

    $draft = Invoice::factory()->forTenant($tenant)->create([
        'customer_id' => Customer::factory()->forTenant($tenant)->create()->id,
    ]);
    expect($draft->number)->toBeNull()
        ->and($draft->state)->toBe(InvoiceState::Draft);

    $issued = Invoice::factory()->forTenant($tenant)->issued()->create([
        'customer_id' => Customer::factory()->forTenant($tenant)->create()->id,
    ]);
    expect($issued->number)->not->toBeNull()
        ->and($issued->state)->toBe(InvoiceState::Issued);
});

test('quantity is stored as a decimal, not a float', function () {
    $tenant = Tenant::factory()->create();
    actAsTenant($tenant);
    $invoice = Invoice::factory()->forTenant($tenant)->create([
        'customer_id' => Customer::factory()->forTenant($tenant)->create()->id,
    ]);
    InvoiceLine::factory()->for($invoice)->create(['quantity' => '2.5']);

    $type = DB::selectOne(
        "select data_type from information_schema.columns
         where table_name = 'invoice_lines' and column_name = 'quantity'"
    );
    expect($type->data_type)->toBe('numeric'); // Postgres DECIMAL, never float
});
