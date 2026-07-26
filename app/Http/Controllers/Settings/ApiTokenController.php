<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiTokenController extends Controller
{
    /**
     * Display a listing of user API tokens.
     */
    public function index(Request $request): Response
    {
        $tokens = $request->user()->tokens()
            ->latest('created_at')
            ->get()
            ->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at?->diffForHumans() ?? 'Never',
                'created_at' => $token->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('settings/ApiTokens', [
            'tokens' => $tokens,
            'plainTextToken' => session('plainTextToken'),
        ]);
    }

    /**
     * Create a new API token.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $token = $request->user()->createToken($validated['name']);

        return back()->with('plainTextToken', $token->plainTextToken);
    }

    /**
     * Delete the given API token.
     */
    public function destroy(Request $request, int $tokenId): RedirectResponse
    {
        $request->user()->tokens()->where('id', $tokenId)->delete();

        return back();
    }
}
