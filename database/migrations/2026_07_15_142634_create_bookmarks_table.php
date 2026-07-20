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
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('bookmark_categories')->nullOnDelete();
            $table->text('url');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('favicon_url')->nullable();
            $table->text('preview_image_url')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->string('status')->default('pending'); // pending, fetching, success, failed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
    }
};
