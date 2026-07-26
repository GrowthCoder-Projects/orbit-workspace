<?php

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Task;
use App\Models\User;

test('authenticated user can add milestone to project', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $project = Project::factory()->create();

    $response = $this->post(route('projects.milestones.store', $project), [
        'title' => 'Phase 1 — Core MVP',
        'due_date' => now()->addDays(14)->format('Y-m-d'),
    ]);

    $response->assertRedirect(route('projects.show', $project));
    $this->assertDatabaseHas('project_milestones', [
        'project_id' => $project->id,
        'title' => 'Phase 1 — Core MVP',
        'status' => 'pending',
    ]);
});

test('milestone requires title and due date', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $project = Project::factory()->create();

    $response = $this->post(route('projects.milestones.store', $project), [
        'title' => '',
        'due_date' => '',
    ]);

    $response->assertSessionHasErrors(['title', 'due_date']);
});

test('authenticated user can toggle milestone to completed', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $milestone = ProjectMilestone::factory()->pending()->create();

    $response = $this->patch(route('projects.milestones.update', $milestone), [
        'status' => 'completed',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('project_milestones', [
        'id' => $milestone->id,
        'status' => 'completed',
    ]);

    $milestone->refresh();
    expect($milestone->completed_at)->not->toBeNull();
});

test('toggling milestone back to pending clears completed_at', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $milestone = ProjectMilestone::factory()->completed()->create();

    $this->patch(route('projects.milestones.update', $milestone), [
        'status' => 'pending',
    ]);

    $milestone->refresh();
    expect($milestone->status)->toBe('pending');
    expect($milestone->completed_at)->toBeNull();
});

test('authenticated user can delete milestone', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $milestone = ProjectMilestone::factory()->create();

    $response = $this->delete(route('projects.milestones.destroy', $milestone));

    $response->assertRedirect();
    $this->assertDatabaseMissing('project_milestones', ['id' => $milestone->id]);
});

test('milestones are cascade deleted when project is deleted', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $project = Project::factory()->create();
    $milestone = ProjectMilestone::factory()->create(['project_id' => $project->id]);

    $this->delete(route('projects.destroy', $project));

    $this->assertDatabaseMissing('project_milestones', ['id' => $milestone->id]);
});

test('tasks project_milestone_id is set to null when milestone is deleted', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $project = Project::factory()->create();
    $milestone = ProjectMilestone::factory()->create(['project_id' => $project->id]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'project_milestone_id' => $milestone->id,
    ]);

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'project_milestone_id' => $milestone->id,
    ]);

    $this->delete(route('projects.milestones.destroy', $milestone));

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'project_milestone_id' => null,
    ]);
});
