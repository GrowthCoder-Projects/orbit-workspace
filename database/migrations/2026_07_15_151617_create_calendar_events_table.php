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
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->boolean('is_all_day')->default(false);
            $table->string('color')->nullable();
            $table->string('recurrence_pattern')->nullable(); // none, daily, weekly, monthly
            $table->dateTime('recurrence_end')->nullable();
            $table->integer('reminder_lead_time')->nullable(); // in minutes (e.g. 0, 15, 60, 1440)
            $table->dateTime('reminder_sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
