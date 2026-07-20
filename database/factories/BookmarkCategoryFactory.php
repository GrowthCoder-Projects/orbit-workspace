<?php

namespace Database\Factories;

use App\Models\BookmarkCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookmarkCategory>
 */
class BookmarkCategoryFactory extends Factory
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
            'name' => $this->faker->words(2, true),
            'color' => $this->faker->hexColor(),
            'icon' => 'Bookmark',
        ];
    }
}
