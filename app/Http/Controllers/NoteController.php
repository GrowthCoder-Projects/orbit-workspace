<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Note;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): InertiaResponse
    {
        $userId = auth()->id();

        // Get folders with note counts
        $folders = Folder::withCount('notes')->get();

        // Get notes with backlinks and outgoing links
        $notes = Note::with(['folder', 'backlinks', 'outgoingLinks'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($note) {
                // Add a text preview snippet
                $note->snippet = Str::limit(strip_tags($note->content ?? ''), 120);
                return $note;
            });

        // Get user default editor preference
        $defaultEditor = Setting::getValue('notes_editor', 'tiptap');

        return Inertia::render('Notes/Index', [
            'folders' => $folders,
            'notes' => $notes,
            'defaultEditor' => $defaultEditor,
            'selectedNoteId' => $request->query('id') ? (int) $request->query('id') : null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'folder_id' => ['nullable', 'exists:folders,id'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $note = Note::create([
            'folder_id' => $validated['folder_id'] ?? null,
            'title' => $validated['title'] ?? 'Untitled Note',
            'content' => '',
            'is_favorite' => false,
            'is_archived' => false,
        ]);

        return redirect()->route('notes.index', ['id' => $note->id])
            ->with('success', 'Note created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Note $note): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'folder_id' => ['nullable', 'exists:folders,id'],
            'is_favorite' => ['nullable', 'boolean'],
            'is_archived' => ['nullable', 'boolean'],
        ]);

        $note->update($validated);

        // Sync backlinks when content changes
        if ($request->has('content')) {
            $this->syncBacklinks($note);
        }

        return redirect()->route('notes.index', ['id' => $note->id])
            ->with('success', 'Note updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Note $note): RedirectResponse
    {
        $note->delete();

        return redirect()->route('notes.index')
            ->with('success', 'Note deleted successfully.');
    }

    /**
     * Upload an image from the WYSIWYG editor.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:10240'], // Max 10MB
        ]);

        if ($request->file('image')) {
            $path = $request->file('image')->store('notes', 'local');
            $filename = basename($path);

            return response()->json([
                'url' => route('notes.images.show', ['filename' => $filename]),
            ]);
        }

        return response()->json(['error' => 'No file uploaded.'], 400);
    }

    /**
     * Serve notes images securely from private storage.
     */
    public function showImage(string $filename)
    {
        $path = "notes/{$filename}";

        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $filePath = Storage::disk('local')->path($path);
        $mimeType = Storage::disk('local')->mimeType($path);

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    protected function syncBacklinks(Note $note): void
    {
        // Decode HTML entities and strip tags to get pure plain text
        $plainText = html_entity_decode(strip_tags($note->content ?? ''), ENT_QUOTES, 'UTF-8');

        // Normalize whitespace characters (convert non-breaking spaces, tabs, etc. to regular spaces)
        $plainText = preg_replace('/\s+/u', ' ', $plainText);

        // Match [[Note Title]] syntax
        preg_match_all('/\[\[(.*?)\]\]/', $plainText, $matches);

        $targetTitles = array_unique(array_filter(array_map('trim', $matches[1] ?? [])));

        if (empty($targetTitles)) {
            $note->outgoingLinks()->sync([]);
            return;
        }

        // Find matching notes case-insensitively, scoped to current user
        $targetNotes = Note::whereIn(\DB::raw('LOWER(title)'), array_map('strtolower', $targetTitles))
            ->where('id', '!=', $note->id) // Avoid self links
            ->get();

        $note->outgoingLinks()->sync($targetNotes->pluck('id'));
    }
}
