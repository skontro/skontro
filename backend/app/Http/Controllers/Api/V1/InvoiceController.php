<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\InvoiceState;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoices\StoreInvoiceRequest;
use App\Http\Requests\Invoices\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->integer('per_page', 25), 100);

        $query = Invoice::query()->with('customer');

        if ($state = $request->string('state')->toString()) {
            $query->where('state', $state);
        }

        $query->orderByDesc('created_at');

        return InvoiceResource::collection($query->paginate($perPage));
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        // Resolve the customer within the tenant scope; a foreign uuid 404s.
        $customer = Customer::where('uuid', $request->string('customer_id'))->firstOrFail();

        $invoice = DB::transaction(function () use ($request, $customer): Invoice {
            $invoiceDate = $request->date('invoice_date') ?? now();
            $terms = Invoice::resolvePaymentTerms(
                $request->filled('payment_terms_days') ? (int) $request->integer('payment_terms_days') : null,
                $customer,
            );

            $invoice = Invoice::create([
                'customer_id' => $customer->id,
                'state' => InvoiceState::Draft->value,
                'invoice_date' => $invoiceDate->toDateString(),
                'due_date' => $invoiceDate->copy()->addDays($terms)->toDateString(),
                'payment_terms_days' => $terms,
                'service_period_start' => $request->date('service_period_start'),
                'service_period_end' => $request->date('service_period_end'),
                'notes_top' => $request->input('notes_top'),
                'notes_bottom' => $request->input('notes_bottom'),
            ]);

            /** @var array<int, array<string, mixed>> $lines */
            $lines = $request->array('lines');
            $this->syncLines($invoice, $lines);
            $invoice->recalculateTotals();

            return $invoice;
        });

        return InvoiceResource::make($invoice->load(['customer', 'lines']))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        return InvoiceResource::make($invoice->load(['customer', 'lines', 'payments']));
    }

    /**
     * Update a draft. Only drafts are editable (FR-032); any other state 409s.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice): InvoiceResource
    {
        abort_unless(
            $invoice->state->allowsLineEditing(),
            Response::HTTP_CONFLICT,
            'Only a draft invoice can be edited.'
        );

        DB::transaction(function () use ($request, $invoice): void {
            $data = collect($request->validated())->except('lines', 'customer_id')->all();
            if ($request->filled('customer_id')) {
                $customer = Customer::where('uuid', $request->string('customer_id'))->firstOrFail();
                $data['customer_id'] = $customer->id;
            }
            $invoice->update($data);

            if ($request->has('lines')) {
                $invoice->lines()->delete();
                /** @var array<int, array<string, mixed>> $lines */
                $lines = $request->array('lines');
                $this->syncLines($invoice, $lines);
            }

            $invoice->recalculateTotals();
        });

        return InvoiceResource::make($invoice->refresh()->load(['customer', 'lines']));
    }

    /**
     * Replace the invoice's lines from the request payload. Resolves an
     * optional product reference within the tenant scope.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function syncLines(Invoice $invoice, array $lines): void
    {
        foreach (array_values($lines) as $i => $line) {
            $productId = null;
            if (! empty($line['product_id'])) {
                $product = Product::where('uuid', $line['product_id'])->first();
                $productId = $product?->id;
            }

            $invoice->lines()->create([
                'product_id' => $productId,
                'position' => $i + 1,
                'description' => $line['description'],
                'quantity' => (string) $line['quantity'],
                'unit' => $line['unit'],
                'unit_price_cents' => (int) $line['unit_price_cents'],
                'vat_rate' => (int) $line['vat_rate'],
                'line_net_cents' => 0,
                'line_vat_cents' => 0,
            ]);
        }
    }
}
