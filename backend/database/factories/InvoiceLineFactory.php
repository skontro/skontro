<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Unit;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceLine>
 */
class InvoiceLineFactory extends Factory
{
    protected $model = InvoiceLine::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $qty = $this->faker->randomElement(['1', '2', '2.5', '3']);
        $price = $this->faker->numberBetween(500, 20000);
        $rate = $this->faker->randomElement([19, 7, 0]);

        return [
            'invoice_id' => Invoice::factory(),
            'product_id' => null,
            'position' => 1,
            'description' => $this->faker->sentence(3),
            'quantity' => $qty,
            'unit' => Unit::Piece->value,
            'unit_price_cents' => $price,
            'vat_rate' => $rate,
            'line_net_cents' => 0, // recalculated by the aggregate
            'line_vat_cents' => 0,
        ];
    }
}
