<?php

namespace App\Notifications;

use App\Models\CalendarEvent;
use App\Models\Setting;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalendarEventReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public CalendarEvent $event)
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
            'event_id' => $this->event->id,
            'title' => $this->event->title,
            'description' => $this->event->description,
            'start_at' => $this->event->start_at->toIso8601String(),
            'end_at' => $this->event->end_at->toIso8601String(),
            'color' => $this->event->color,
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

        $time = $this->event->start_at->format('H:i');
        $date = $this->event->start_at->format('d-m-Y');

        $text = "🔔 *PENGINGAT JADWAL*\n\n"
              . "📌 *Judul*: {$this->event->title}\n"
              . "📅 *Tanggal*: {$date}\n"
              . "⏰ *Waktu*: {$time}\n";

        if ($this->event->description) {
            $text .= "📝 *Deskripsi*: {$this->event->description}\n";
        }

        try {
            Http::timeout(5)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown',
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send Telegram calendar reminder: " . $e->getMessage());
        }
    }
}
