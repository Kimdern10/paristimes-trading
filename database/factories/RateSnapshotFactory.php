<?php

namespace Database\Factories;

use App\Models\RateSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RateSnapshot>
 */
class RateSnapshotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $buy = fake()->numberBetween(1400, 1500);

        return [
            'currency_code' => 'USD',
            'recorded_on' => now()->toDateString(),
            'buy_rate' => $buy,
            'sell_rate' => $buy + fake()->numberBetween(10, 30),
        ];
    }
}
