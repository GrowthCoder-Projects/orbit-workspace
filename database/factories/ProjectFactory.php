<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->randomElement([
            'Workspace OS',
            'GrowthCoder Platform',
            'Client Portal API',
            'E-Commerce Backend',
            'Analytics Dashboard',
            'Mobile App API',
            'CRM Integration',
            'DevOps Tooling',
        ]);

        $colors = ['#5C59D9', '#2BB673', '#2D2A6F', '#E07B54', '#9B59B6', '#E74C3C', '#3498DB'];

        return [
            'client_id' => null,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'description' => $this->faker->paragraph(),
            'color' => $this->faker->randomElement($colors),
            'repository_url' => 'https://github.com/growthcoder/'.$this->faker->slug(),
            'production_url' => 'https://'.$this->faker->domainName(),
            'staging_url' => 'https://staging.'.$this->faker->domainName(),
            'server_ip' => $this->faker->ipv4(),
            'status' => $this->faker->randomElement(['active', 'pipeline', 'archived']),
        ];
    }

    /**
     * Indicate the project is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate the project is in pipeline.
     */
    public function pipeline(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pipeline',
        ]);
    }

    /**
     * Indicate the project is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }
}
