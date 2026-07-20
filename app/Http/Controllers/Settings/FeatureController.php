<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeatureController extends Controller
{
    /**
     * Show the features settings page.
     */
    public function edit(): Response
    {
        $enabledModules = Setting::getValue('enabled_modules', [
            'projects' => true,
            'tasks' => true,
            'clients' => true,
            'invoices' => true,
            'calendar' => true,
            'finance' => true,
            'habits' => true,
            'documents' => true,
            'notes' => true,
            'kb' => true,
            'bookmarks' => true,
        ]);

        return Inertia::render('settings/Features', [
            'enabledModules' => $enabledModules,
        ]);
    }

    /**
     * Update the features settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'projects' => 'required|boolean',
            'tasks' => 'required|boolean',
            'clients' => 'required|boolean',
            'invoices' => 'required|boolean',
            'calendar' => 'required|boolean',
            'finance' => 'required|boolean',
            'habits' => 'required|boolean',
            'documents' => 'required|boolean',
            'notes' => 'required|boolean',
            'kb' => 'required|boolean',
            'bookmarks' => 'required|boolean',
        ]);

        Setting::setValue('enabled_modules', $validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Pengaturan fitur berhasil diperbarui.'),
        ]);

        return back();
    }
}
