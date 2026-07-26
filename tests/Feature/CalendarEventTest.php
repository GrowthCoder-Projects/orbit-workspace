<?php

use App\Models\CalendarEvent;
use App\Models\ProjectMilestone;
use App\Models\Task;
use App\Models\User;
use App\Notifications\CalendarEventReminderNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

test('guest cannot view calendar', function () {
    $response = $this->get(route('calendar.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated user can view calendar page with unified schedule', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $event = CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'start_at' => now()->startOfMonth()->addDays(5)->setHour(10)->setMinute(0),
        'end_at' => now()->startOfMonth()->addDays(5)->setHour(11)->setMinute(0),
    ]);

    $task = Task::factory()->create([
        'due_date' => now()->startOfMonth()->addDays(6)->format('Y-m-d'),
    ]);

    $milestone = ProjectMilestone::factory()->create([
        'due_date' => now()->startOfMonth()->addDays(7)->format('Y-m-d'),
    ]);

    $response = $this->get(route('calendar.index', [
        'start' => now()->startOfMonth()->toDateString(),
        'end' => now()->endOfMonth()->toDateString(),
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Calendar/Index')
        ->has('events')
        ->has('tasks')
        ->has('milestones')
        ->has('notifications')
    );
});

test('authenticated user can create calendar event', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('calendar.events.store'), [
        'title' => 'Important Meeting',
        'description' => 'Discuss project plans.',
        'start_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
        'end_at' => now()->addDays(2)->addHour()->format('Y-m-d H:i:s'),
        'is_all_day' => false,
        'color' => '#16a34a',
        'recurrence_pattern' => 'none',
        'reminder_lead_time' => 15,
    ]);

    $response->assertRedirect(route('calendar.index'));
    $this->assertDatabaseHas('calendar_events', [
        'title' => 'Important Meeting',
        'reminder_lead_time' => 15,
    ]);
});

test('authenticated user can update calendar event', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $event = CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'title' => 'Old Title',
    ]);

    $response = $this->patch(route('calendar.events.update', $event), [
        'title' => 'New Event Title',
        'start_at' => $event->start_at->format('Y-m-d H:i:s'),
        'end_at' => $event->end_at->format('Y-m-d H:i:s'),
        'recurrence_pattern' => 'weekly',
        'recurrence_end' => now()->addMonth()->format('Y-m-d'),
        'reminder_lead_time' => 60,
    ]);

    $response->assertRedirect(route('calendar.index'));
    $this->assertDatabaseHas('calendar_events', [
        'id' => $event->id,
        'title' => 'New Event Title',
        'recurrence_pattern' => 'weekly',
        'reminder_lead_time' => 60,
    ]);
});

test('authenticated user can delete calendar event', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $event = CalendarEvent::factory()->create([
        'user_id' => $user->id,
    ]);

    $response = $this->delete(route('calendar.events.destroy', $event));

    $response->assertRedirect(route('calendar.index'));
    $this->assertDatabaseMissing('calendar_events', [
        'id' => $event->id,
    ]);
});

test('recurring events generate correct dynamic occurrences within range', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create a weekly event that repeats 4 times in July 2026
    $event = CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'start_at' => '2026-07-01 10:00:00',
        'end_at' => '2026-07-01 11:00:00',
        'recurrence_pattern' => 'weekly',
        'recurrence_end' => '2026-07-25 23:59:59',
    ]);

    // Query July 2026 range
    $start = Carbon::parse('2026-07-01');
    $end = Carbon::parse('2026-07-31');

    $instances = $event->getInstancesInRange($start, $end);

    // 2026-07-01 (Wed), 2026-07-08 (Wed), 2026-07-15 (Wed), 2026-07-22 (Wed)
    expect($instances)->toHaveCount(4);
    expect($instances[0]->start_at->toDateString())->toBe('2026-07-01');
    expect($instances[1]->start_at->toDateString())->toBe('2026-07-08');
    expect($instances[2]->start_at->toDateString())->toBe('2026-07-15');
    expect($instances[3]->start_at->toDateString())->toBe('2026-07-22');
});

test('artisan command sends notifications for due reminders', function () {
    Notification::fake();

    $user = User::factory()->create();
    // Simulate auth user so BelongsToUser global scope works
    $this->actingAs($user);

    // Create event that starts in 15 minutes, with reminder_lead_time = 15 (should trigger now)
    $event = CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'start_at' => now()->addMinutes(15),
        'end_at' => now()->addMinutes(75),
        'reminder_lead_time' => 15,
        'reminder_sent_at' => null,
    ]);

    // Run the scheduler command
    $this->artisan('calendar:send-reminders')
        ->assertSuccessful();

    // Assert notification was sent to the user
    Notification::assertSentTo($user, CalendarEventReminderNotification::class);

    // Assert database updated reminder_sent_at
    $event->refresh();
    expect($event->reminder_sent_at)->not->toBeNull();
});
