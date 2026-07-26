<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->user = User::factory()->create(['id' => 1]);
});

test('can upload custom app logo', function () {
    $file = UploadedFile::fake()->image('custom-logo.png', 200, 200);

    $response = $this->actingAs($this->user)->post(route('settings.logo.update'), [
        'logo' => $file,
    ]);

    $response->assertRedirect();

    $logoPath = Setting::getValue('app_logo');
    expect($logoPath)->not->toBeNull();
    Storage::disk('public')->assertExists($logoPath);
});

test('can reset app logo to default', function () {
    $file = UploadedFile::fake()->image('custom-logo.png', 200, 200);

    $this->actingAs($this->user)->post(route('settings.logo.update'), [
        'logo' => $file,
    ]);

    $oldPath = Setting::getValue('app_logo');
    expect($oldPath)->not->toBeNull();

    $response = $this->delete(route('settings.logo.destroy'));

    $response->assertRedirect();
    expect(Setting::getValue('app_logo'))->toBeNull();
    Storage::disk('public')->assertMissing($oldPath);
});

test('shared settings prop contains app_logo URL', function () {
    $response = $this->actingAs($this->user)->get(route('appearance.edit'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('settings.app_logo')
        ->has('settings.is_custom_logo')
    );
});
