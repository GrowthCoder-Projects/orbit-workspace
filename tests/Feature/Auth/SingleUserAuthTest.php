<?php

use App\Models\User;
use Illuminate\Support\Facades\Config;

test('registration is blocked when a user already exists in single-user mode', function () {
    // Assert single-user mode is active
    Config::set('workspace.single_user', true);

    // Create the first user
    User::factory()->create([
        'email' => 'first@example.com',
    ]);

    // Attempt to register a second user
    $response = $this->post(route('register.store'), [
        'name' => 'Second User',
        'email' => 'second@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    // Assert validation error
    $response->assertSessionHasErrors(['email']);
    $this->assertDatabaseCount('users', 1);
});

test('registration screen redirects to login when user exists in single-user mode', function () {
    Config::set('workspace.single_user', true);

    // Create the first user
    User::factory()->create([
        'email' => 'first@example.com',
    ]);

    // Visit register page
    $response = $this->get(route('register'));

    // Assert redirect to login
    $response->assertRedirect(route('login'));
});

test('multiple registrations are allowed when single-user mode is disabled', function () {
    Config::set('workspace.single_user', false);

    // Create the first user
    User::factory()->create([
        'email' => 'first@example.com',
    ]);

    // Visit register page
    $responseGet = $this->get(route('register'));
    $responseGet->assertOk();

    // Register second user
    $responsePost = $this->post(route('register.store'), [
        'name' => 'Second User',
        'email' => 'second@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $this->assertDatabaseCount('users', 2);
    $responsePost->assertRedirect(route('dashboard', absolute: false));
});

test('middleware blocks non-user-1 in single-user mode', function () {
    Config::set('workspace.single_user', true);

    // Create two users
    $user1 = User::factory()->create(['id' => 1]);
    $user2 = User::factory()->create(['id' => 2]);

    // Access dashboard as User 1
    $response1 = $this->actingAs($user1)->get(route('dashboard'));
    $response1->assertOk();

    // Access dashboard as User 2
    $response2 = $this->actingAs($user2)->get(route('dashboard'));
    $response2->assertForbidden();
});

test('middleware allows any user in multi-user mode', function () {
    Config::set('workspace.single_user', false);

    // Create two users
    $user1 = User::factory()->create(['id' => 1]);
    $user2 = User::factory()->create(['id' => 2]);

    // Access dashboard as User 1
    $response1 = $this->actingAs($user1)->get(route('dashboard'));
    $response1->assertOk();

    // Access dashboard as User 2
    $response2 = $this->actingAs($user2)->get(route('dashboard'));
    $response2->assertOk();
});
