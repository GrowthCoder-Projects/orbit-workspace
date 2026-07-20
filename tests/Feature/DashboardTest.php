<?php

use App\Models\User;
use App\Models\Project;
use App\Models\Client;
use App\Models\Folder;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard and receive all layout data', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create seed data
    Project::factory()->create(['status' => 'active']);
    Client::factory()->create();
    Folder::factory()->create();

    $response = $this->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->has('stats')
        ->has('todaySchedule')
        ->has('activeTasks')
        ->has('expenseChartData')
        ->has('upcomingBills')
        ->has('invoiceStats')
        ->has('quickDraftNote')
        ->has('projects')
        ->has('clients')
        ->has('folders')
        ->has('nextInvoiceNumber')
    );
});
