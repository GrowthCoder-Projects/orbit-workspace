<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issueDate = $this->faker->dateTimeBetween('-6 months', 'now');
        $dueDate = (clone $issueDate)->modify('+30 days');

        return [
            'user_id' => User::factory(),
            'client_id' => Client::factory(),
            'project_id' => null,
            'invoice_number' => $this->faker->unique()->numerify('INV-2026-####'),
            'brand_prefix' => null,
            'status' => $this->faker->randomElement(['draft', 'sent', 'overdue', 'paid']),
            'issue_date' => $issueDate->format('Y-m-d'),
            'due_date' => $dueDate->format('Y-m-d'),
            'currency' => $this->faker->randomElement(['IDR', 'USD']),
            'subtotal' => 0.00,
            'tax_rate' => $this->faker->randomElement([0, 10, 11]),
            'discount_amount' => 0.00,
            'discount_type' => 'fixed',
            'total' => 0.00,
            'template_name' => 'modern',
            'color_accent' => '#3b82f6',
            'font_family' => 'Helvetica',
            'spacing' => 'cozy',
            'notes' => $this->faker->sentence(),
            'paid_at' => null,
            'finance_account_id' => null,
        ];
    }
}
