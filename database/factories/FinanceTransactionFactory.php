<?php

namespace Database\Factories;

use App\Models\FinanceAccount;
use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanceTransaction>
 */
class FinanceTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => FinanceAccount::factory(),
            'destination_account_id' => null,
            'category_id' => FinanceCategory::factory(),
            'client_id' => null,
            'type' => 'expense',
            'amount' => $this->faker->randomFloat(2, 5000, 100000),
            'converted_amount' => null,
            'exchange_rate' => 1.000000,
            'transaction_date' => $this->faker->date(),
            'description' => $this->faker->sentence(),
            'tags' => ['testing'],
        ];
    }
}
