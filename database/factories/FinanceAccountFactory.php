<?php

namespace Database\Factories;

use App\Models\FinanceAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanceAccount>
 */
class FinanceAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word() . ' Bank',
            'type' => $this->faker->randomElement(['cash', 'bank', 'e-wallet', 'credit_card', 'investment']),
            'balance' => $this->faker->randomFloat(2, 1000000, 100000000),
            'currency' => $this->faker->randomElement(['IDR', 'USD']),
            'color' => $this->faker->safeHexColor(),
            'notes' => $this->faker->sentence(),
        ];
    }
}
