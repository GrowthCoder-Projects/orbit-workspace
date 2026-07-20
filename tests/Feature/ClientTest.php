<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\User;

test('guest cannot view clients', function () {
    $response = $this->get(route('clients.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated user can view client list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Client::factory()->create();

    $response = $this->get(route('clients.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Clients/Index')
        ->has('clients')
    );
});

test('authenticated user can create a client', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('clients.store'), [
        'name' => 'Wayne Enterprises',
        'company' => 'Wayne Corp',
        'email' => 'bruce@wayne.com',
        'phone' => '+1-555-1234',
        'tax_id' => 'VAT-998877',
        'billing_address' => 'Wayne Manor, Gotham',
        'notes' => 'Likes dark mode.',
    ]);

    $this->assertDatabaseHas('clients', [
        'name' => 'Wayne Enterprises',
        'company' => 'Wayne Corp',
        'email' => 'bruce@wayne.com',
    ]);

    $response->assertRedirect(route('clients.index'));
});

test('authenticated user can update a client', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $client = Client::factory()->create(['name' => 'Old Client Name']);

    $response = $this->put(route('clients.update', $client), [
        'name' => 'Updated Client Name',
        'company' => $client->company,
        'email' => $client->email,
        'phone' => $client->phone,
        'tax_id' => $client->tax_id,
        'billing_address' => $client->billing_address,
        'notes' => 'New notes text.',
    ]);

    $response->assertRedirect(route('clients.index'));
    $this->assertDatabaseHas('clients', [
        'id' => $client->id,
        'name' => 'Updated Client Name',
        'notes' => 'New notes text.',
    ]);
});

test('authenticated user can delete a client', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $client = Client::factory()->create();
    $project = Project::factory()->create(['client_id' => $client->id]);

    $response = $this->delete(route('clients.destroy', $client));

    $response->assertRedirect(route('clients.index'));
    $this->assertDatabaseMissing('clients', ['id' => $client->id]);

    // Verify associated project was unlinked (client_id set to null)
    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'client_id' => null,
    ]);
});

test('client validation requires name', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('clients.store'), [
        'name' => '', // blank name
    ]);

    $response->assertSessionHasErrors(['name']);
});
