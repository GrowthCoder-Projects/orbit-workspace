<?php

use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\DocumentVersion;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('guest cannot view documents list', function () {
    $response = $this->get(route('documents.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated user can view document list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $project = Project::factory()->create();
    $folder = DocumentFolder::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
    $doc = Document::factory()->create(['user_id' => $user->id, 'folder_id' => $folder->id, 'project_id' => $project->id]);
    DocumentVersion::factory()->create(['document_id' => $doc->id]);

    $response = $this->get(route('documents.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Documents/Index')
        ->has('folders')
        ->has('documents')
        ->has('breadcrumbs')
        ->has('projects')
    );
});

test('authenticated user can create a document folder', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('document-folders.store'), [
        'name' => 'Project Specifications',
        'project_id' => null,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('document_folders', [
        'user_id' => $user->id,
        'name' => 'Project Specifications',
    ]);
});

test('authenticated user can rename a folder', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $folder = DocumentFolder::factory()->create(['user_id' => $user->id, 'name' => 'Old Folder Name']);

    $response = $this->patch(route('document-folders.update', $folder), [
        'name' => 'New Folder Name',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('document_folders', [
        'id' => $folder->id,
        'name' => 'New Folder Name',
    ]);
});

test('authenticated user can delete a folder recursively', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Storage::fake('local');

    $folder = DocumentFolder::factory()->create(['user_id' => $user->id]);
    $doc = Document::factory()->create(['user_id' => $user->id, 'folder_id' => $folder->id]);
    $version = DocumentVersion::factory()->create(['document_id' => $doc->id, 'file_path' => 'documents/test.txt']);

    // Create a fake physical file
    Storage::disk('local')->put($version->file_path, 'dummy content');

    $response = $this->delete(route('document-folders.destroy', $folder));

    $response->assertRedirect();
    $this->assertDatabaseMissing('document_folders', ['id' => $folder->id]);
    $this->assertDatabaseMissing('documents', ['id' => $doc->id]);
    $this->assertDatabaseMissing('document_versions', ['id' => $version->id]);

    // Verify physical file was deleted
    Storage::disk('local')->assertMissing($version->file_path);
});

test('authenticated user can upload a document', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Storage::fake('local');

    $file = UploadedFile::fake()->create('contract.pdf', 1024, 'application/pdf');

    $response = $this->post(route('documents.store'), [
        'file' => $file,
        'name' => 'Client Contract',
        'description' => 'Signed contract copy',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('documents', [
        'user_id' => $user->id,
        'name' => 'Client Contract',
        'description' => 'Signed contract copy',
    ]);

    $document = Document::where('name', 'Client Contract')->first();
    $this->assertDatabaseHas('document_versions', [
        'document_id' => $document->id,
        'version' => 1,
        'file_name' => 'contract.pdf',
        'mime_type' => 'application/pdf',
    ]);

    $version = $document->latestVersion;
    Storage::disk('local')->assertExists($version->file_path);
});

test('authenticated user can upload a new version', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Storage::fake('local');

    $doc = Document::factory()->create(['user_id' => $user->id]);
    $v1 = DocumentVersion::factory()->create(['document_id' => $doc->id, 'version' => 1]);

    $newFile = UploadedFile::fake()->create('contract_v2.pdf', 2048, 'application/pdf');

    $response = $this->post(route('documents.versions.store', $doc), [
        'file' => $newFile,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('document_versions', [
        'document_id' => $doc->id,
        'version' => 2,
        'file_name' => 'contract_v2.pdf',
    ]);

    $v2 = DocumentVersion::where('document_id', $doc->id)->where('version', 2)->first();
    Storage::disk('local')->assertExists($v2->file_path);
});

test('authenticated user can update and relocate a document', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $folder = DocumentFolder::factory()->create(['user_id' => $user->id]);
    $doc = Document::factory()->create(['user_id' => $user->id, 'folder_id' => null]);

    $response = $this->patch(route('documents.update', $doc), [
        'name' => 'Relocated Document',
        'description' => 'Moved to a subfolder',
        'folder_id' => $folder->id,
        'project_id' => null,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('documents', [
        'id' => $doc->id,
        'name' => 'Relocated Document',
        'description' => 'Moved to a subfolder',
        'folder_id' => $folder->id,
    ]);
});

test('authenticated user can preview and download files', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Storage::fake('local');

    $doc = Document::factory()->create(['user_id' => $user->id]);
    $version = DocumentVersion::factory()->create([
        'document_id' => $doc->id,
        'file_path' => 'documents/contract.pdf',
        'file_name' => 'contract.pdf',
        'mime_type' => 'application/pdf',
    ]);

    Storage::disk('local')->put($version->file_path, 'mock pdf content');

    // Test preview
    $previewResponse = $this->get(route('documents.preview', $version));
    $previewResponse->assertOk();
    $previewResponse->assertHeader('Content-Type', 'application/pdf');

    // Test download
    $downloadResponse = $this->get(route('documents.download', $version));
    $downloadResponse->assertOk();
    $downloadResponse->assertHeader('Content-Disposition', 'attachment; filename=contract.pdf');
});
