<?php

declare(strict_types=1);

use App\Jobs\GenerateInvoiceDocument;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function draftInvoiceFor(User $admin): Invoice
{
    app()->instance('currentTenant', $admin->tenant);
    app()->instance('currentTenantId', $admin->tenant->id);
    $customer = Customer::factory()->forTenant($admin->tenant)->create();
    $invoice = Invoice::factory()->forTenant($admin->tenant)->create(['customer_id' => $customer->id]);
    InvoiceLine::factory()->for($invoice)->create([
        'quantity' => '1', 'unit_price_cents' => 10000, 'vat_rate' => 19, 'position' => 1,
    ]);
    $invoice->recalculateTotals();

    return $invoice->fresh();
}

test('issuing a draft mints a number, locks it, and dispatches the document job', function () {
    Queue::fake();
    $admin = User::factory()->admin()->create();
    $invoice = draftInvoiceFor($admin);

    actingAs($admin)->postJson("/api/v1/invoices/{$invoice->uuid}/issue")
        ->assertOk()
        ->assertJsonPath('data.state', 'issued');

    $fresh = $invoice->fresh();
    expect($fresh->number)->toMatch('/^R-\d{4}-\d{5}$/')
        ->and($fresh->issued_at)->not->toBeNull();

    Queue::assertPushed(GenerateInvoiceDocument::class);
});

test('an issued invoice cannot be edited', function () {
    $admin = User::factory()->admin()->create();
    $invoice = draftInvoiceFor($admin);
    actingAs($admin)->postJson("/api/v1/invoices/{$invoice->uuid}/issue")->assertOk();

    actingAs($admin)->patchJson("/api/v1/invoices/{$invoice->uuid}", [
        'notes_top' => 'too late',
    ])->assertStatus(409);
});

test('you cannot send a draft (must issue first) — 409', function () {
    $admin = User::factory()->admin()->create();
    $invoice = draftInvoiceFor($admin);

    actingAs($admin)->postJson("/api/v1/invoices/{$invoice->uuid}/send")
        ->assertStatus(409)
        ->assertJsonPath('current_state', 'draft')
        ->assertJsonPath('attempted_state', 'sent');
});

test('recording full payment transitions to paid', function () {
    $admin = User::factory()->admin()->create();
    $invoice = draftInvoiceFor($admin); // total 11900
    actingAs($admin)->postJson("/api/v1/invoices/{$invoice->uuid}/issue")->assertOk();

    actingAs($admin)->postJson("/api/v1/invoices/{$invoice->uuid}/payments", [
        'amount_cents' => 11900,
        'payment_date' => now()->toDateString(),
        'method' => 'bank_transfer',
    ])->assertOk()->assertJsonPath('data.state', 'paid');
});

test('recording partial payment transitions to partially_paid', function () {
    $admin = User::factory()->admin()->create();
    $invoice = draftInvoiceFor($admin); // total 11900
    actingAs($admin)->postJson("/api/v1/invoices/{$invoice->uuid}/issue")->assertOk();

    actingAs($admin)->postJson("/api/v1/invoices/{$invoice->uuid}/payments", [
        'amount_cents' => 5000,
        'payment_date' => now()->toDateString(),
        'method' => 'cash',
    ])->assertOk()->assertJsonPath('data.state', 'partially_paid');
});

test('cancelling requires a reason and cannot cancel a paid invoice', function () {
    $admin = User::factory()->admin()->create();
    $invoice = draftInvoiceFor($admin);
    actingAs($admin)->postJson("/api/v1/invoices/{$invoice->uuid}/issue")->assertOk();

    // No reason -> 422
    actingAs($admin)->postJson("/api/v1/invoices/{$invoice->uuid}/cancel", [])
        ->assertStatus(422)->assertJsonValidationErrors('reason');

    // With reason -> cancelled
    actingAs($admin)->postJson("/api/v1/invoices/{$invoice->uuid}/cancel", ['reason' => 'Duplicate'])
        ->assertOk()->assertJsonPath('data.state', 'cancelled');

    // Pay a different invoice fully then try to cancel -> 409
    $paid = draftInvoiceFor($admin);
    actingAs($admin)->postJson("/api/v1/invoices/{$paid->uuid}/issue")->assertOk();
    actingAs($admin)->postJson("/api/v1/invoices/{$paid->uuid}/payments", [
        'amount_cents' => 11900, 'payment_date' => now()->toDateString(), 'method' => 'cash',
    ])->assertOk();
    actingAs($admin)->postJson("/api/v1/invoices/{$paid->uuid}/cancel", ['reason' => 'too late'])
        ->assertStatus(409);
});
