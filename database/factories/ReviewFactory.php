<?php

namespace Database\Factories;

use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'detail' => fake()->randomElement(['', 'Trader, Idumota', 'Business owner, Lagos']),
            'rating' => fake()->numberBetween(4, 5),
            'body' => fake()->sentence(16),
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that the review is hidden from the website.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}
