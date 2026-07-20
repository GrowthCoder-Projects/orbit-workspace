<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $qty = $this->faker->randomFloat(2, 1, 10);
        $price = $this->faker->randomFloat(2, 100000, 5000000);

        return [
            'invoice_id' => Invoice::factory(),
            'description' => $this->faker->words(3, true),
            'quantity' => $qty,
            'unit_price' => $price,
            'tax_rate' => 0.00,
            'discount_amount' => 0.00,
            'total' => 0.00, // Calculated automatically by saved/saving booted callbacks
        ];
    }
}
