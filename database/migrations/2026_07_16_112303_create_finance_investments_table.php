<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('finance_investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('finance_accounts')->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // 'stock', 'mutual_fund', 'gold', 'crypto', 'bond', 'deposit'
            $table->decimal('shares_quantity', 15, 6);
            $table->decimal('average_buy_price', 15, 2);
            $table->decimal('current_price', 15, 2);
            $table->string('currency', 3)->default('IDR');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_investments');
    }
};
