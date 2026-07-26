<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $showDailyWelcome = false;
        if ($request->user()) {
            if (! $request->session()->has('daily_welcome_shown')) {
                $showDailyWelcome = true;
                $request->session()->put('daily_welcome_shown', true);
            }
        }

        $logoPath = Setting::getValue('app_logo');
        $iconPath = Setting::getValue('app_icon');
        $hasCustomLogo = ! empty($logoPath) && $logoPath !== 'logo/logo-orbit.png';
        $appLogoUrl = $logoPath ? asset('storage/'.$logoPath) : asset('storage/logo/logo-orbit.png');
        $appIconUrl = $iconPath ? asset('storage/'.$iconPath) : asset('storage/logo/icon-workspace.png');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'showDailyWelcome' => $showDailyWelcome,
            ],
            'settings' => [
                'app_logo' => $appLogoUrl,
                'app_icon' => $appIconUrl,
                'is_custom_logo' => $hasCustomLogo,
                'notes_editor' => $request->user() ? Setting::getValue('notes_editor', 'tiptap') : 'tiptap',
                'enabled_modules' => $request->user()
                    ? Setting::getValue('enabled_modules', [
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
                    ])
                    : [],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];

    }
}
