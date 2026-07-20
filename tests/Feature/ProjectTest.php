<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

test('guest cannot view projects', function () {
    $response = $this->get(route('projects.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated user can view project list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Project::factory()->create();

    $response = $this->get(route('projects.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Projects/Index')
        ->has('projects')
    );
});

test('authenticated user can view create project form', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('projects.create'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Projects/Create'));
});

test('authenticated user can create a project', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('projects.store'), [
        'name' => 'My New Project',
        'description' => 'A test project for the workspace.',
        'status' => 'active',
        'color' => '#5C59D9',
        'repository_url' => 'https://github.com/user/repo',
        'production_url' => 'https://my-app.com',
        'staging_url' => 'https://staging.my-app.com',
        'server_ip' => '192.168.1.1',
    ]);

    $this->assertDatabaseHas('projects', [
        'name' => 'My New Project',
        'status' => 'active',
        'slug' => 'my-new-project',
    ]);

    $project = Project::where('name', 'My New Project')->first();
    $response->assertRedirect(route('projects.show', $project));
});

test('project slug is auto-generated on create', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post(route('projects.store'), [
        'name' => 'Workspace OS Platform',
        'status' => 'active',
    ]);

    $this->assertDatabaseHas('projects', [
        'name' => 'Workspace OS Platform',
        'slug' => 'workspace-os-platform',
    ]);
});

test('authenticated user can view project detail', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $project = Project::factory()->create();

    $response = $this->get(route('projects.show', $project));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Projects/Show')
        ->has('project')
        ->has('taskStats')
        ->has('progressPercent')
    );
});

test('authenticated user can update a project', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $project = Project::factory()->create(['name' => 'Old Name', 'status' => 'pipeline']);

    $response = $this->put(route('projects.update', $project), [
        'name' => 'Updated Project Name',
        'status' => 'active',
        'color' => '#2BB673',
    ]);

    $response->assertRedirect(route('projects.show', $project));
    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => 'Updated Project Name',
        'status' => 'active',
    ]);
});

test('authenticated user can delete a project', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $project = Project::factory()->create();

    $response = $this->delete(route('projects.destroy', $project));

    $response->assertRedirect(route('projects.index'));
    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
});

test('progress percent is zero when project has no tasks', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $project = Project::factory()->create();

    $response = $this->get(route('projects.show', $project));
    $response->assertInertia(fn ($page) => $page
        ->where('progressPercent', 0)
        ->where('taskStats.total', 0)
    );
});

test('progress percent is calculated correctly from tasks', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $project = Project::factory()->create();

    Task::factory()->create(['project_id' => $project->id, 'status' => 'done']);
    Task::factory()->create(['project_id' => $project->id, 'status' => 'done']);
    Task::factory()->create(['project_id' => $project->id, 'status' => 'todo']);
    Task::factory()->create(['project_id' => $project->id, 'status' => 'in_progress']);

    $response = $this->get(route('projects.show', $project));
    $response->assertInertia(fn ($page) => $page
        ->where('progressPercent', 50)
        ->where('taskStats.total', 4)
        ->where('taskStats.done', 2)
    );
});

test('project status validation rejects invalid values', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('projects.store'), [
        'name' => 'Test Project',
        'status' => 'invalid_status',
    ]);

    $response->assertSessionHasErrors(['status']);
});
