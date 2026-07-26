<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    /**
     * Serve media/image files securely from storage disks.
     */
    public function show(Request $request, string $path): BinaryFileResponse
    {
        // Sanitize path against directory traversal
        if (str_contains($path, '..') || str_contains($path, "\0")) {
            abort(400, 'Invalid file path.');
        }

        $cleanPath = ltrim($path, '/\\');

        // Check in public disk first, then local private disk
        if (Storage::disk('public')->exists($cleanPath)) {
            $fullPath = Storage::disk('public')->path($cleanPath);
            $mimeType = Storage::disk('public')->mimeType($cleanPath);
        } elseif (Storage::disk('local')->exists($cleanPath)) {
            $fullPath = Storage::disk('local')->path($cleanPath);
            $mimeType = Storage::disk('local')->mimeType($cleanPath);
        } else {
            abort(404, 'Media file not found.');
        }

        return response()->file($fullPath, [
            'Content-Type' => $mimeType ?: 'application/octet-stream',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
