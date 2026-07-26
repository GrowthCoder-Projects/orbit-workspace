<?php

namespace App\Listeners;

use App\Services\ActivityLogService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class LogAuthActivity
{
    /**
     * Handle user login events.
     */
    public function handleLogin(Login $event): void
    {
        ActivityLogService::log(
            action: 'login',
            description: "Login sebagai {$event->user->name}",
            subject: null,
            properties: [['field' => 'guard', 'old' => null, 'new' => $event->guard]],
        );
    }

    /**
     * Handle user logout events.
     */
    public function handleLogout(Logout $event): void
    {
        ActivityLogService::log(
            action: 'logout',
            description: 'Logout dari sesi aktif',
            subject: null,
        );
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<string, string>
     */
    public function subscribe(): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
        ];
    }
}
