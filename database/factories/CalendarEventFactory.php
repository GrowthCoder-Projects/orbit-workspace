<?php

namespace Database\Factories;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarEvent>
 */
class CalendarEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->addDays(rand(-7, 14))->setHour(rand(8, 17))->setMinute(0)->setSecond(0);
        $end = $start->copy()->addHours(rand(1, 4));

        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'start_at' => $start,
            'end_at' => $end,
            'is_all_day' => false,
            'color' => $this->faker->randomElement(['#2563eb', '#16a34a', '#7c3aed', '#ea580c', '#e11d48']),
            'recurrence_pattern' => 'none',
            'recurrence_end' => null,
            'reminder_lead_time' => null,
            'reminder_sent_at' => null,
        ];
    }
}
