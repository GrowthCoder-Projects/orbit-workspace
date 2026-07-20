<?php

use App\Jobs\FetchBookmarkMetadataJob;
use App\Models\Bookmark;
use App\Models\BookmarkCategory;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('guest cannot view bookmarks list', function () {
    $response = $this->get(route('bookmarks.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated user can view bookmarks list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    BookmarkCategory::factory()->create(['user_id' => $user->id]);
    Bookmark::factory()->create(['user_id' => $user->id]);
    Tag::factory()->create(['user_id' => $user->id]);

    $response = $this->get(route('bookmarks.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Bookmarks/Index')
        ->has('bookmarks')
        ->has('categories')
        ->has('tags')
    );
});

test('authenticated user can create a bookmark and it dispatches background job', function () {
    Queue::fake();

    $user = User::factory()->create();
    $this->actingAs($user);

    $category = BookmarkCategory::factory()->create(['user_id' => $user->id]);

    $response = $this->post(route('bookmarks.store'), [
        'url' => 'https://laravel.com',
        'category_id' => $category->id,
        'tags' => ['framework', 'php', 'laravel'],
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('bookmarks', [
        'user_id' => $user->id,
        'url' => 'https://laravel.com',
        'category_id' => $category->id,
        'status' => 'pending',
    ]);

    $bookmark = Bookmark::where('url', 'https://laravel.com')->first();

    // Assert tags were synced correctly
    expect($bookmark->tags)->toHaveCount(3);
    $this->assertDatabaseHas('tags', ['user_id' => $user->id, 'name' => 'framework']);

    // Assert job was dispatched
    Queue::assertPushed(FetchBookmarkMetadataJob::class, function ($job) use ($bookmark) {
        return $job->bookmark->id === $bookmark->id;
    });
});

test('authenticated user can update a bookmark', function () {
    Queue::fake();

    $user = User::factory()->create();
    $this->actingAs($user);

    $bookmark = Bookmark::factory()->create([
        'user_id' => $user->id,
        'url' => 'https://old-url.com',
        'title' => 'Old Title',
    ]);

    $newCategory = BookmarkCategory::factory()->create(['user_id' => $user->id]);

    $response = $this->put(route('bookmarks.update', $bookmark), [
        'url' => 'https://new-url.com',
        'title' => 'New Title',
        'description' => 'Updated Description',
        'category_id' => $newCategory->id,
        'tags' => ['new-tag'],
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('bookmarks', [
        'id' => $bookmark->id,
        'url' => 'https://new-url.com',
        'title' => 'New Title',
        'description' => 'Updated Description',
        'category_id' => $newCategory->id,
    ]);

    $bookmark->refresh();
    expect($bookmark->tags)->toHaveCount(1);
    expect($bookmark->tags->first()->name)->toBe('new-tag');
});

test('authenticated user can delete a bookmark', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $bookmark = Bookmark::factory()->create(['user_id' => $user->id]);

    $response = $this->delete(route('bookmarks.destroy', $bookmark));

    $response->assertRedirect();
    $this->assertDatabaseMissing('bookmarks', ['id' => $bookmark->id]);
});

test('authenticated user can toggle bookmark favorite status', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $bookmark = Bookmark::factory()->create([
        'user_id' => $user->id,
        'is_favorite' => false,
    ]);

    $response = $this->patch(route('bookmarks.favorite', $bookmark));

    $response->assertRedirect();
    $this->assertTrue($bookmark->fresh()->is_favorite);
});

test('authenticated user can create a bookmark category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('bookmark-categories.store'), [
        'name' => 'Work Resources',
        'color' => '#ef4444',
        'icon' => 'Folder',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('bookmark_categories', [
        'user_id' => $user->id,
        'name' => 'Work Resources',
        'color' => '#ef4444',
        'icon' => 'Folder',
    ]);
});

test('authenticated user can update a bookmark category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = BookmarkCategory::factory()->create([
        'user_id' => $user->id,
        'name' => 'Old Category',
    ]);

    $response = $this->put(route('bookmark-categories.update', $category), [
        'name' => 'New Category',
        'color' => '#3b82f6',
        'icon' => 'Bookmark',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('bookmark_categories', [
        'id' => $category->id,
        'name' => 'New Category',
        'color' => '#3b82f6',
        'icon' => 'Bookmark',
    ]);
});

test('authenticated user can delete a bookmark category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = BookmarkCategory::factory()->create(['user_id' => $user->id]);

    $response = $this->delete(route('bookmark-categories.destroy', $category));

    $response->assertRedirect();
    $this->assertDatabaseMissing('bookmark_categories', ['id' => $category->id]);
});
