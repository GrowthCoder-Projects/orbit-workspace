<?php

use App\Models\FinanceAccount;
use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use App\Models\FinanceBudget;
use App\Models\FinanceSaving;
use App\Models\FinanceGoal;
use App\Models\FinanceInvestment;
use App\Models\FinanceAsset;
use App\Models\FinanceLiability;
use App\Models\FinanceBill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/* -------------------------------------------------------------------------- */
/* OLD TESTS (ACCOUNTS, TRANSACTIONS, OBSERVERS)                              */
/* -------------------------------------------------------------------------- */

test('guest cannot view finance dashboard', function () {
    $response = $this->get(route('finance.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated user can view finance dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('finance.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Finance/Dashboard')
        ->has('accounts')
        ->has('netWorthIDR')
    );
});

test('authenticated user can create a finance account', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    \Illuminate\Support\Facades\Storage::fake('public');

    $response = $this->post(route('accounts.store'), [
        'name' => 'BCA Syariah',
        'type' => 'bank',
        'account_number' => '1234567890',
        'account_holder' => 'Ihsan',
        'logo' => \Illuminate\Http\UploadedFile::fake()->image('logo.png'),
        'balance' => 5000000.00,
        'currency' => 'IDR',
        'color' => '#10b981',
        'notes' => 'Rekening utama simpanan.',
    ]);

    $this->assertDatabaseHas('finance_accounts', [
        'name' => 'BCA Syariah',
        'account_number' => '1234567890',
        'account_holder' => 'Ihsan',
        'balance' => 5000000.00,
        'currency' => 'IDR',
    ]);

    $account = FinanceAccount::where('name', 'BCA Syariah')->first();
    $this->assertNotNull($account->logo_path);
    \Illuminate\Support\Facades\Storage::disk('public')->assertExists($account->logo_path);

    $response->assertRedirect();
});

test('authenticated user can update a finance account', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    \Illuminate\Support\Facades\Storage::fake('public');

    $account = FinanceAccount::factory()->create([
        'name' => 'Mandiri Savings',
        'balance' => 1000.00,
    ]);

    $response = $this->put(route('accounts.update', $account), [
        'name' => 'Mandiri Platinum',
        'type' => $account->type,
        'account_number' => '0987654321',
        'account_holder' => 'Workspace',
        'logo' => \Illuminate\Http\UploadedFile::fake()->image('new-logo.png'),
        'balance' => 2000.00,
        'currency' => $account->currency,
        'color' => '#ffffff',
        'notes' => 'Updated notes.',
    ]);

    $this->assertDatabaseHas('finance_accounts', [
        'id' => $account->id,
        'name' => 'Mandiri Platinum',
        'account_number' => '0987654321',
        'account_holder' => 'Workspace',
        'balance' => 2000.00,
    ]);

    $account->refresh();
    $this->assertNotNull($account->logo_path);
    \Illuminate\Support\Facades\Storage::disk('public')->assertExists($account->logo_path);

    $response->assertRedirect();
});

test('authenticated user can delete a finance account', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $account = FinanceAccount::factory()->create();

    $response = $this->delete(route('accounts.destroy', $account));

    $this->assertDatabaseMissing('finance_accounts', [
        'id' => $account->id,
    ]);

    $response->assertRedirect();
});

test('authenticated user can create a finance category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('categories.store'), [
        'name' => 'Kebutuhan Rumah',
        'type' => 'expense',
        'icon' => 'Home',
        'color' => '#3b82f6',
    ]);

    $this->assertDatabaseHas('finance_categories', [
        'name' => 'Kebutuhan Rumah',
        'type' => 'expense',
    ]);

    $response->assertRedirect();
});

test('observer: balance updates correctly when income is created', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $account = FinanceAccount::factory()->create([
        'balance' => 1000.00,
    ]);
    $category = FinanceCategory::factory()->create([
        'type' => 'income',
    ]);

    $response = $this->post(route('transactions.store'), [
        'account_id' => $account->id,
        'category_id' => $category->id,
        'type' => 'income',
        'amount' => 500.00,
        'transaction_date' => now()->toDateString(),
        'description' => 'Gaji bulanan',
    ]);

    $response->assertRedirect();

    // Balance should increase by 500.00 (1000 + 500 = 1500)
    $this->assertEquals(1500.00, $account->fresh()->balance);
});

test('observer: balance updates correctly when expense is created', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $account = FinanceAccount::factory()->create([
        'balance' => 1000.00,
    ]);
    $category = FinanceCategory::factory()->create([
        'type' => 'expense',
    ]);

    $response = $this->post(route('transactions.store'), [
        'account_id' => $account->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 300.00,
        'transaction_date' => now()->toDateString(),
        'description' => 'Makan malam',
    ]);

    $response->assertRedirect();

    // Balance should decrease by 300.00 (1000 - 300 = 700)
    $this->assertEquals(700.00, $account->fresh()->balance);
});

