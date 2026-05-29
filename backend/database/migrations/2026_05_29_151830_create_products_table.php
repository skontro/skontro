<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku')->nullable(); // FR-028, optional, not unique in v0.1

            // FR-026: money is integer cents in a BIGINT column. Never FLOAT.
            $table->unsignedBigInteger('unit_price_cents');

            $table->unsignedTinyInteger('vat_rate'); // VatRate enum: 19 / 7 / 0
            $table->string('unit')->default('Stück'); // Unit enum

            // FR-027: archived products stay readable but drop out of pickers.
            // A status flag, NOT soft delete — the row is never "deleted".
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
