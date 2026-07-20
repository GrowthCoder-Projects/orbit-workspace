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
        Schema::create('habits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('frequency_type'); // daily, weekly, custom_days
            $table->json('frequency_days')->nullable(); // For custom_days: ['mon', 'wed', 'fri']
            $table->unsignedInteger('frequency_count')->default(1); // For weekly: 3 (times per week)
            $table->string('color_accent')->default('emerald'); // emerald, indigo, amber, violet, rose
            $table->string('reminder_time', 5)->nullable(); // e.g. "18:30"
            $table->unsignedInteger('streak_current')->default(0);
            $table->unsignedInteger('streak_longest')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('habits');
    }
};
