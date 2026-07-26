<?php

use App\Models\Setting;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['id' => 1]);
});

test('can render features settings page', function () {
    $response = $this->actingAs($this->user)->get(route('settings.features.edit'));

    $response->assertOk();
});

test('can update enabled modules settings', function () {
    $response = $this->actingAs($this->user)->patch(route('settings.features.update'), [
        'projects' => true,
        'tasks' => true,
        'clients' => true,
        'invoices' => true,
        'calendar' => true,
        'finance' => false,
        'habits' => true,
        'documents' => true,
        'notes' => true,
        'kb' => true,
        'bookmarks' => true,
    ]);

    $response->assertRedirect();

    $enabledModules = Setting::getValue('enabled_modules');
    expect($enabledModules['finance'])->toBeFalse();
    expect($enabledModules['projects'])->toBeTrue();
});

test('redirects from route of a disabled module to dashboard', function () {
    $this->actingAs($this->user);

    Setting::setValue('enabled_modules', [
        'projects' => true,
        'tasks' => true,
        'clients' => true,
        'invoices' => true,
        'calendar' => true,
        'finance' => false,
        'habits' => true,
        'documents' => true,
        'notes' => true,
        'kb' => true,
        'bookmarks' => true,
    ]);

    $response = $this->get(route('finance.index'));

    $response->assertRedirect(route('dashboard'));
});
