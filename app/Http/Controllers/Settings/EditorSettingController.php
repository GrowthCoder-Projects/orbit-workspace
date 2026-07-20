<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EditorSettingController extends Controller
{
    /**
     * Update the default notes editor setting.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'editor' => ['required', 'string', 'in:tiptap,ckeditor'],
        ]);

        Setting::setValue('notes_editor', $validated['editor']);

        return back()->with('success', 'Default editor preference updated.');
    }
}
