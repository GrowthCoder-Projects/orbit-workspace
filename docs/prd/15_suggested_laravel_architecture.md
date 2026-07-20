# 15. Suggested Laravel Architecture

The backend utilizes **Laravel 13** and follows clean architectural patterns, keeping controllers slim and encapsulating business logic inside service classes.

```
┌──────────────────────────────────────────────────────────┐
│                     Inertia Views                        │
└────────────────────────────┬─────────────────────────────┘
                             │ (XHR / Page Visits)
                             ▼
┌──────────────────────────────────────────────────────────┐
│                      Route / Web                         │
└────────────────────────────┬─────────────────────────────┘
                             │
                             ▼
┌──────────────────────────────────────────────────────────┐
│                   Controller Layer                       │
│    (Invokes Request Validations, Delegates to Services)  │
└────────────────────────────┬─────────────────────────────┘
                             │
                             ▼
┌──────────────────────────────────────────────────────────┐
│                    Service Layer                         │
│  (Contains business logic, updates, file writes, etc.)  │
└──────────────────────┬────────────────────────────┬──────┘
                       │                            │
                       ▼                            ▼
┌──────────────────────────────┐            ┌──────────────┐
│       Database / Eloquent    │            │ Queue Jobs   │
└──────────────────────────────┘            └──────────────┘
```

### Route Layout (`routes/web.php`)
*   **Auth Routes:** Handles login validation using Fortify.
*   **App Routes (Protected by `auth` middleware):**
    *   `/dashboard`: Dashboard view.
    *   `/projects`: Resource endpoints for Projects and Milestones.
    *   `/clients`: Resource endpoints for Clients.
    *   `/tasks`: Kanban updates, task lists, attachments.
    *   `/calendar`: Event listings.
    *   `/notes`: Full-text/backlink editors.
    *   `/kb`: Documentation management.
    *   `/documents`: Explorer layout and S3 stream files.
    *   `/credentials`: Security endpoints, encrypting values via controller commands.
    *   `/finance`: Balance logs.
    *   `/invoices`: Compilation vectors and PDF builders.
    *   `/bookmarks`: Saving crawlers.
    *   `/settings`: Integrations mapping.

### Service Layer Core Packages
*   `App\Services\CredentialService`: Encapsulates secret encryption/decryption using the configured `App_Key` string.
*   `App\Services\InvoicePdfService`: Formulates HTML vectors using Blade and executes PDF conversions via Chromium driver wrappers (Spatie Browsershot).
*   `App\Services\BookmarkCrawlerService`: Evaluates web pages via cURL parser wrappers to pull link headers, OG images, and page descriptions.
