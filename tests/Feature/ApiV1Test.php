<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('unauthenticated api requests are rejected', function () {
    $response = $this->getJson('/api/v1/notes');

    $response->assertStatus(401);
});

test('authenticated user can list and create notes via API', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/notes', [
        'title' => 'API Note Test',
        'content' => 'Created via Sanctum API Token',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'title' => 'API Note Test',
                'content' => 'Created via Sanctum API Token',
            ],
        ]);

    $this->getJson('/api/v1/notes')
        ->assertStatus(200)
        ->assertJsonPath('data.data.0.title', 'API Note Test');
});

test('authenticated user can create tasks via API', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/tasks', [
        'title' => 'Buy groceries',
        'priority' => 'high',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'title' => 'Buy groceries',
                'priority' => 'high',
            ],
        ]);
});

test('authenticated user can toggle habit completion via API', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $habitResponse = $this->postJson('/api/v1/habits', [
        'name' => 'Morning Jogging',
    ]);

    $habitId = $habitResponse->json('data.id');

    $toggleResponse = $this->postJson("/api/v1/habits/{$habitId}/toggle", [
        'date' => now()->format('Y-m-d'),
    ]);

    $toggleResponse->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'habit_id' => $habitId,
                'completed' => true,
            ],
        ]);
});
