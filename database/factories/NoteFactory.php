<?php

namespace Database\Factories;

use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
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
            'folder_id' => null,
            'title' => $this->faker->sentence(),
            'content' => '<p>'.$this->faker->paragraph().'</p>',
            'is_favorite' => false,
            'is_archived' => false,
        ];
    }
}
