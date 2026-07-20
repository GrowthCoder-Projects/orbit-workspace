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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action'); // created, updated, deleted, login, logout, status_changed, budget_alert
            $table->string('subject_type')->nullable(); // Fully qualified model class name
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable(); // e.g. "Project", "Task", "Invoice"
            $table->string('description'); // Human-readable description
            $table->json('properties')->nullable(); // Diff: [{field, old, new}]
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
