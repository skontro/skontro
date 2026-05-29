<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A per-(tenant, document_type, year) counter. Intentionally does NOT use the
 * BelongsToTenant trait: SequenceGenerator manages tenant scoping explicitly
 * inside a row-locked transaction, and the trait's global scope + auto-stamp
 * would interfere with the locked firstOrCreate lookup. All access to this
 * model goes through SequenceGenerator — never query it directly from
 * application code.
 */
class NumberSequence extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'tenant_id',
        'document_type',
        'year',
        'last_number',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'year' => 'integer',
            'last_number' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
