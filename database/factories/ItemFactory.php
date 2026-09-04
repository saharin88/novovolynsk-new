<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraphs(3, true),
            'contacts' => [
                [
                    'name' => fake()->firstName(),
                    'phone' => fake()->e164PhoneNumber(),
                ],
            ],
            'price' => fake()->optional()->numberBetween(100, 500000),
            'currency' => fake()->randomElement(['UAH', 'USD', 'EUR']),
            'views' => fake()->numberBetween(0, 1000),
            'phone_views' => fake()->numberBetween(0, 250),
            'email_views' => fake()->numberBetween(0, 250),
            'archived_at' => fake()->boolean(20) ? fake()->dateTimeBetween('-2 months', 'now') : null,
        ];
    }
}
