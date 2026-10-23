<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $buy = fake()->numberBetween(1000, 2000);

        return [
            'code' => fake()->unique()->currencyCode(),
            'name' => fake()->words(2, true),
            'symbol' => '',
            'buy_rate' => $buy,
            'sell_rate' => $buy + fake()->numberBetween(5, 40),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that the owner has not entered rates yet.
     */
    public function withoutRates(): static
    {
        return $this->state(fn (array $attributes) => [
            'buy_rate' => null,
            'sell_rate' => null,
        ]);
    }

    /**
     * Indicate that the currency is hidden from the website.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
