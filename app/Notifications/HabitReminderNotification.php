<?php

namespace App\Notifications;

use App\Models\Habit;
use App\Models\Setting;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HabitReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Habit $habit)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $token = Setting::getValue('telegram_bot_token');
        $chatId = Setting::getValue('telegram_chat_id');

        if ($token && $chatId) {
            $channels[] = TelegramChannel::class;
        }

        return $channels;
    }

    /**
     * Get the array representation of the notification for the database.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'habit_id' => $this->habit->id,
            'name' => $this->habit->name,
            'description' => $this->habit->description,
            'streak_current' => $this->habit->streak_current,
        ];
    }

    /**
     * Send the notification via Telegram.
     */
    public function toTelegramCustom(object $notifiable): void
    {
        $token = Setting::getValue('telegram_bot_token');
        $chatId = Setting::getValue('telegram_chat_id');

        if (! $token || ! $chatId) {
            return;
        }

        $text = "⏰ *PENGINGAT HABIT*\n\n"
              . "💪 Jangan lupa untuk menyelesaikan habit hari ini:\n"
              . "📌 *Nama*: {$this->habit->name}\n"
              . "🔥 *Streak*: {$this->habit->streak_current} hari\n";

        if ($this->habit->description) {
            $text .= "📝 *Deskripsi*: {$this->habit->description}\n";
        }

        try {
            Http::timeout(5)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown',
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send Telegram habit reminder: " . $e->getMessage());
        }
    }
}
