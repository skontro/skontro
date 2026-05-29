<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Unit;
use App\Enums\VatRate;
use App\Models\Concerns\BelongsToTenant;
use Brick\Money\Money;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int $unit_price_cents
 * @property Money $price
 * @property VatRate $vat_rate
 * @property Unit $unit
 * @property bool $is_active
 */
class Product extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'description',
        'sku',
        'unit_price_cents',
        'vat_rate',
        'unit',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vat_rate' => VatRate::class,
            'unit' => Unit::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Expose the money column under a clean attribute name while keeping the
     * database column explicitly "_cents". $product->price returns a Money;
     * setting $product->price = Money|int writes cents to unit_price_cents.
     *
     * @return Attribute<Money, array{unit_price_cents: int}>
     */
    public function price(): Attribute
    {
        return Attribute::make(
            get: fn (): Money => Money::ofMinor((int) $this->unit_price_cents, 'EUR'),
            set: fn (mixed $value): array => [
                'unit_price_cents' => $value instanceof Money
                    ? $value->getMinorAmount()->toInt()
                    : (int) $value,
            ],
        );
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            if (empty($product->uuid)) {
                $product->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Only active (non-archived) products. Used by the invoice product picker.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Case-insensitive search across name and SKU (FR-020-style).
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || $term === '') {
            return $query;
        }

        $like = '%'.str_replace('%', '\%', $term).'%';

        return $query->where(function (Builder $q) use ($like): void {
            $q->where('name', 'ilike', $like)->orWhere('sku', 'ilike', $like);
        });
    }
}
