<?php

use App\Models\Folder;
use App\Models\Note;
use App\Models\Setting;
use App\Models\User;

test('guest cannot view notes', function () {
    $response = $this->get(route('notes.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated user can view notes list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Folder::factory()->create(['user_id' => $user->id]);
    Note::factory()->create(['user_id' => $user->id]);

    $response = $this->get(route('notes.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Notes/Index')
        ->has('notes')
        ->has('folders')
        ->has('defaultEditor')
    );
});

test('authenticated user can create a note', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('notes.store'), [
        'title' => 'Test Note Idea',
    ]);

    $this->assertDatabaseHas('notes', [
        'user_id' => $user->id,
        'title' => 'Test Note Idea',
    ]);

    $note = Note::where('title', 'Test Note Idea')->first();

    $response->assertRedirect(route('notes.index', ['id' => $note->id]));
});

test('authenticated user can update a note', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $note = Note::factory()->create([
        'user_id' => $user->id,
        'title' => 'Old Title',
        'content' => 'Old content.',
    ]);

    $response = $this->patch(route('notes.update', $note), [
        'title' => 'New Title',
        'content' => '<p>New content is here.</p>',
        'is_favorite' => true,
    ]);

    $response->assertRedirect(route('notes.index', ['id' => $note->id]));

    $this->assertDatabaseHas('notes', [
        'id' => $note->id,
        'title' => 'New Title',
        'content' => '<p>New content is here.</p>',
        'is_favorite' => true,
    ]);
});

test('note backlinks are synchronized on content update', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $noteTarget = Note::factory()->create([
        'user_id' => $user->id,
        'title' => 'Target Page',
    ]);

    $noteSource = Note::factory()->create([
        'user_id' => $user->id,
        'title' => 'Source Page',
        'content' => 'No links yet.',
    ]);

    // Update source note to contain backlink [[Target Page]]
    $this->patch(route('notes.update', $noteSource), [
        'title' => $noteSource->title,
        'content' => '<p>Check out this page [[Target Page]] for details.</p>',
    ]);

    // Assert backlink relation exists in database
    $this->assertDatabaseHas('note_backlinks', [
        'source_note_id' => $noteSource->id,
        'target_note_id' => $noteTarget->id,
    ]);

    // Assert relationships resolve correctly on models
    expect($noteSource->fresh()->outgoingLinks->contains($noteTarget))->toBeTrue();
    expect($noteTarget->fresh()->backlinks->contains($noteSource))->toBeTrue();
});

test('authenticated user can delete a note', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $note = Note::factory()->create(['user_id' => $user->id]);

    $response = $this->delete(route('notes.destroy', $note));

    $response->assertRedirect(route('notes.index'));
    $this->assertDatabaseMissing('notes', ['id' => $note->id]);
});

test('authenticated user can create a folder', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('folders.store'), [
        'name' => 'Design System',
        'color' => '#ef4444',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('folders', [
        'user_id' => $user->id,
        'name' => 'Design System',
        'color' => '#ef4444',
    ]);
});

test('authenticated user can update folder preference', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $folder = Folder::factory()->create([
        'user_id' => $user->id,
        'name' => 'Old Folder Name',
    ]);

    $response = $this->patch(route('folders.update', $folder), [
        'name' => 'New Folder Name',
        'color' => '#3b82f6',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('folders', [
        'id' => $folder->id,
        'name' => 'New Folder Name',
        'color' => '#3b82f6',
    ]);
});

test('authenticated user can update default editor preference', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->patch(route('settings.editor.update'), [
        'editor' => 'ckeditor',
    ]);

    $response->assertRedirect();

    // Setting getValue is scoped globally to authenticated user
    expect(Setting::getValue('notes_editor'))->toBe('ckeditor');
});
