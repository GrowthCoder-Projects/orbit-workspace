<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LogoController extends Controller
{
    /**
     * Update the application logo.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
        ]);

        $oldPath = Setting::getValue('app_logo');

        if ($oldPath && $oldPath !== 'logo/logo-orbit.png' && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $path = $request->file('logo')->store('logo', 'public');
        Setting::setValue('app_logo', $path);

        return back()->with('success', 'App logo updated successfully.');
    }

    /**
     * Reset the application logo to default.
     */
    public function destroy(): RedirectResponse
    {
        $oldPath = Setting::getValue('app_logo');

        if ($oldPath && $oldPath !== 'logo/logo-orbit.png' && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        Setting::where('key', 'app_logo')->delete();

        return back()->with('success', 'App logo reset to default.');
    }
}
