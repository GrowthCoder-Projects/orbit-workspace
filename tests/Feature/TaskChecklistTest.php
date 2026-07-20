<?php

use App\Models\Task;
use App\Models\TaskChecklist;
use App\Models\User;

test('authenticated user can add checklist item to task', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $task = Task::factory()->create();

    $response = $this->post(route('tasks.checklists.store', $task), [
        'item_text' => 'New Checklist Subtask Item',
    ]);

    $response->assertRedirect(route('tasks.index'));
    $this->assertDatabaseHas('task_checklists', [
        'task_id' => $task->id,
        'item_text' => 'New Checklist Subtask Item',
        'is_completed' => false,
    ]);
});

test('authenticated user can update checklist item state', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $item = TaskChecklist::factory()->create([
        'is_completed' => false,
    ]);

    $response = $this->patch(route('tasks.checklists.update', $item), [
        'is_completed' => true,
    ]);

    $response->assertRedirect(route('tasks.index'));
    $this->assertDatabaseHas('task_checklists', [
        'id' => $item->id,
        'is_completed' => true,
    ]);
});

test('authenticated user can delete checklist item', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $item = TaskChecklist::factory()->create();

    $response = $this->delete(route('tasks.checklists.destroy', $item));

    $response->assertRedirect(route('tasks.index'));
    $this->assertDatabaseMissing('task_checklists', [
        'id' => $item->id,
    ]);
});
