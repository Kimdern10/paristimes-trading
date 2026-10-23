<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question' => rtrim(fake()->sentence(6), '.').'?',
            'answer' => fake()->paragraph(),
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that the question is hidden from the website.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}
