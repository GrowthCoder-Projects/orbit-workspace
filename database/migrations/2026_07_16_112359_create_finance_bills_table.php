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
        Schema::create('finance_bills', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // 'bill', 'subscription'
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('IDR');
            $table->integer('due_day'); // 1-31
            $table->string('recurrence_period')->default('monthly'); // 'monthly', 'annual'
            $table->foreignId('category_id')->nullable()->constrained('finance_categories')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->date('last_paid_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_bills');
    }
};
