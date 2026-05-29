<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Unit;
use App\Enums\VatRate;
use Database\Factories\InvoiceLineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An invoice line. Not directly tenant-scoped — it is always reached through
 * its parent Invoice (which is tenant-scoped), so isolation is inherited via
 * the aggregate. Lines are immutable once their invoice is issued (enforced in
 * the Invoice aggregate / controller, not here).
 *
 * @property Unit $unit
 * @property VatRate $vat_rate
 * @property int $unit_price_cents
 * @property int $line_net_cents
 * @property int $line_vat_cents
 */
class InvoiceLine extends Model
{
    /** @use HasFactory<InvoiceLineFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'invoice_id',
        'product_id',
        'position',
        'description',
        'quantity',
        'unit',
        'unit_price_cents',
        'vat_rate',
        'line_net_cents',
        'line_vat_cents',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit' => Unit::class,
            'vat_rate' => VatRate::class,
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