test('observer: balance updates correctly when transfer is created', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $source = FinanceAccount::factory()->create([
        'balance' => 1000.00,
        'currency' => 'IDR',
    ]);
    $dest = FinanceAccount::factory()->create([
        'balance' => 500.00,
        'currency' => 'IDR',
    ]);

    $response = $this->post(route('transactions.store'), [
        'account_id' => $source->id,
        'destination_account_id' => $dest->id,
        'type' => 'transfer',
        'amount' => 200.00,
        'transaction_date' => now()->toDateString(),
        'description' => 'Kirim uang jajan',
    ]);

    $response->assertRedirect();

    // Source balance should decrease by 200.00 (1000 - 200 = 800)
    $this->assertEquals(800.00, $source->fresh()->balance);
    // Destination balance should increase by 200.00 (500 + 200 = 700)
    $this->assertEquals(700.00, $dest->fresh()->balance);
});

test('observer: balance updates correctly when multi-currency transfer is created', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $source = FinanceAccount::factory()->create([
        'balance' => 100.00,
        'currency' => 'USD',
    ]);
    $dest = FinanceAccount::factory()->create([
        'balance' => 10000.00,
        'currency' => 'IDR',
    ]);

    // Transfer 10 USD to IDR at 16000 rate (expecting 160000 IDR received)
    $response = $this->post(route('transactions.store'), [
        'account_id' => $source->id,
        'destination_account_id' => $dest->id,
        'type' => 'transfer',
        'amount' => 10.00,
        'converted_amount' => 160000.00,
        'exchange_rate' => 16000.000000,
        'transaction_date' => now()->toDateString(),
        'description' => 'Transfer USD ke IDR',
    ]);

    $response->assertRedirect();

    // Source balance should decrease by 10.00 USD (100 - 10 = 90)
    $this->assertEquals(90.00, $source->fresh()->balance);
    // Destination balance should increase by 160,000.00 IDR (10000 + 160000 = 170000)
    $this->assertEquals(170000.00, $dest->fresh()->balance);
});

test('observer: balance reverses correctly when transaction is deleted', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $account = FinanceAccount::factory()->create([
        'balance' => 1000.00,
    ]);
    $tx = FinanceTransaction::factory()->create([
        'account_id' => $account->id,
        'type' => 'income',
        'amount' => 500.00,
    ]);

    // Initial check: balance should have been updated by factory creation trigger (1000 + 500 = 1500)
    $this->assertEquals(1500.00, $account->fresh()->balance);

    $response = $this->delete(route('transactions.destroy', $tx));

    $response->assertRedirect();
    // After deletion: balance should reverse back to 1000.00
    $this->assertEquals(1000.00, $account->fresh()->balance);
});

test('observer: balance adjusts correctly when transaction is updated', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $account1 = FinanceAccount::factory()->create([
        'balance' => 1000.00,
    ]);
    $account2 = FinanceAccount::factory()->create([
        'balance' => 500.00,
    ]);
    $category = FinanceCategory::factory()->create(['type' => 'expense']);

    // Create initial expense transaction of 200 on Account 1 (Account 1 balance: 800)
    $tx = FinanceTransaction::factory()->create([
        'account_id' => $account1->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 200.00,
    ]);

    $this->assertEquals(800.00, $account1->fresh()->balance);

    // Update transaction: change amount to 300, and shift account to Account 2
    $response = $this->patch(route('transactions.update', $tx), [
        'account_id' => $account2->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 300.00,
        'transaction_date' => now()->toDateString(),
        'description' => 'Makan malam mewah',
    ]);

    $response->assertRedirect();

    // Account 1 should be restored (800 + 200 = 1000)
    $this->assertEquals(1000.00, $account1->fresh()->balance);
    // Account 2 should be decremented by 300 (500 - 300 = 200)
    $this->assertEquals(200.00, $account2->fresh()->balance);
});

/* -------------------------------------------------------------------------- */
/* NEW SUB-MODULES TESTS                                                      */
/* -------------------------------------------------------------------------- */

test('authenticated user can view budgets page and store budget', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = FinanceCategory::factory()->create(['type' => 'expense']);

    $response = $this->get(route('budgets.index'));
    $response->assertOk();

    // Store budget
    $response = $this->post(route('budgets.store'), [
        'category_id' => $category->id,
        'amount' => 2500000.00,
        'year' => 2026,
        'month' => 7,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('finance_budgets', [
        'category_id' => $category->id,
        'amount' => 2500000.00,
        'year' => 2026,
        'month' => 7,
    ]);
});

test('authenticated user can manage savings and goals', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // View index
    $response = $this->get(route('savings.index'));
    $response->assertOk();

    // Store saving target
    $response = $this->post(route('savings.store'), [
        'name' => 'Dana Darurat',
        'target_amount' => 20000000.00,
        'current_amount' => 5000000.00,
        'target_date' => '2026-12-31',
        'currency' => 'IDR',
        'color' => '#10b981',
        'notes' => 'Simpanan darurat 6 bulan pengeluaran',
    ]);
    $response->assertRedirect();
    $this->assertDatabaseHas('finance_savings', ['name' => 'Dana Darurat']);

    // Store financial goal
    $response = $this->post(route('goals.store'), [
        'title' => 'DP Rumah',
        'target_amount' => 100000000.00,
        'current_amount' => 10000000.00,
        'deadline' => '2027-06-30',
        'notes' => 'Target DP Rumah di Tangerang',
    ]);
    $response->assertRedirect();
    $this->assertDatabaseHas('finance_goals', ['title' => 'DP Rumah']);
});

