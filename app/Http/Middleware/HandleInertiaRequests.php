<?php

namespace App\Http\Middleware;

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

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'showDailyWelcome' => $showDailyWelcome,
            ],
            'settings' => [
                'notes_editor' => $request->user() ? \App\Models\Setting::getValue('notes_editor', 'tiptap') : 'tiptap',
                'enabled_modules' => $request->user()
                    ? \App\Models\Setting::getValue('enabled_modules', [
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

