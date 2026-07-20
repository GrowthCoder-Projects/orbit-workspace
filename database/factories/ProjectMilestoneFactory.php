<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectMilestone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectMilestone>
 */
class ProjectMilestoneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'completed']);

        return [
            'project_id' => Project::factory(),
            'title' => $this->faker->randomElement([
                'Phase 1 — Requirements & Design',
                'Phase 2 — Core Backend API',
                'Phase 3 — Frontend MVP',
                'Phase 4 — QA & Testing',
                'Beta Launch',
                'Production Deployment',
                'Client Acceptance Testing',
                'Post-Launch Monitoring',
                'Performance Optimization',
            ]),
            'description' => $this->faker->optional()->sentence(),
            'due_date' => $this->faker->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'completed_at' => $status === 'completed' ? now() : null,
            'status' => $status,
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }

    /**
     * Indicate the milestone is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => now(),
            'due_date' => now()->subDays(rand(1, 30))->format('Y-m-d'),
        ]);
    }

    /**
     * Indicate the milestone is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'completed_at' => null,
            'due_date' => now()->addDays(rand(1, 60))->format('Y-m-d'),
        ]);
    }
}
