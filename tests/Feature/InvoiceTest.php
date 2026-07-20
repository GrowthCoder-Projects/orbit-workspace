<?php

use App\Models\Client;
use App\Models\FinanceAccount;
use App\Models\FinanceCategory;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot access invoices routes', function () {
    $response = $this->get(route('invoices.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated user can view invoices index', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $invoice = Invoice::factory()->create(['user_id' => $user->id]);

    $response = $this->get(route('invoices.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Invoices/Index')
        ->has('invoices')
        ->has('clients')
        ->has('projects')
        ->has('accounts')
    );
});

test('authenticated user can view invoice creation page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('invoices.create'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Invoices/CreateEdit')
        ->has('clients')
        ->has('projects')
        ->has('nextNumber')
    );
});

test('authenticated user can store invoice with items', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $client = Client::factory()->create();

    $invoiceData = [
        'client_id' => $client->id,
        'invoice_number' => 'INV-2026-9999',
        'status' => 'draft',
        'issue_date' => '2026-07-16',
        'due_date' => '2026-08-16',
        'currency' => 'IDR',
        'tax_rate' => 11.00,
        'discount_amount' => 100000.00,
        'discount_type' => 'fixed',
        'template_name' => 'modern',
        'color_accent' => '#3b82f6',
        'font_family' => 'Helvetica',
        'spacing' => 'cozy',
        'notes' => 'Harap bayar tepat waktu.',
        'items' => [
            [
                'description' => 'Web Development',
                'quantity' => 10.00,
                'unit_price' => 500000.00, // 5M subtotal
                'tax_rate' => 0.00,
                'discount_amount' => 0.00,
            ],
            [
                'description' => 'Desain UI/UX',
                'quantity' => 1.00,
                'unit_price' => 1000000.00, // 1M subtotal
                'tax_rate' => 0.00,
                'discount_amount' => 0.00,
            ]
        ]
    ];

    $response = $this->post(route('invoices.store'), $invoiceData);

    $response->assertRedirect(route('invoices.index'));

    $this->assertDatabaseHas('invoices', [
        'invoice_number' => 'INV-2026-9999',
        'currency' => 'IDR',
        'subtotal' => 6000000.00, // (10 * 500k) + (1 * 1M)
        'total' => 6560000.00,    // 6M + 11% tax (660k) - 100k discount
    ]);

    $this->assertDatabaseHas('invoice_items', [
        'description' => 'Web Development',
        'quantity' => 10.00,
        'unit_price' => 500000.00,
    ]);
});

test('authenticated user can view single invoice and its edit page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $invoice = Invoice::factory()->create(['user_id' => $user->id]);

    $response = $this->get(route('invoices.show', $invoice));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Invoices/Show')
        ->has('invoice')
    );

    $response = $this->get(route('invoices.edit', $invoice));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Invoices/CreateEdit')
        ->has('invoice')
    );
});

test('authenticated user can update invoice and recreate items', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $invoice = Invoice::factory()->create(['user_id' => $user->id]);
    $item = InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

    $updateData = [
        'client_id' => $invoice->client_id,
        'invoice_number' => 'INV-2026-UPDATED',
        'status' => 'sent',
        'issue_date' => $invoice->issue_date->toDateString(),
        'due_date' => $invoice->due_date->toDateString(),
        'currency' => 'IDR',
        'tax_rate' => 0.00,
        'discount_amount' => 50000.00,
        'discount_type' => 'fixed',
        'template_name' => 'minimalist',
        'color_accent' => '#000000',
        'font_family' => 'Helvetica',
        'spacing' => 'compact',
        'items' => [
            [
                'description' => 'Item Diperbarui',
                'quantity' => 2.00,
                'unit_price' => 250000.00,
                'tax_rate' => 0.00,
                'discount_amount' => 0.00,
            ]
        ]
    ];

    $response = $this->put(route('invoices.update', $invoice), $updateData);

    $response->assertRedirect(route('invoices.show', $invoice));

    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'invoice_number' => 'INV-2026-UPDATED',
        'template_name' => 'minimalist',
        'subtotal' => 500000.00,
        'total' => 450000.00, // 500k - 50k
    ]);

    // Check old item was deleted
    $this->assertDatabaseMissing('invoice_items', ['id' => $item->id]);

    // Check new item was created
    $this->assertDatabaseHas('invoice_items', [
        'invoice_id' => $invoice->id,
        'description' => 'Item Diperbarui',
    ]);
});

test('authenticated user can delete an invoice', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $invoice = Invoice::factory()->create(['user_id' => $user->id]);
    $item = InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

    $response = $this->delete(route('invoices.destroy', $invoice));

    $response->assertRedirect(route('invoices.index'));
    
    $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
    $this->assertDatabaseMissing('invoice_items', ['id' => $item->id]);
});

test('authenticated user can pay an invoice and trigger finance transaction', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $account = FinanceAccount::factory()->create(['balance' => 1000000.00]);
    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'status' => 'sent',
        'total' => 500000.00,
    ]);

    $response = $this->post(route('invoices.pay', $invoice), [
        'finance_account_id' => $account->id,
    ]);

    $response->assertRedirect();
    
    // Verify invoice is paid
    $this->assertEquals('paid', $invoice->fresh()->status);
    $this->assertNotNull($invoice->fresh()->paid_at);
    $this->assertEquals($account->id, $invoice->fresh()->finance_account_id);

    // Verify ledger transaction was automatically created
    $this->assertDatabaseHas('finance_transactions', [
        'account_id' => $account->id,
        'type' => 'income',
        'amount' => 500000.00,
        'description' => 'Pembayaran Invoice: ' . $invoice->invoice_number,
    ]);

    // Verify observer: account balance should be updated (1M + 500k = 1.5M)
    $this->assertEquals(1500000.00, $account->fresh()->balance);
});

test('client accessors correctly sum actual invoices data', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $client = Client::factory()->create();

    // Create 3 invoices: paid (300k), sent (200k), overdue (150k)
    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'paid',
        'total' => 300000.00,
    ]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'total' => 200000.00,
    ]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'overdue',
        'total' => 150000.00,
    ]);

    // Accessors checks
    $this->assertEquals(300000.00, $client->fresh()->paid_amount);
    $this->assertEquals(350000.00, $client->fresh()->unpaid_amount); // 200k + 150k
    $this->assertEquals(150000.00, $client->fresh()->overdue_amount);
    $this->assertEquals(300000.00, $client->fresh()->lifetime_value);
});

test('authenticated user can render and stream invoice pdf', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $invoice = Invoice::factory()->create(['user_id' => $user->id]);
    InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

    $response = $this->get(route('invoices.pdf', $invoice));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
});
