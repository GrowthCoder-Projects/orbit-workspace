<?php

namespace Database\Factories;

use App\Models\Bookmark;
use App\Models\BookmarkCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bookmark>
 */
class BookmarkFactory extends Factory
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
            'category_id' => BookmarkCategory::factory(),
            'url' => $this->faker->url(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'favicon_url' => 'https://example.com/favicon.ico',
            'preview_image_url' => 'https://example.com/preview.jpg',
            'is_favorite' => $this->faker->boolean(),
            'status' => 'success',
        ];
    }
}
