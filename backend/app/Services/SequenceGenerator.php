<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DocumentType;
use App\Models\NumberSequence;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * Issues atomic, gapless, per-tenant, annually-resetting document numbers
 * (FR-013). Correctness under concurrency comes from a SELECT ... FOR UPDATE
 * row lock: concurrent callers for the same (tenant, type, year) serialize on
 * the single counter row, so each receives a distinct, consecutive value.
 *
 * Format: PREFIX-YYYY-NNNNN, e.g. K-2026-00001. NNNNN is zero-padded to five
 * digits and grows beyond five only if a tenant exceeds 99,999 documents of
 * one type in a year (acceptable — the format stays parseable).
 */
class SequenceGenerator
{
    public function next(Tenant $tenant, DocumentType $type, ?int $year = null): string
    {
        $year ??= (int) now()->format('Y');

        $number = DB::transaction(function () use ($tenant, $type, $year): int {
            // lockForUpdate() emits SELECT ... FOR UPDATE, locking the counter
            // row for the duration of the transaction. A concurrent caller
            // blocks here until this transaction commits.
            $sequence = NumberSequence::query()
                ->where('tenant_id', $tenant->id)
                ->where('document_type', $type->value)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                // First document of this type/year for this tenant. The unique
                // index guarantees that even under a race only one row is
                // created; the loser would retry and find it on the next pass.
                $sequence = NumberSequence::create([
                    'tenant_id' => $tenant->id,
                    'document_type' => $type->value,
                    'year' => $year,
                    'last_number' => 0,
                ]);
            }

            $sequence->last_number++;
            $sequence->save();

            return $sequence->last_number;
        });

        return sprintf('%s-%d-%05d', $type->prefix(), $year, $number);
    }
}
