<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();

            // Per-tenant document number (FR-013). Unique within a tenant, not
            // globally — two tenants may both have K-2026-00001.
            $table->string('number');

            $table->string('type'); // CustomerType
            $table->string('company_name')->nullable();
            $table->string('contact_name');

            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->string('street')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->string('country_code', 2)->nullable(); // Country enum

            $table->string('vat_id')->nullable();

            // FR-040 groundwork: per-customer default payment terms (days),
            // overriding the tenant default, itself overridable per invoice.
            $table->unsignedSmallInteger('payment_terms_days')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes(); // FR-019

            $table->unique(['tenant_id', 'number']);
            $table->index(['tenant_id', 'contact_name']);
            $table->index(['tenant_id', 'company_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
