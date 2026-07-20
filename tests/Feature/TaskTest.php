<?php

use App\Models\Task;
use App\Models\User;

test('guest cannot view tasks', function () {
    $response = $this->get(route('tasks.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated user can view tasks list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $task = Task::factory()->create();

    $response = $this->get(route('tasks.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Tasks')
        ->has('tasks')
    );
});

test('authenticated user can create a task', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->from(route('tasks.index'))->post(route('tasks.store'), [
        'title' => 'Test Task Title',
        'description' => 'Test task description content.',
        'status' => 'todo',
        'priority' => 'high',
        'due_date' => now()->addDays(5)->format('Y-m-d'),
        'tags' => ['tag1', 'tag2'],
    ]);

    $response->assertRedirect(route('tasks.index'));
    $this->assertDatabaseHas('tasks', [
        'title' => 'Test Task Title',
        'priority' => 'high',
    ]);
});

test('authenticated user can update task details', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $task = Task::factory()->create([
        'status' => 'todo',
    ]);

    $response = $this->from(route('tasks.index'))->patch(route('tasks.update', $task), [
        'title' => 'Updated Task Title',
        'status' => 'in_progress',
    ]);

    $response->assertRedirect(route('tasks.index'));
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Updated Task Title',
        'status' => 'in_progress',
    ]);
});

test('task done status automatically updates completed_at', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $task = Task::factory()->create([
        'status' => 'todo',
        'completed_at' => null,
    ]);

    $response = $this->from(route('tasks.index'))->patch(route('tasks.update', $task), [
        'status' => 'done',
    ]);

    $response->assertRedirect(route('tasks.index'));

    $task->refresh();
    expect($task->status)->toBe('done');
    expect($task->completed_at)->not->toBeNull();
});

test('authenticated user can delete task', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $task = Task::factory()->create();

    $response = $this->delete(route('tasks.destroy', $task));

    $response->assertRedirect(route('tasks.index'));
    $this->assertDatabaseMissing('tasks', [
        'id' => $task->id,
    ]);
});
