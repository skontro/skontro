<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\DocumentType;
use App\Enums\InvoiceState;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoices\CancelInvoiceRequest;
use App\Http\Requests\Invoices\RecordPaymentRequest;
use App\Http\Resources\InvoiceResource;
use App\Jobs\GenerateInvoiceDocument;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Services\InvoiceStateMachine;
use App\Services\SequenceGenerator;
use Illuminate\Support\Facades\DB;

class InvoiceActionController extends Controller
{
    public function __construct(
        private readonly InvoiceStateMachine $stateMachine,
        private readonly SequenceGenerator $sequences,
    ) {}

    /**
     * Issue a draft (FR-033): mint the number, lock the lines, set issued_at,
     * dispatch the (stubbed) document generation job.
     */
    public function issue(Invoice $invoice): InvoiceResource
    {
        $this->stateMachine->assertCanTransition($invoice->state, InvoiceState::Issued);

        DB::transaction(function () use ($invoice): void {
            /** @var Tenant $tenant */
            $tenant = app('currentTenant');

            $invoice->update([
                'number' => $this->sequences->next($tenant, DocumentType::Invoice),
                'state' => InvoiceState::Issued->value,
                'issued_at' => now(),
            ]);
        });

        $invoice->refresh();

        // Dispatch document generation (stub for now; real ZUGFeRD later).
        GenerateInvoiceDocument::dispatch($invoice);

        return InvoiceResource::make($invoice->load(['customer', 'lines']));
    }

    public function send(Invoice $invoice): InvoiceResource
    {
        $this->stateMachine->assertCanTransition($invoice->state, InvoiceState::Sent);
        $invoice->update(['state' => InvoiceState::Sent->value]);

        return InvoiceResource::make($invoice->load(['customer', 'lines']));
    }

    /**
     * Record a payment (FR-035). Drives the state to partially_paid or paid
     * based on cumulative payments versus the invoice total.
     */
    public function recordPayment(RecordPaymentRequest $request, Invoice $invoice): InvoiceResource
    {
        DB::transaction(function () use ($request, $invoice): void {
            $invoice->payments()->create([
                'amount_cents' => (int) $request->integer('amount_cents'),
                'payment_date' => $request->date('payment_date'),
                'method' => $request->string('method')->toString(),
                'reference' => $request->input('reference'),
            ]);

            $paid = $invoice->paidCents();
            $target = $paid >= (int) $invoice->total_cents
                ? InvoiceState::Paid
                : InvoiceState::PartiallyPaid;

            // Only transition if legal from the current state (e.g. issued or
            // sent or partially_paid). The state machine guards it.
            if ($this->stateMachine->canTransition($invoice->state, $target)) {
                $invoice->update(['state' => $target->value]);
            }
        });

        return InvoiceResource::make($invoice->load(['customer', 'lines', 'payments']));
    }

    /**
     * Cancel (FR-034): requires a reason; cannot cancel a paid invoice (the
     * state machine forbids paid -> cancelled, yielding 409).
     */
    public function cancel(CancelInvoiceRequest $request, Invoice $invoice): InvoiceResource
    {
        $this->stateMachine->assertCanTransition($invoice->state, InvoiceState::Cancelled);

        $invoice->update([
            'state' => InvoiceState::Cancelled->value,
            'cancelled_at' => now(),
            'cancellation_reason' => $request->string('reason')->toString(),
        ]);

        return InvoiceResource::make($invoice->load(['customer', 'lines']));
    }
}
