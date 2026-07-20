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
        Schema::create('finance_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('finance_categories')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('period')->default('monthly'); // 'monthly', 'annual'
            $table->integer('year');
            $table->integer('month');
            $table->timestamps();
            $table->unique(['category_id', 'period', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_budgets');
    }
};
