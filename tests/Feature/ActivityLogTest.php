<?php

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;

uses(RefreshDatabase::class);

// ─── ActivityLogService ───────────────────────────────────────────────────────

test('ActivityLogService::created logs a created entry', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $project = Project::factory()->create(['name' => 'Test Project']);

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'created',
        'subject_type' => Project::class,
        'subject_id' => $project->id,
        'subject_label' => 'Projects',
        'description' => 'Project "Test Project" dibuat',
    ]);
});

test('ActivityLogService::updated logs changed fields as properties', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $project = Project::factory()->create(['name' => 'Old Name']);
    $project->update(['name' => 'New Name']);

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'updated',
        'subject_type' => Project::class,
        'subject_id' => $project->id,
    ]);

    $log = ActivityLog::where('action', 'updated')
        ->where('subject_type', Project::class)
        ->where('subject_id', $project->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->properties)->toBeArray()
        ->and(collect($log->properties)->where('field', 'name')->first()['old'])->toBe('Old Name')
        ->and(collect($log->properties)->where('field', 'name')->first()['new'])->toBe('New Name');
});

test('ActivityLogService::deleted logs a deleted entry', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $project = Project::factory()->create(['name' => 'Doomed Project']);
    $projectId = $project->id;

    $project->delete();

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'deleted',
        'subject_type' => Project::class,
        'subject_id' => $projectId,
        'description' => 'Project "Doomed Project" dihapus',
    ]);
});

test('ActivityLogService::statusChanged logs status transition with properties', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $task = Task::factory()->create(['title' => 'Sample Task', 'status' => 'todo']);
    $task->update(['status' => 'in_progress']);

    $log = ActivityLog::where('action', 'status_changed')
        ->where('subject_type', Task::class)
        ->where('subject_id', $task->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->properties[0]['field'])->toBe('status')
        ->and($log->properties[0]['old'])->toBe('todo')
        ->and($log->properties[0]['new'])->toBe('in_progress');
});

// ─── ActivityLog Scopes ───────────────────────────────────────────────────────

test('ActivityLog::scopeForModule filters by subject_label', function () {
    ActivityLog::create([
        'action' => 'created',
        'subject_label' => 'Projects',
        'description' => 'Project created',
    ]);

    ActivityLog::create([
        'action' => 'created',
        'subject_label' => 'Tasks',
        'description' => 'Task created',
    ]);

    $projectLogs = ActivityLog::forModule('Projects')->get();
    $taskLogs = ActivityLog::forModule('Tasks')->get();

    expect($projectLogs)->toHaveCount(1)
        ->and($taskLogs)->toHaveCount(1);
});

test('ActivityLog::scopeForDateRange filters by date range', function () {
    $oldLog = ActivityLog::create([
        'action' => 'login',
        'description' => 'scope-date-test-old',
        'created_at' => now()->subDays(10),
    ]);

    $recentLog = ActivityLog::create([
        'action' => 'login',
        'description' => 'scope-date-test-recent',
        'created_at' => now()->subDay(),
    ]);

    $recentLogs = ActivityLog::forDateRange(now()->subDays(3)->toDateString(), now()->toDateString())->get();

    expect($recentLogs->where('description', 'scope-date-test-old')->count())->toBe(0)
        ->and($recentLogs->where('description', 'scope-date-test-recent')->count())->toBe(1);
});

// ─── Activity Page ────────────────────────────────────────────────────────────

test('guest cannot access activity page', function () {
    $response = $this->get(route('activity.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated user can access activity page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('activity.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Activity/Index')
        ->has('notifications')
        ->has('activityLogs')
        ->has('availableModules')
        ->has('filters')
        ->has('unreadCount')
    );
});

test('activity page passes correct filter data', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('activity.index', ['tab' => 'activity', 'module' => 'Projects']));

    $response->assertInertia(fn ($page) => $page
        ->where('filters.tab', 'activity')
        ->where('filters.module', 'Projects')
    );
});

// ─── Notifications ────────────────────────────────────────────────────────────

test('notifications poll endpoint returns json with unread count and notifications', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->getJson(route('notifications.poll'));

    $response->assertOk()
        ->assertJsonStructure(['unread_count', 'notifications']);
});

test('mark all read sets read_at for all unread notifications', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create a fake notification
    $user->notify(new \App\Notifications\ActivityNotification(
        title: 'Test Notif',
        body: 'Test body',
    ));

    expect($user->unreadNotifications()->count())->toBe(1);

    $this->post(route('notifications.mark-all-read'));

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

// ─── PruneActivityLogs Command ────────────────────────────────────────────────

test('PruneActivityLogs command deletes logs older than specified days', function () {
    $oldLog = ActivityLog::create([
        'action' => 'login',
        'description' => 'prune-test-old-log',
        'created_at' => now()->subDays(130),
    ]);

    $recentLog = ActivityLog::create([
        'action' => 'login',
        'description' => 'prune-test-recent-log',
        'created_at' => now()->subDays(10),
    ]);

    $this->artisan('activity-logs:prune --days=120')->assertSuccessful();

    expect(ActivityLog::where('description', 'prune-test-old-log')->exists())->toBeFalse()
        ->and(ActivityLog::where('description', 'prune-test-recent-log')->exists())->toBeTrue();
});
