<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('project_milestone_id')->nullable()->after('project_id');
            
            $table->foreign('project_milestone_id', 'fk_tasks_project_milestone')
                ->references('id')
                ->on('project_milestones')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign('fk_tasks_project_milestone');
            $table->dropColumn('project_milestone_id');
        });
    }
};
