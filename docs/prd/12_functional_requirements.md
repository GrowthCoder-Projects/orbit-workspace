# 12. Functional Requirements

### 12.1 Core System Logic
*   **Authentication Flow:** The application must block all requests unless a valid Laravel Sanctum session is authenticated. Only a single user can register; subsequent registrations are disabled once the primary account is active.
*   **Single-User Guard:** Middleware checks that user database queries match user ID `1`. No endpoints should support multi-tenant query params.

### 12.2 Keyboard Navigation & Command Palette
*   **Global Command Palette (`Cmd+K` / `Ctrl+K`):** Instantly displays a modal popup containing a fuzzy search bar. This bar must search:
    1. Navigation routes (e.g., "Settings", "Kanban").
    2. Active projects, clients, tasks, and notes by title.
    3. Custom actions (e.g., "Add Expense", "New Task", "Lock Vault").
*   **Global Keyboard Shortcuts:**
    *   `g` then `t`: Go to Tasks.
    *   `g` then `n`: Go to Notes.
    *   `g` then `d`: Go to Dashboard.
    *   `Esc`: Close modals, drawers, or exit Focus Mode.
    *   `Cmd+S` (inside Notes/Wiki): Trigger manual save, bypassing auto-save debounces.

### 12.3 Markdown Parsing & Rendering
*   Notes and Knowledge Base editor views must support live parsing of Markdown text. Raw input like `### Header` must dynamically transition to styled headers. Code segments must implement syntax highlighting (prismjs or shiki).

### 12.4 Secure Local/S3 Storage
*   Document files and attachments must support storage in a local private directory or an S3 compatible storage array. File uploads must stream through chunked uploads if exceeding 10MB to prevent browser lock-ups.

### 12.5 Telegram Bot Notification Engine
*   A background daemon monitors scheduled events. Notifications are pushed to Telegram using a webhook endpoint. If transmission fails, the engine retries up to three times with exponential backoff before logging a failure.
