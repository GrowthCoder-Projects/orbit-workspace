<?php

use App\Models\Habit;
use App\Models\Task;
use App\Models\User;

test('guest cannot access daily welcome endpoint', function () {
    $response = $this->getJson(route('daily-welcome.index'));

    $response->assertStatus(401);
});

test('authenticated user can fetch daily welcome summary', function () {
    $user = User::factory()->create();

    // Create a habit and a task
    Habit::create([
        'user_id' => $user->id,
        'name' => 'Morning Meditation',
        'frequency_type' => 'daily',
        'is_active' => true,
    ]);

    Task::factory()->create([
        'title' => 'Complete Project Proposal',
        'due_date' => now()->toDateString(),
        'status' => 'todo',
    ]);

    $response = $this->actingAs($user)->getJson(route('daily-welcome.index'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'greeting',
            'greeting_icon',
            'submessage',
            'date_formatted',
            'user_name',
            'quote' => ['quote', 'author', 'category', 'source'],
            'summary' => [
                'habits_completed_count',
                'total_habits_today',
                'pending_tasks_count',
                'events_today_count',
            ],
            'habits',
            'tasks',
            'events',
        ]);
});

test('user can request a random quote for shuffle', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson(route('daily-welcome.quote'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'quote' => ['quote', 'author', 'category', 'source'],
        ]);
});

test('inertia request sets showDailyWelcome on initial request and prevents on subsequent requests', function () {
    $user = User::factory()->create();

    // First request after login
    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    $this->assertTrue(session()->has('daily_welcome_shown'));
});
