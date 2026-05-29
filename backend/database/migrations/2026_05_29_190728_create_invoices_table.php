<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();

            // Null while draft; minted on issue (R-YYYY-NNNNN).
            $table->string('number')->nullable();

            $table->string('state')->default('draft'); // InvoiceState

            $table->date('invoice_date');
            $table->date('due_date');
            $table->unsignedSmallInteger('payment_terms_days');

            $table->date('service_period_start')->nullable(); // FR-037
            $table->date('service_period_end')->nullable();

            $table->text('notes_top')->nullable();    // FR-038
            $table->text('notes_bottom')->nullable();

            // Stored totals (integer cents), recomputed from lines on save.
            $table->unsignedBigInteger('subtotal_cents')->default(0);
            $table->unsignedBigInteger('total_vat_cents')->default(0);
            $table->unsignedBigInteger('total_cents')->default(0);

            // Audit fields for state changes (FR-032, FR-033, FR-034).
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'number']); // number unique per tenant
            $table->index(['tenant_id', 'state']);
            $table->index(['tenant_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
