<?php

namespace App\Http\Controllers;

use App\Services\DailyQuoteService;
use App\Services\DailyWelcomeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DailyWelcomeController extends Controller
{
    public function __construct(
        protected DailyWelcomeService $welcomeService,
        protected DailyQuoteService $quoteService
    ) {}

    /**
     * Get summary information for the daily welcome modal.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->welcomeService->getDailyWelcomeData($request->user());

        return response()->json($data);
    }

    /**
     * Get a random quote for shuffle action in modal.
     */
    public function randomQuote(Request $request): JsonResponse
    {
        $tryExternal = $request->boolean('try_external', false);
        $quote = $this->quoteService->getRandomQuote($tryExternal);

        return response()->json([
            'quote' => $quote,
        ]);
    }
}
