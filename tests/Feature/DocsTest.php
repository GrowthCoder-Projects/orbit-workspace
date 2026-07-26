<?php

use App\Models\User;

test('public users can access interactive API documentation page', function () {
    $response = $this->get('/docs');

    $response->assertStatus(200);
});

test('authenticated users can access in-app API documentation page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/app/docs');

    $response->assertStatus(200);
});
