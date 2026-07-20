<?php

use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\User;
use App\Notifications\HabitReminderNotification;
use App\Services\HabitStreakService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

test('guest cannot view habits', function () {
    $response = $this->get(route('habits.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated user can view active and archived habits lists', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $habitActive = Habit::create([
        'user_id' => $user->id,
        'name' => 'Active Habit',
        'frequency_type' => 'daily',
        'is_active' => true,
    ]);

    $habitArchived = Habit::create([
        'user_id' => $user->id,
        'name' => 'Archived Habit',
        'frequency_type' => 'daily',
        'is_active' => false,
        'archived_at' => now(),
    ]);

    $response = $this->get(route('habits.index'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Habits/Index')
        ->has('habits')
        ->has('archived_habits')
    );
});

test('authenticated user can create a habit', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('habits.store'), [
        'name' => 'Read Code',
        'description' => 'Focus session',
        'frequency_type' => 'custom_days',
        'frequency_days' => ['mon', 'wed', 'fri'],
        'color_accent' => 'indigo',
        'reminder_time' => '19:00',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('habits', [
        'user_id' => $user->id,
        'name' => 'Read Code',
        'frequency_type' => 'custom_days',
        'color_accent' => 'indigo',
        'reminder_time' => '19:00',
    ]);

    $habit = Habit::where('name', 'Read Code')->first();
    expect($habit->frequency_days)->toEqual(['mon', 'wed', 'fri']);
});

test('authenticated user can update a habit', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $habit = Habit::create([
        'user_id' => $user->id,
        'name' => 'Old Name',
        'frequency_type' => 'daily',
        'color_accent' => 'emerald',
    ]);

    $response = $this->put(route('habits.update', $habit), [
        'name' => 'New Name',
        'frequency_type' => 'weekly',
        'frequency_count' => 3,
        'color_accent' => 'rose',
        'reminder_time' => '08:30',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('habits', [
        'id' => $habit->id,
        'name' => 'New Name',
        'frequency_type' => 'weekly',
        'frequency_count' => 3,
        'color_accent' => 'rose',
        'reminder_time' => '08:30',
    ]);
});

test('authenticated user can toggle completion for a habit', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $habit = Habit::create([
        'user_id' => $user->id,
        'name' => 'Toggle Habit',
        'frequency_type' => 'daily',
    ]);

    $todayStr = Carbon::today()->format('Y-m-d');

    // Toggle complete
    $response = $this->post(route('habits.toggle', $habit), [
        'date' => $todayStr,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('habit_logs', [
        'habit_id' => $habit->id,
        'completed_date' => $todayStr,
    ]);

    // Toggle incomplete
    $response = $this->post(route('habits.toggle', $habit), [
        'date' => $todayStr,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseMissing('habit_logs', [
        'habit_id' => $habit->id,
        'completed_date' => $todayStr,
    ]);
});

test('authenticated user cannot toggle completion older than 30 days', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $habit = Habit::create([
        'user_id' => $user->id,
        'name' => 'Toggle Habit',
        'frequency_type' => 'daily',
    ]);

    $oldDateStr = Carbon::today()->subDays(31)->format('Y-m-d');

    $response = $this->post(route('habits.toggle', $habit), [
        'date' => $oldDateStr,
    ]);

    $response->assertSessionHasErrors('date');
    $this->assertDatabaseMissing('habit_logs', [
        'habit_id' => $habit->id,
        'completed_date' => $oldDateStr,
    ]);
});

test('authenticated user can archive and restore a habit', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $habit = Habit::create([
        'user_id' => $user->id,
        'name' => 'Archive Habit',
        'frequency_type' => 'daily',
        'is_active' => true,
    ]);

    // Archive
    $response = $this->post(route('habits.archive', $habit));
    $response->assertRedirect();
    $this->assertFalse($habit->fresh()->is_active);
    expect($habit->fresh()->archived_at)->not->toBeNull();

    // Restore
    $response = $this->post(route('habits.archive', $habit));
    $response->assertRedirect();
    $this->assertTrue($habit->fresh()->is_active);
    expect($habit->fresh()->archived_at)->toBeNull();
});

test('authenticated user can delete a habit', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $habit = Habit::create([
        'user_id' => $user->id,
        'name' => 'Delete Habit',
        'frequency_type' => 'daily',
    ]);

    $response = $this->delete(route('habits.destroy', $habit));
    $response->assertRedirect();
    $this->assertDatabaseMissing('habits', ['id' => $habit->id]);
});

test('daily habit calculates current and longest streaks correctly', function () {
    $user = User::factory()->create();
    $habit = Habit::create([
        'user_id' => $user->id,
        'name' => 'Daily Streak Test',
        'frequency_type' => 'daily',
    ]);

    $streakService = new HabitStreakService();

    // Log for today, yesterday, and 2 days ago (streak = 3)
    HabitLog::create(['habit_id' => $habit->id, 'completed_date' => Carbon::today()->format('Y-m-d')]);
    HabitLog::create(['habit_id' => $habit->id, 'completed_date' => Carbon::yesterday()->format('Y-m-d')]);
    HabitLog::create(['habit_id' => $habit->id, 'completed_date' => Carbon::today()->subDays(2)->format('Y-m-d')]);

    $streakService->recalculate($habit);
    $habit->refresh();

    expect($habit->streak_current)->toBe(3);
    expect($habit->streak_longest)->toBe(3);

    // Skip a day and log 4, 5, 6 days ago (longest remains 3, current resets to 0)
    HabitLog::create(['habit_id' => $habit->id, 'completed_date' => Carbon::today()->subDays(4)->format('Y-m-d')]);
    HabitLog::create(['habit_id' => $habit->id, 'completed_date' => Carbon::today()->subDays(5)->format('Y-m-d')]);
    HabitLog::create(['habit_id' => $habit->id, 'completed_date' => Carbon::today()->subDays(6)->format('Y-m-d')]);

    // Let's delete today and yesterday's logs to break the current streak
    HabitLog::where('habit_id', $habit->id)->whereIn('completed_date', [
        Carbon::today()->format('Y-m-d'),
        Carbon::yesterday()->format('Y-m-d'),
        Carbon::today()->subDays(2)->format('Y-m-d')
    ])->delete();

    $streakService->recalculate($habit);
    $habit->refresh();

    expect($habit->streak_current)->toBe(0);
    expect($habit->streak_longest)->toBe(3); // Should retain the longest streak of 3
});

test('custom days habit calculates current streak correctly', function () {
    $user = User::factory()->create();
    // Monday, Wednesday, Friday
    $habit = Habit::create([
        'user_id' => $user->id,
        'name' => 'Custom Streak Test',
        'frequency_type' => 'custom_days',
        'frequency_days' => ['mon', 'wed', 'fri'],
    ]);

    $streakService = new HabitStreakService();

    // Mock logging schedule: We will insert completions for specific dates representing Mon, Wed, Fri
    // Let's find dates corresponding to a Monday, Wednesday, Friday
    $monday = Carbon::parse('next monday');
    $wednesday = $monday->copy()->addDays(2);
    $friday = $monday->copy()->addDays(4);

    HabitLog::create(['habit_id' => $habit->id, 'completed_date' => $monday->format('Y-m-d')]);
    HabitLog::create(['habit_id' => $habit->id, 'completed_date' => $wednesday->format('Y-m-d')]);
    HabitLog::create(['habit_id' => $habit->id, 'completed_date' => $friday->format('Y-m-d')]);

    // Set today as Friday to check streak
    Carbon::setTestNow($friday);

    $streakService->recalculate($habit);
    $habit->refresh();

    expect($habit->streak_current)->toBe(3);

    // Reset clock mocking
    Carbon::setTestNow();
});

test('habit reminder command triggers notification successfully', function () {
    Notification::fake();

    $user = User::factory()->create();
    $nowTime = now()->format('H:i');

    $habit = Habit::create([
        'user_id' => $user->id,
        'name' => 'Incomplete Reminder Habit',
        'frequency_type' => 'daily',
        'reminder_time' => $nowTime,
        'is_active' => true,
    ]);

    // Execute the console command
    $this->artisan('habits:send-reminders')->assertExitCode(0);

    Notification::assertSentTo(
        $user,
        HabitReminderNotification::class,
        function ($notification, $channels) use ($habit) {
            return $notification->habit->id === $habit->id;
        }
    );
});
