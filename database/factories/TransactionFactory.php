<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->numberBetween(1, 50) * 100;
        $rate = fake()->numberBetween(1400, 2000);

        return [
            'traded_on' => fake()->dateTimeBetween('-60 days', 'now')->format('Y-m-d'),
            'type' => fake()->randomElement(TransactionType::cases()),
            'currency_code' => fake()->randomElement(['USD', 'GBP', 'EUR']),
            'amount' => $amount,
            'rate' => $rate,
            'naira_total' => Transaction::nairaTotal($amount, $rate),
            'customer_name' => fake()->name(),
            'note' => '',
            'receipt_path' => null,
            'is_public' => true,
        ];
    }

    /**
     * Indicate that the deal is kept off the website.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }
}