test('authenticated user can manage investments and calculates ROI', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $account = FinanceAccount::factory()->create();

    // Store investment
    $response = $this->post(route('investments.store'), [
        'account_id' => $account->id,
        'name' => 'Bitcoin',
        'type' => 'crypto',
        'shares_quantity' => 0.05,
        'average_buy_price' => 50000.00, // 50,000 USD
        'current_price' => 60000.00,     // 60,000 USD
        'currency' => 'USD',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('finance_investments', ['name' => 'Bitcoin']);

    // Check index calculation
    $response = $this->get(route('investments.index'));
    $response->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->component('Finance/Investments')
        ->has('investments')
        ->where('investments.0.roi', 20) // (60k-50k)/50k * 100 = 20%
        ->where('investments.0.profit_loss', 500) // (60k-50k) * 0.05 = 500 USD
    );
});

test('authenticated user can manage physical assets and liabilities', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // View split page
    $response = $this->get(route('assets.index'));
    $response->assertOk();

    // Store physical asset
    $response = $this->post(route('assets.store'), [
        'name' => 'Macbook Pro 16',
        'category' => 'electronics',
        'purchase_value' => 35000000.00,
        'current_value' => 30000000.00,
        'purchase_date' => '2026-01-10',
        'depreciation_rate' => 10.00,
        'notes' => 'Laptop kerja utama.',
    ]);
    $response->assertRedirect();
    $this->assertDatabaseHas('finance_assets', ['name' => 'Macbook Pro 16']);

    // Store liability
    $response = $this->post(route('liabilities.store'), [
        'name' => 'Cicilan HP',
        'type' => 'installment',
        'total_amount' => 15000000.00,
        'remaining_amount' => 10000000.00,
        'interest_rate' => 0.00,
        'due_date' => '2026-12-25',
        'notes' => 'Cicilan 12 bulan',
    ]);
    $response->assertRedirect();
    $this->assertDatabaseHas('finance_liabilities', ['name' => 'Cicilan HP']);
});

test('authenticated user can pay a bill with one-click and register transaction in ledger', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $account = FinanceAccount::factory()->create(['balance' => 5000.00]);
    $category = FinanceCategory::factory()->create(['type' => 'expense']);
    
    $bill = FinanceBill::create([
        'name' => 'Netflix Premium',
        'type' => 'subscription',
        'amount' => 200.00,
        'currency' => 'IDR',
        'due_day' => 15,
        'recurrence_period' => 'monthly',
        'category_id' => $category->id,
        'account_id' => $account->id,
    ]);

    // Perform Pay Action
    $response = $this->post(route('bills.pay', $bill), [
        'account_id' => $account->id,
    ]);

    $response->assertRedirect();

    // Verify transaction created
    $this->assertDatabaseHas('finance_transactions', [
        'account_id' => $account->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 200.00,
        'description' => 'Pembayaran Langganan: Netflix Premium',
    ]);

    // Verify last_paid_at is set
    $this->assertNotNull($bill->fresh()->last_paid_at);

    // Verify observer: balance should be decremented by 200 (5000 - 200 = 4800)
    $this->assertEquals(4800.00, $account->fresh()->balance);
});

test('authenticated user can view bill payment history', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $account = FinanceAccount::factory()->create(['balance' => 5000.00]);
    $category = FinanceCategory::factory()->create(['type' => 'expense']);
    
    $bill = FinanceBill::create([
        'name' => 'Netflix Premium',
        'type' => 'subscription',
        'amount' => 200.00,
        'currency' => 'IDR',
        'due_day' => 15,
        'recurrence_period' => 'monthly',
        'category_id' => $category->id,
        'account_id' => $account->id,
    ]);

    // Create a transaction matching the bill payment description
    FinanceTransaction::create([
        'account_id' => $account->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 200.00,
        'transaction_date' => '2026-07-16',
        'description' => 'Pembayaran Langganan: Netflix Premium',
        'tags' => ['subscription', 'tagihan-otomatis'],
    ]);

    // Call the history route
    $response = $this->get(route('bills.history', $bill));

    $response->assertJsonCount(1);
    $response->assertJsonPath('0.amount', '200.00');
    $response->assertJsonPath('0.account_name', $account->name);
});

test('authenticated user can view finance reports', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('finance.reports.index'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Finance/Reports')
        ->has('cashFlowChartData')
        ->has('categoryChartData')
    );
});
