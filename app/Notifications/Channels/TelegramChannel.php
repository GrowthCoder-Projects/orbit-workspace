<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;

class TelegramChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (method_exists($notification, 'toTelegramCustom')) {
            $notification->toTelegramCustom($notifiable);
        }
    }
}
