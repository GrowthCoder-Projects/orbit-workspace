<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\DocumentVersion;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): InertiaResponse
    {
        $search = $request->input('search');
        $currentFolderId = $request->input('folder_id');
        $currentProjectId = $request->input('project_id');

        // Fetch breadcrumbs
        $breadcrumbs = [];
        if ($currentFolderId) {
            $folder = DocumentFolder::with('parent')->find($currentFolderId);
            $tempFolder = $folder;
            while ($tempFolder) {
                array_unshift($breadcrumbs, [
                    'id' => $tempFolder->id,
                    'name' => $tempFolder->name,
                ]);
                $tempFolder = $tempFolder->parent;
            }
        }

        // Folders query
        $foldersQuery = DocumentFolder::query();

        // If folder is specified, get children of that folder
        if ($currentFolderId) {
            $foldersQuery->where('parent_id', $currentFolderId);
        } else {
            // Otherwise, get root folders
            $foldersQuery->whereNull('parent_id');
        }

        if ($currentProjectId) {
            $foldersQuery->where('project_id', $currentProjectId);
        }

        if ($search) {
            $foldersQuery->where('name', 'like', "%{$search}%");
        }

        $folders = $foldersQuery->orderBy('name')->get();

        // Documents query
        $documentsQuery = Document::with(['latestVersion', 'project', 'folder']);

        if ($currentFolderId) {
            $documentsQuery->where('folder_id', $currentFolderId);
        } else {
            $documentsQuery->whereNull('folder_id');
        }

        if ($currentProjectId) {
            $documentsQuery->where('project_id', $currentProjectId);
        }

        if ($search) {
            $documentsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $documents = $documentsQuery->orderBy('name')->get();

        return Inertia::render('Documents/Index', [
            'folders' => $folders,
            'documents' => $documents,
            'breadcrumbs' => $breadcrumbs,
            'projects' => Project::orderBy('name')->get(['id', 'name']),
            'allFolders' => DocumentFolder::orderBy('name')->get(['id', 'name', 'parent_id']),
            'currentFolderId' => $currentFolderId ? (int) $currentFolderId : null,
            'currentProjectId' => $currentProjectId ? (int) $currentProjectId : null,
            'filters' => [
                'search' => $search,
                'project_id' => $currentProjectId ? (int) $currentProjectId : null,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'], // 50MB limit
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'folder_id' => ['nullable', 'exists:document_folders,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        $uploadedFile = $request->file('file');
        $originalName = $uploadedFile->getClientOriginalName();
        $mimeType = $uploadedFile->getMimeType();
        $fileSize = $uploadedFile->getSize();

        $documentName = $request->input('name') ?: pathinfo($originalName, PATHINFO_FILENAME);

        // Store file in private local disk
        $filePath = $uploadedFile->store('documents', 'local');

        // Create document record
        $document = Document::create([
            'folder_id' => $request->folder_id,
            'project_id' => $request->project_id,
            'name' => $documentName,
            'description' => $request->description,
        ]);

        // Create version 1 record
        $document->versions()->create([
            'version' => 1,
            'file_path' => $filePath,
            'file_name' => $originalName,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
        ]);

        return back()->with('success', 'Document uploaded successfully.');
    }

    /**
     * Store a new version for an existing document.
     */
    public function storeVersion(Request $request, Document $document): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'], // 50MB limit
        ]);

        $uploadedFile = $request->file('file');
        $originalName = $uploadedFile->getClientOriginalName();
        $mimeType = $uploadedFile->getMimeType();
        $fileSize = $uploadedFile->getSize();

        // Store file in private local disk
        $filePath = $uploadedFile->store('documents', 'local');

        $latestVersionNumber = $document->latestVersion ? $document->latestVersion->version : 0;

        // Create new version record
        $document->versions()->create([
            'version' => $latestVersionNumber + 1,
            'file_path' => $filePath,
            'file_name' => $originalName,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
        ]);

        return back()->with('success', 'New version uploaded successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Document $document): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'folder_id' => ['nullable', 'exists:document_folders,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        $document->update($validated);

        return back()->with('success', 'Document updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Document $document): RedirectResponse
    {
        // Eloquent deleting hook handles version records and physical file deletions
        $document->delete();

        return back()->with('success', 'Document deleted successfully.');
    }

    /**
     * Preview the specified version of the document.
     */
    public function preview(DocumentVersion $version): BinaryFileResponse
    {
        if (!Storage::disk('local')->exists($version->file_path)) {
            abort(404, 'File not found on storage.');
        }

        $path = Storage::disk('local')->path($version->file_path);

        return response()->file($path, [
            'Content-Type' => $version->mime_type,
            'Content-Disposition' => 'inline; filename="' . $version->file_name . '"',
        ]);
    }

    /**
     * Download the specified version of the document.
     */
    public function download(DocumentVersion $version): BinaryFileResponse
    {
        if (!Storage::disk('local')->exists($version->file_path)) {
            abort(404, 'File not found on storage.');
        }

        $path = Storage::disk('local')->path($version->file_path);

        return response()->download($path, $version->file_name);
    }
}
