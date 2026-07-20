<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskChecklist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskChecklist>
 */
class TaskChecklistFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'item_text' => $this->faker->sentence(),
            'is_completed' => $this->faker->boolean(),
            'sort_order' => 0,
        ];
    }
}
