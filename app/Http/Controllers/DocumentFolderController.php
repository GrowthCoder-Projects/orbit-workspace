<?php

namespace App\Http\Controllers;

use App\Models\DocumentFolder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DocumentFolderController extends Controller
{
    /**
     * Store a newly created folder in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:document_folders,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        DocumentFolder::create($validated);

        return back()->with('success', 'Folder created successfully.');
    }

    /**
     * Update the specified folder in storage.
     */
    public function update(Request $request, DocumentFolder $folder): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:document_folders,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        $folder->update($validated);

        return back()->with('success', 'Folder updated successfully.');
    }

    /**
     * Remove the specified folder from storage.
     */
    public function destroy(DocumentFolder $folder): RedirectResponse
    {
        $folder->delete();

        return back()->with('success', 'Folder deleted successfully.');
    }
}
