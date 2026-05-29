<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Country;
use App\Enums\CustomerType;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property CustomerType $type
 * @property ?Country $country_code
 */
class Customer extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'number',
        'type',
        'company_name',
        'contact_name',
        'email',
        'phone',
        'street',
        'postal_code',
        'city',
        'country_code',
        'vat_id',
        'payment_terms_days',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CustomerType::class,
            'country_code' => Country::class,
            'payment_terms_days' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Customer $customer): void {
            if (empty($customer->uuid)) {
                $customer->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Case-insensitive substring search across name, number, email, and VAT ID
     * (FR-020). The query is already tenant-scoped by the global scope, so this
     * only narrows within the current tenant.
     *
     * @param  Builder<Customer>  $query
     * @return Builder<Customer>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || $term === '') {
            return $query;
        }

        $like = '%'.str_replace('%', '\%', $term).'%';

        return $query->where(function (Builder $q) use ($like): void {
            $q->where('contact_name', 'ilike', $like)
                ->orWhere('company_name', 'ilike', $like)
                ->orWhere('number', 'ilike', $like)
                ->orWhere('email', 'ilike', $like)
                ->orWhere('vat_id', 'ilike', $like);
        });
    }
}
