<?php

use App\Models\KbArticle;
use App\Models\KbCategory;
use App\Models\User;

test('guest cannot view knowledge base', function () {
    $response = $this->get(route('kb.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated user can view knowledge base list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = KbCategory::factory()->create(['user_id' => $user->id]);
    $article = KbArticle::factory()->create(['user_id' => $user->id]);
    $article->categories()->attach($category);

    $response = $this->get(route('kb.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('KnowledgeBase/Index')
        ->has('categories')
        ->has('articles')
        ->has('selectedArticleId')
    );
});

test('authenticated user can create a category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('kb.categories.store'), [
        'name' => 'Server Configuration',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('kb_categories', [
        'user_id' => $user->id,
        'name' => 'Server Configuration',
        'slug' => 'server-configuration',
    ]);
});

test('authenticated user can update a category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = KbCategory::factory()->create([
        'user_id' => $user->id,
        'name' => 'Old Name',
    ]);

    $response = $this->patch(route('kb.categories.update', $category), [
        'name' => 'New Name',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('kb_categories', [
        'id' => $category->id,
        'name' => 'New Name',
        'slug' => 'new-name',
    ]);
});

test('authenticated user can delete a category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = KbCategory::factory()->create(['user_id' => $user->id]);

    $response = $this->delete(route('kb.categories.destroy', $category));

    $response->assertRedirect();
    $this->assertDatabaseMissing('kb_categories', [
        'id' => $category->id,
    ]);
});

test('authenticated user can create an article', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = KbCategory::factory()->create(['user_id' => $user->id]);

    $response = $this->post(route('kb.articles.store'), [
        'title' => 'Server Setup Guide',
        'category_ids' => [$category->id],
    ]);

    $this->assertDatabaseHas('kb_articles', [
        'user_id' => $user->id,
        'title' => 'Server Setup Guide',
        'slug' => 'server-setup-guide',
    ]);

    $article = KbArticle::where('title', 'Server Setup Guide')->first();

    $this->assertDatabaseHas('kb_category_article', [
        'kb_article_id' => $article->id,
        'kb_category_id' => $category->id,
    ]);

    $response->assertRedirect(route('kb.index', ['id' => $article->id]));
});

test('authenticated user can create a nested article', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $parent = KbArticle::factory()->create(['user_id' => $user->id]);

    $response = $this->post(route('kb.articles.store'), [
        'title' => 'Nginx Configuration',
        'parent_article_id' => $parent->id,
    ]);

    $this->assertDatabaseHas('kb_articles', [
        'user_id' => $user->id,
        'title' => 'Nginx Configuration',
        'parent_article_id' => $parent->id,
    ]);

    $article = KbArticle::where('title', 'Nginx Configuration')->first();
    $response->assertRedirect(route('kb.index', ['id' => $article->id]));
});

test('authenticated user can update an article', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $article = KbArticle::factory()->create([
        'user_id' => $user->id,
        'title' => 'Old Title',
        'content' => 'Old content.',
    ]);

    $category = KbCategory::factory()->create(['user_id' => $user->id]);

    $response = $this->patch(route('kb.articles.update', $article), [
        'title' => 'New Title',
        'content' => '<p>New content block.</p>',
        'category_ids' => [$category->id],
    ]);

    $response->assertRedirect(route('kb.index', ['id' => $article->id]));

    $this->assertDatabaseHas('kb_articles', [
        'id' => $article->id,
        'title' => 'New Title',
        'content' => '<p>New content block.</p>',
    ]);

    $this->assertDatabaseHas('kb_category_article', [
        'kb_article_id' => $article->id,
        'kb_category_id' => $category->id,
    ]);
});

test('article update prevents circular references', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $article = KbArticle::factory()->create(['user_id' => $user->id]);

    // Attempting to set self as parent should fail or default to null
    $this->patch(route('kb.articles.update', $article), [
        'title' => $article->title,
        'parent_article_id' => $article->id,
    ]);

    $article->refresh();
    expect($article->parent_article_id)->toBeNull();
});

test('authenticated user can delete an article', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $article = KbArticle::factory()->create(['user_id' => $user->id]);

    $response = $this->delete(route('kb.articles.destroy', $article));

    $response->assertRedirect(route('kb.index'));
    $this->assertDatabaseMissing('kb_articles', [
        'id' => $article->id,
    ]);
});
