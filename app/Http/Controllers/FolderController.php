<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    /**
     * Store a newly created folder in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:folders,id'],
            'color' => ['nullable', 'string', 'max:50'],
        ]);

        Folder::create($validated);

        return back()->with('success', 'Folder created successfully.');
    }

    /**
     * Update the specified folder in storage.
     */
    public function update(Request $request, Folder $folder): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:folders,id'],
            'color' => ['nullable', 'string', 'max:50'],
        ]);

        $folder->update($validated);

        return back()->with('success', 'Folder updated successfully.');
    }

    /**
     * Remove the specified folder from storage.
     */
    public function destroy(Folder $folder): RedirectResponse
    {
        $folder->delete();

        return back()->with('success', 'Folder deleted successfully.');
    }
}
