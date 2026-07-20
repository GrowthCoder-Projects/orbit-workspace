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
        Schema::create('finance_assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category'); // 'property', 'vehicle', 'electronics', 'other'
            $table->decimal('purchase_value', 15, 2);
            $table->decimal('current_value', 15, 2);
            $table->date('purchase_date');
            $table->decimal('depreciation_rate', 5, 2)->default(0.00); // per year %
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_assets');
    }
};
