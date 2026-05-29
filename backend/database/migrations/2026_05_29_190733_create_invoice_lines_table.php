<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            // Optional catalog reference; null for ad-hoc lines (FR-030).
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedInteger('position')->default(0); // ordering

            $table->string('description');
            // Quantity is a decimal (2.5 hours is valid). DECIMAL, not float.
            $table->decimal('quantity', 12, 3);
            $table->string('unit'); // Unit enum value
            $table->unsignedBigInteger('unit_price_cents');
            $table->unsignedTinyInteger('vat_rate'); // VatRate

            // Computed line amounts (cents), set by the calculator on save.
            $table->unsignedBigInteger('line_net_cents');
            $table->unsignedBigInteger('line_vat_cents');

            $table->timestamps();

            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
