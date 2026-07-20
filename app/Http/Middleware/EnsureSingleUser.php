<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleUser
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('workspace.single_user')) {
            $user = $request->user();
            if ($user && $user->id !== 1) {
                abort(403, 'Unauthorized action in single-user mode.');
            }
        }

        return $next($request);
    }
}
