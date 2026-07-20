<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationController extends Controller
{
    /**
     * Show the integrations settings page.
     */
    public function edit(): Response
    {
        return Inertia::render('settings/Integrations', [
            'telegramBotToken' => Setting::getValue('telegram_bot_token'),
            'telegramChatId' => Setting::getValue('telegram_chat_id'),
            'status' => session('status'),
        ]);
    }

    /**
     * Update the Telegram integration settings.
     */
    public function updateTelegram(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'telegram_bot_token' => 'nullable|string|max:255',
            'telegram_chat_id' => 'nullable|string|max:255',
        ]);

        Setting::setValue('telegram_bot_token', $validated['telegram_bot_token']);
        Setting::setValue('telegram_chat_id', $validated['telegram_chat_id']);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Telegram settings updated successfully.'),
        ]);

        return back();
    }

    /**
     * Send a test notification to Telegram.
     */
    public function testTelegram(): RedirectResponse
    {
        $token = Setting::getValue('telegram_bot_token');
        $chatId = Setting::getValue('telegram_chat_id');

        if (! $token || ! $chatId) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Please configure and save both Bot Token and Chat ID before testing.'),
            ]);

            return back();
        }

        try {
            $response = Http::timeout(5)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => '🔔 '.__('Test notification from Growth Coder Workspace! Your integration is working perfectly.'),
            ]);

            if ($response->successful()) {
                Inertia::flash('toast', [
                    'type' => 'success',
                    'message' => __('Test notification sent successfully to Telegram!'),
                ]);
            } else {
                $errorData = $response->json();
                $errorMessage = $errorData['description'] ?? __('Unknown Telegram API error.');

                Inertia::flash('toast', [
                    'type' => 'error',
                    'message' => __('Telegram Error: :message', ['message' => $errorMessage]),
                ]);
            }
        } catch (\Exception $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Failed to connect to Telegram API: :message', ['message' => $e->getMessage()]),
            ]);
        }

        return back();
    }
}
