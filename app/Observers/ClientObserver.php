<?php

namespace App\Observers;

use App\Models\Client;
use App\Services\ActivityLogService;

class ClientObserver
{
    public function created(Client $client): void
    {
        ActivityLogService::created($client, $client->name);
    }

    public function updated(Client $client): void
    {
        ActivityLogService::updated($client, $client->name);
    }

    public function deleted(Client $client): void
    {
        ActivityLogService::deleted($client, $client->name);
    }
}
