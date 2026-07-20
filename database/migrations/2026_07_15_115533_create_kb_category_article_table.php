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
        Schema::create('kb_category_article', function (Blueprint $table) {
            $table->foreignId('kb_article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kb_category_id')->constrained()->cascadeOnDelete();
            $table->primary(['kb_article_id', 'kb_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kb_category_article');
    }
};
