<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
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

        if (! ($enabledModules[$module] ?? true)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Modul :module dinonaktifkan.', ['module' => ucfirst($module)]),
            ]);

            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
