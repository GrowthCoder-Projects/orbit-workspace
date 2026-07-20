<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->user = User::factory()->create(['id' => 1]);
});

test('can render integrations settings page', function () {
    $response = $this->actingAs($this->user)->get(route('integrations.edit'));

    $response->assertOk();
});

test('can update telegram integration settings', function () {
    $response = $this->actingAs($this->user)->patch(route('integrations.telegram.update'), [
        'telegram_bot_token' => '123456:test_token',
        'telegram_chat_id' => '987654321',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('settings', [
        'user_id' => 1,
        'key' => 'telegram_bot_token',
        'value' => json_encode('123456:test_token'),
    ]);
    $this->assertDatabaseHas('settings', [
        'user_id' => 1,
        'key' => 'telegram_chat_id',
        'value' => json_encode('987654321'),
    ]);
});

test('can send test telegram notification', function () {
    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => true]),
    ]);

    $this->actingAs($this->user);

    // Save tokens
    Setting::setValue('telegram_bot_token', '123456:test_token');
    Setting::setValue('telegram_chat_id', '987654321');

    $response = $this->post(route('integrations.telegram.test'));

    $response->assertRedirect();
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'sendMessage') &&
            $request['chat_id'] === '987654321' &&
            str_contains($request['text'], 'Test notification');
    });
});

test('can render backups settings page', function () {
    $response = $this->actingAs($this->user)->get(route('backups.edit'));

    $response->assertOk();
});

test('can trigger a database backup', function () {
    $response = $this->actingAs($this->user)->post(route('backups.run'));

    $response->assertRedirect();
});
