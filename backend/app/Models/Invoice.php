<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceState;
use App\Models\Concerns\BelongsToTenant;
use App\Services\InvoiceCalculator;
use App\Support\InvoiceLineInput;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'customer_id',
        'number',
        'state',
        'invoice_date',
        'due_date',
        'payment_terms_days',
        'service_period_start',
        'service_period_end',
        'notes_top',
        'notes_bottom',
        'subtotal_cents',
        'total_vat_cents',
        'total_cents',
        'issued_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'state' => InvoiceState::class,
            'invoice_date' => 'date',
            'due_date' => 'date',
            'service_period_start' => 'date',
            'service_period_end' => 'date',
            'issued_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice): void {
            if (empty($invoice->uuid)) {
                $invoice->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<InvoiceLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('position');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Recompute and persist totals from the current lines using the line-level
     * VAT calculator (FR-031). Also writes each line's computed net and VAT.
     */
    public function recalculateTotals(): void
    {
        $lines = $this->lines()->get();

        /** @var list<InvoiceLineInput> $inputs */
        $inputs = $lines->map(fn (InvoiceLine $l): InvoiceLineInput => new InvoiceLineInput(
            quantity: (string) $l->quantity,
            unitPriceCents: (int) $l->unit_price_cents,
            vatRate: $l->vat_rate->value,
        ))->values()->all();

        $totals = (new InvoiceCalculator)->calculate($inputs);

        // Persist per-line computed amounts so they are stable and queryable.
        foreach ($lines->values() as $i => $line) {
            $input = $inputs[$i];
            $lineNet = BigDecimal::of($input->quantity)
                ->multipliedBy($input->unitPriceCents)
                ->toScale(0, RoundingMode::HALF_UP)
                ->toInt();
            $lineVat = BigDecimal::of($lineNet)
                ->multipliedBy($input->vatRate)
                ->dividedBy(100, 0, RoundingMode::HALF_UP)
                ->toInt();
            $line->update(['line_net_cents' => $lineNet, 'line_vat_cents' => $lineVat]);
        }

        $this->update([
            'subtotal_cents' => $totals->subtotalCents,
            'total_vat_cents' => $totals->totalVatCents,
            'total_cents' => $totals->totalCents,
        ]);
    }

    /**
     * Sum of recorded payments, in cents.
     */
    public function paidCents(): int
    {
        return (int) $this->payments()->sum('amount_cents');
    }

    /**
     * Resolve payment-terms precedence (FR-036, FR-040): an explicit
     * invoice-level value wins; else the customer's default; else the tenant
     * default (14). Used when creating a draft.
     */
    public static function resolvePaymentTerms(?int $invoiceTerms, Customer $customer, int $tenantDefault = 14): int
    {
        return $invoiceTerms
            ?? $customer->payment_terms_days
            ?? $tenantDefault;
    }
}
