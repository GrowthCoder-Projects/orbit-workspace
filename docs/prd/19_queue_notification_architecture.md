# 19. Queue & Notification Architecture

```
                      ┌───────────────────────┐
                      │    Trigger Event      │
                      │ (e.g. Due Task Alert) │
                      └───────────┬───────────┘
                                  │
                                  ▼
                      ┌───────────────────────┐
                      │  Laravel Dispatcher   │
                      └───────────┬───────────┘
                                  │
                                  ▼
                      ┌───────────────────────┐
                      │     Redis Queue       │
                      │ (Laravel Horizon App) │
                      └───────────┬───────────┘
                                  │
         ┌────────────────────────┼────────────────────────┐
         ▼                        ▼                        ▼
┌─────────────────┐      ┌─────────────────┐      ┌─────────────────┐
│ Database Queue  │      │ Telegram Queue  │      │   Email Queue   │
│ (In-App Alerts) │      │  (Bot Messages) │      │  (Mail Trans.)  │
└─────────────────┘      └─────────────────┘      └─────────────────┘
```

*   **Queue Engine:** Powered by **Redis** and managed via **Laravel Horizon** for full dashboard metrics.
*   **Job Queues:**
    1.  `default`: Basic data processing (e.g. processing image uploads).
    2.  `notifications`: Delivers emails and Telegram webhooks.
    3.  `backups`: Processes database dumps and executes S3 transfers.
*   **Notification Dispatching:** Implements Laravel’s `Notification` system. Classes extend `ShouldQueue` to move notifications to the background worker pool, preventing UI blocking.
