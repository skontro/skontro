<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Dispatched when an invoice is issued (FR-033). Generates the ZUGFeRD 2.1
 * PDF/A-3 document for the invoice.
 *
 * NOTE: document generation itself (FR-041–FR-048) is a separate milestone.
 * For now this job is a stub that records that it ran, so the issue flow and
 * its dispatch are complete and tested without the generator existing yet.
 * When the e-invoicing milestone lands, the generation logic goes here.
 */
class GenerateInvoiceDocument implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Invoice $invoice) {}

    public function handle(): void
    {
        // Placeholder for ZUGFeRD/PDF generation (e-invoicing milestone).
        Log::info('Invoice document generation queued', [
            'invoice_id' => $this->invoice->id,
            'number' => $this->invoice->number,
        ]);
    }
}
