# 10. Module Specifications

---

## 10.1 Dashboard

### Purpose
Serves as the high-density landing view, aggregating vital indicators, quick entries, and immediate timelines for the developer's day.

### Features
*   **Greeting & Context:** Displays current date, day, and a random coding quote or motivational micro-text.
*   **Today's Schedule:** Integrated widget displaying today's calendar events and deadlines.
*   **Active Tasks Widget:** Checklist of tasks due today or overdue, with direct status toggle.
*   **Quick Notes Card:** Scratchpad area using a clean, auto-saving text field.
*   **Invoice Tracker Widget:** Summary of pending, paid, and overdue invoices.
*   **Statistics Ring:** Clean SVGs depicting monthly income vs. target, task completion ratios, and server health checks (via ping integrations).
*   **Quick Action Drawer:** Buttons for "New Task", "New Note", "New Invoice", "New Credential".

### User Flow
1. User loads `workspace.local`.
2. Views overdue tasks in the list, clicks the checkbox to mark one complete.
3. Types a quick link or thought into the Quick Notes card; data saves asynchronously on keypress pause (500ms debounce).
4. Clicks "New Invoice" in Quick Actions, opening the invoice draft overlay.

### Database Entities
No dedicated `Dashboard` table. It queries other modules (Tasks, Calendar, Invoices, Notes) using aggregated Eloquent relations.

### Validation Rules
*   *Quick Notes Debounced Save:* `content` -> `nullable|string|max:5000`

### Future Improvements
*   Integration with local system metrics (CPU/RAM of the server running Workspace).

### UX Recommendations
*   Utilize a modular CSS grid layout allowing drag-and-drop widget resizing.
*   Apply high-contrast text tags for deadlines (e.g., amber for < 48 hours, crimson for overdue).

---

## 10.2 Projects

### Purpose
Centralize code links, environments, production states, and tracking details for active developer contracts or side projects.

### Features
*   **Repository Integration:** Links to GitHub, GitLab, or local directories.
*   **Environment Overview:** Displays IP addresses, domain names, SSH ports, and links to production/staging environments.
*   **Milestones Tracker:** Interactive visual timeline showing delivery markers.
*   **Project-Specific Document Vault:** Direct list of documents tagged with this project.
*   **Progress Indicators:** Graphical progress bar calculated from task completion percentages.

### User Flow
1. User clicks "Projects" in the sidebar.
2. Selects "Workspace App" from the project list card.
3. Views staging URL, clicks the "Copy IP" button next to server environments.
4. Adds a new milestone: "Phase 1 Beta Launch" due in two weeks.

### Database Entities
*   `projects`: `id`, `name`, `slug`, `description`, `repository_url`, `production_url`, `staging_url`, `status` (active, archived, pipeline), `progress_percent`, `timestamps`
*   `project_milestones`: `id`, `project_id`, `title`, `due_date`, `completed_at`, `status` (pending, completed), `timestamps`

### Relationships
*   `Project` hasMany `Milestone`
*   `Project` belongsTo `Client` (nullable for personal projects)
*   `Project` hasMany `Task`
*   `Project` hasMany `Document`
*   `Project` hasMany `Credential`

### Validation Rules
*   `name`: `required|string|max:255`
*   `repository_url`: `nullable|url`
*   `production_url`: `nullable|url`
*   `staging_url`: `nullable|url`
*   `status`: `required|in:active,archived,pipeline`

### Future Improvements
*   Auto-fetch repository activity (commits, active branches) using GitHub REST APIs.

### UX Recommendations
*   Use a card-grid layout for the dashboard of projects with micro-logos generated from initials or favicon fetches of the production URLs.

---

## 10.3 Clients

### Purpose
Manage billing info, history, contact detail, and records of individuals or companies paying for developer services.

### Features
*   **Core Information Profile:** Client company name, representative name, business tax ID, address.
*   **Contact Info:** Direct email, phone, and messaging links (Telegram/Slack).
*   **Client Project History:** Lists all historical and ongoing projects.
*   **Invoicing Records:** Aggregates paid, unpaid, and total lifetime client value metrics.
*   **Personal Notes Area:** Simple Rich Text area for logging business terms, client preferences, or communications.

### User Flow
1. User navigates to Clients, clicks "Add Client".
2. Fills in tax details and email address.
3. Views the Client profile page to see all invoices associated with that client.

### Database Entities
*   `clients`: `id`, `name`, `company_name`, `email`, `phone`, `tax_id`, `billing_address`, `notes`, `timestamps`

### Relationships
*   `Client` hasMany `Project`
*   `Client` hasMany `Invoice`

### Validation Rules
*   `name`: `required|string|max:255`
*   `email`: `required|email|max:255`
*   `tax_id`: `nullable|string|max:100`

### Future Improvements
*   Integration with public company registry API to auto-fill billing addresses based on tax ID.

### UX Recommendations
*   A clean split-pane design: Left pane is list of clients, right pane shows selected client details, projects list, and billing charts.

---

## 10.4 Tasks

### Purpose
Provide high-fidelity project management using Kanban and List interfaces for tracking individual work items.

### Features
*   **Multi-View Support:** Seamless hotkey toggle between Kanban board (columns: Todo, In Progress, Blocked, Done) and a flat list.
*   **Rich Task Detail:** Supports priority weights (low, medium, high, urgent), custom labels, and due dates.
*   **Subtask Checklist:** Inner list within a task showing checkbox completions.
*   **Attachments & Comments:** Secure local uploads of design specs or logs, and a notes/comment thread.
*   **Recurring Tasks Engine:** Configurable settings to auto-regenerate tasks daily, weekly, or monthly.

### User Flow
1. User views Kanban board, drags "Refactor Auth" card from "Todo" to "In Progress".
2. Hits `Enter` on the card to open detail view; adds subtask: "Update Fortify routes".
3. Assigns label "Security" and sets due date to tomorrow.

### Database Entities
*   `tasks`: `id`, `project_id` (nullable), `title`, `description`, `status` (todo, in_progress, blocked, done), `priority` (low, medium, high, urgent), `due_date`, `is_recurring`, `recurrence_rule` (cron format/enum), `completed_at`, `timestamps`
*   `task_checklists`: `id`, `task_id`, `item_text`, `is_completed`, `sort_order`, `timestamps`
*   `task_attachments`: `id`, `task_id`, `file_path`, `file_name`, `file_size`, `timestamps`

### Relationships
*   `Task` belongsTo `Project` (nullable)
*   `Task` hasMany `TaskChecklist`
*   `Task` hasMany `TaskAttachment`

### Validation Rules
*   `title`: `required|string|max:255`
*   `status`: `required|in:todo,in_progress,blocked,done`
*   `priority`: `required|in:low,medium,high,urgent`
*   `due_date`: `nullable|date`

### Future Improvements
*   Automated ticket synchronization with external GitHub Issues.

### UX Recommendations
*   Utilize smooth drag-and-drop libraries (`@shopify/draggable` or custom HTML5 Drag/Drop in Vue).
*   Add a visual indicator of due date urgency via changing color borders on card faces.

---

## 10.5 Calendar

### Purpose
Visualizes time-based deliverables, client meetings, project launch schedules, and personal reminders on monthly, weekly, and daily grids.

### Features
*   **Unified Schedule:** Merges client milestones, task deadlines, and calendar events into one dashboard.
*   **Recurring Events Manager:** Set recurring calendar entries for weekly syncs or standups.
*   **Telegram integration:** Sends reminder notifications for upcoming calendar entries 15 minutes before the start time.

### User Flow
1. User navigates to Calendar, clicks a day square.
2. Form overlay prompts for event name: "Client Sync", start/end times, and reminder preference.
3. Saves, and the event populates the monthly grid instantly.

### Database Entities
*   `calendar_events`: `id`, `title`, `description`, `start_time`, `end_time`, `is_all_day`, `is_recurring`, `recurrence_rule`, `reminder_lead_time_minutes`, `timestamps`

### Relationships
*   `CalendarEvent` optionally belongsTo `Project` or `Client`

### Validation Rules
*   `title`: `required|string|max:255`
*   `start_time`: `required|date`
*   `end_time`: `required|date|after_or_equal:start_time`

### Future Improvements
*   Bidirectional CalDAV or Google Calendar sync to fetch phone-scheduled events automatically.

### UX Recommendations
*   A fluid, CSS grid-based layout that mimics the aesthetics of Apple Calendar or Sunrise calendar.
*   Allow resizing of events by dragging bottom edges on the week/day views.

---

## 10.6 Notes

### Purpose
Provide a markdown-native, folders-and-tags based writing module for quick thoughts, scratchpads, and unstructured ideas.

### Features
*   **Markdown WYSIWYG Editor:** Interleaved Markdown parsing using Milkdown or TipTap, showing clean formatting.
*   **Bidirectional Linking (Backlinks):** Mention notes using obsidian-like `[[Note Name]]` syntax.
*   **Folders & Tagging:** Nested hierarchies and search filters.
*   **Auto-save & Version History:** Periodic backups saved to PostgreSQL database as modifications happen.

### User Flow
1. User presses `Cmd+K`, types "New Note".
2. Typings start in full screen. User writes `# Redis Tuning Guidelines` and links it to `[[Workspace App]]`.
3. System automatically creates a backlink in the "Workspace App" project notes.

### Database Entities
*   `notes`: `id`, `title`, `slug`, `content`, `folder_id` (nullable), `is_archived`, `is_favorite`, `timestamps`
*   `folders`: `id`, `name`, `parent_id` (nullable), `timestamps`
*   `note_backlinks`: `id`, `source_note_id`, `target_note_id`, `timestamps`

### Relationships
*   `Note` belongsTo `Folder` (nullable)
*   `Note` belongsToMany `Tag`
*   `Note` hasMany `NoteBacklink` (as source and target)

### Validation Rules
*   `title`: `required|string|max:255`
*   `content`: `nullable|string`

### Future Improvements
*   Offline sync with LocalStorage backup if connection drops.

### UX Recommendations
*   Keep the page styling clean: center-focused reading lane, hidden sidebars, serif font option for deep reading, and a live word count.

---

## 10.7 Knowledge Base

### Purpose
Organize long-term technical documentation, server architectures, deployment scripts, and reference wikis.

### Features
*   **Structured Wiki Pages:** Hierarchical articles focusing on technical documentation.
*   **Snippet Repository:** Multi-language syntax-highlighted code drawer (supporting copy-to-clipboard).
*   **Deployment Manuals Checklist:** Steps for manual setups with integrated copy-to-clipboard blocks for terminal commands.

### User Flow
1. User navigates to Knowledge Base.
2. Clicks "Server Configs" subdirectory, opens "PostgreSQL Backup Script".
3. Copies the shell script directly via a single click of the code snippet copy button.

### Database Entities
*   `kb_articles`: `id`, `title`, `slug`, `content`, `parent_article_id` (nullable), `category` (deployment, server, logic, general), `timestamps`

### Relationships
*   `KbArticle` hasMany child articles (`parent_article_id` hierarchy)
*   `KbArticle` belongsToMany `Tag`

### Validation Rules
*   `title`: `required|string|max:255`
*   `content`: `required|string`
*   `category`: `required|string`

### Future Improvements
*   Import static documentation folders containing standard markdown directly from GitHub.

### UX Recommendations
*   A left-hand structural tree navigation, similar to GitBook or Readme.io, with a sticky right-side page table-of-contents.

---

## 10.8 Documents

### Purpose
Securely store and organize business contracts, invoices received, PDF reports, assets, and design files.

### Features
*   **Hierarchical File Manager:** Standard nesting folders.
*   **Inline File Previewer:** Native browser rendering of PDFs, images, text, and videos directly without downloading.
*   **Version Control History:** Allows uploading revisions to the same document file node.
*   **S3/Local Storage Adaptability:** Easily transition document physical storage files from local disk to S3 arrays via config.

### User Flow
1. User enters Documents page, navigates to `/Contracts/2026`.
2. Drags `retainer_agreement_v2.pdf` onto the window.
3. System uploads the file to `storage/documents`, creates database entry, and generates an image preview.

### Database Entities
*   `documents`: `id`, `name`, `file_path`, `mime_type`, `file_size`, `folder_id` (nullable), `project_id` (nullable), `version`, `timestamps`

### Relationships
*   `Document` belongsTo `Folder` (nullable)
*   `Document` belongsTo `Project` (nullable)

### Validation Rules
*   `file`: `required|file|max:51200` (50MB maximum size limit)
*   `name`: `required|string|max:255`

### Future Improvements
*   OCR engine (via Tesseract/local queues) to scan text within uploaded PDFs, making document contents searchable.

### UX Recommendations
*   Grid layout using folder/file icons with detailed attributes (size, date uploaded) and hover popups for instant previews.

---

## 10.9 Credentials

### Purpose
Provide a secure, locally encrypted vault for SSH keys, database credentials, API tokens, and server passwords.

### Features
*   **Data Encryption:** Sensitive database fields are automatically encrypted at rest using AES-256-GCM.
*   **Secret Masking:** Hidden fields by default; clicking eye icons or utilizing key bindings copies raw values to the clipboard.
*   **Expiration Warnings:** Track date profiles of tokens/keys and fire warning alerts when approaching dates.
*   **Cloudflare/GitHub Token Templates:** Forms designed to hold specific parameters for popular services.

### User Flow
1. User enters Credentials tab, inputs the primary application master password to unlock decryption.
2. Selects "GitHub Access Token", clicks the "Copy" icon.
3. The raw decrypted string copies to clipboard, and a notification logs the read in the Activity Log.

### Database Entities
*   `credentials`: `id`, `title`, `category` (ssh, api_key, server, db, cloudflare, github, other), `host` (nullable), `username` (nullable), `encrypted_secret`, `expires_at`, `notes`, `timestamps`

### Relationships
*   `Credential` optionally belongsTo `Project`

### Validation Rules
*   `title`: `required|string|max:255`
*   `encrypted_secret`: `required|string`
*   `category`: `required|in:ssh,api_key,server,db,cloudflare,github,other`

### Future Improvements
*   Browser extension integration to autofill credentials onto development sites.

### UX Recommendations
*   A strict security timeout: credentials automatically lock after 5 minutes of inactivity, requiring user re-authentication.

---

## 10.10 Finance

### Purpose
Track professional income, business expenses, software subscriptions, and tax reserves without accounting complexity.

### Features
*   **Double-Entry Log:** Record items as either income (credited) or expense (debited).
*   **Subscription Monitor:** Track recurring software fees, rendering monthly costs and renewal date reminders.
*   **Tax Projection Estimates:** Set a target tax withholding percentage (e.g., 30%) and display projected tax owed.
*   **Financial Aggregations:** Interactive charts for income vs. outflow on yearly/monthly splits.

### User Flow
1. User buys a subscription to an API, clicks "Add Expense".
2. Inputs: "OpenAI API", amount, select category "Software", check "Recurring Subscription".
3. System charts update to show new projected monthly run rates.

### Database Entities
*   `finance_transactions`: `id`, `type` (income, expense), `amount` (decimal 10,2), `category`, `transaction_date`, `is_recurring`, `recurrence_period` (monthly, annual), `client_id` (nullable), `description`, `timestamps`

### Relationships
*   `FinanceTransaction` belongsTo `Client` (nullable)

### Validation Rules
*   `type`: `required|in:income,expense`
*   `amount`: `required|numeric|min:0.01`
*   `transaction_date`: `required|date`

### Future Improvements
*   CSV/OFX transaction imports from banks.

### UX Recommendations
*   Use standard financial color semantics: green highlights for income, subtle muted shades for expenses. Avoid red overload to maintain a calm dashboard.

---

## 10.11 Invoice

### Purpose
Quick draft and creation of PDF client invoices, providing simple delivery and payment tracking.

### Features
*   **Line Item Draft Editor:** Real-time billing calculators for hours, flat-rate items, and taxes.
*   **PDF Compiler:** Clean, minimal PDF print-template generation using standard CSS layouts.
*   **Status Management:** Labels for "Draft", "Sent", "Overdue", "Paid".
*   **Direct Reference:** Links client profiles directly, pulling billing address and tax identification fields automatically.

### User Flow
1. User clicks "Generate Invoice" from Client profile.
2. Fills in hours: "15 hours at $100/hr", flat rate item: "Design asset kit".
3. Clicks "Preview PDF" to view output. Clicks "Finalize & Send" to flag it as "Sent" and export the PDF.

### Database Entities
*   `invoices`: `id`, `invoice_number` (unique string), `client_id`, `project_id` (nullable), `issue_date`, `due_date`, `tax_rate`, `subtotal`, `total`, `status` (draft, sent, overdue, paid), `timestamps`
*   `invoice_items`: `id`, `invoice_id`, `description`, `quantity`, `unit_price`, `total`, `timestamps`

### Relationships
*   `Invoice` belongsTo `Client`
*   `Invoice` belongsTo `Project` (nullable)
*   `Invoice` hasMany `InvoiceItem`

### Validation Rules
*   `invoice_number`: `required|string|unique:invoices`
*   `client_id`: `required|exists:clients,id`
*   `issue_date`: `required|date`
*   `due_date`: `required|date|after_or_equal:issue_date`

### Future Improvements
*   Automated email delivery with payment gateway integration (Stripe payment link embedding).

### UX Recommendations
*   The invoice editor should mirror Obsidian's live preview; clicking values lets the user type in-place, reducing form clutter.

---

## 10.12 Bookmarks

### Purpose
Save and organize programming documentation, reference guides, design tools, and educational videos.

### Features
*   **Categorized bookmark system:** Folders, tags, and source types (documentation, tool, video, article, other).
*   **Link Meta Fetcher:** Auto-scrapes page title, description, and preview icons when saving.
*   **Elastic Full-text Search:** Quick query match against bookmark descriptions and titles.

### User Flow
1. User pastes `https://laravel.com/docs/13.x/eloquent` into the quick-bookmark drawer.
2. Background task fetches metadata, saving title: "Eloquent ORM - Laravel" and tags it as "Documentation".
3. The bookmark appears in the dev bookmarks stack.

### Database Entities
*   `bookmarks`: `id`, `url`, `title`, `description`, `preview_image_url`, `type` (documentation, tool, video, article, other), `timestamps`

### Relationships
*   `Bookmark` belongsToMany `Tag`

### Validation Rules
*   `url`: `required|url|max:1000`
*   `title`: `nullable|string|max:500`

### Future Improvements
*   Chrome/Arc Browser extension to save bookmarks to Workspace in one click.

### UX Recommendations
*   A card grid layout showing rich snippet previews, titles, descriptions, and a quick "Open Link" arrow button.

---

## 10.13 Notifications

### Purpose
Aggregate in-app indicators and coordinate external alerts (Telegram, Email) for task deadlines and invoice statuses.

### Features
*   **Reminder Center:** A dedicated panel in the app displaying notification records.
*   **Notification Channels:**
    *   *In-App:* Toast alerts and indicator badges.
    *   *Telegram:* Bot integration pushing message summaries.
    *   *Email:* Periodic summaries or critical system notifications.
*   **Granular System Triggers:** Turn channels on/off for specific triggers.

### User Flow
1. User sets task deadline: "Deploy Beta" due at 5:00 PM.
2. At 4:45 PM, a scheduled cron task triggers.
3. System fires a Telegram message: `Reminder: "Deploy Beta" is due in 15 minutes.` and adds an in-app notification item.

### Database Entities
Uses Laravel's default `notifications` table structure:
*   `notifications`: `uuid`, `type`, `notifiable_type`, `notifiable_id`, `data` (JSON), `read_at`, `timestamps`

### Relationships
*   `Notification` morphsTo `Notifiable` (which is the single `User` model)

### Validation Rules
No standard input forms; internal controller validates configuration states.

### Future Improvements
*   Real-time pushes via WebSockets (Laravel Reverb) to instantly show in-app reminders without page refreshes.

### UX Recommendations
*   A clean slide-out notification center drawer from the top right, with single-click "Clear All" capability.

---

## 10.14 Activity Log

### Purpose
Provide a transparent, audit-ready timeline of all operations, mutations, and database queries performed inside the system.

### Features
*   **Log Everything:** Track creations, edits, deletions, login events, and credential access.
*   **Details Snapshot:** Store JSON payloader snapshots of record states before and after mutations.
*   **Activity Timeline:** High-density scrollable list showing chronological steps.

### User Flow
1. User deletes a client record.
2. The system triggers an observer, creating a row in the Activity Log detailing the deletion, timestamp, and user IP.
3. User visits Activity Log later to review when the action took place.

### Database Entities
*   `activity_logs`: `id`, `action` (created, updated, deleted, viewed_credentials, logged_in), `subject_type`, `subject_id`, `payload` (JSON), `ip_address`, `timestamps`

### Relationships
*   `ActivityLog` morphsTo `Subject` (polymorphic relationship to log actions against any model).

### Validation Rules
*   System generated. No direct user-facing forms.

### Future Improvements
*   Log exporting functionality in JSON and CSV formats for records matching search filters.

### UX Recommendations
*   Monospace-styled timeline items showing concise summaries: `[2026-07-14 15:40] CREDENTIALS: ssh-key-1 decrypted by user (IP: 127.0.0.1)`.

---

## 10.15 Settings

### Purpose
Provide control over security keys, profiles, interface preferences, backup executions, and external bot links.

### Features
*   **Profile Setup:** Single-user name, email, secure password settings.
*   **Theme Controls:** Toggle dark, light, or system themes.
*   **Telegram Bot Setup:** Credentials and test ping buttons to establish bot messaging channels.
*   **Manual & Auto Backup Configuration:** Trigger instant database dumps to configured S3 storage.

### User Flow
1. User navigates to Settings -> Integrations.
2. inputs Telegram Bot Token and chat ID, clicks "Send Test Notification".
3. Receives a verification message on Telegram, confirming connection.

### Database Entities
*   `settings`: `id`, `key` (unique string index), `value` (JSON/text), `timestamps`

### Validation Rules
*   `profile.email`: `required|email|max:255`
*   `settings.telegram_bot_token`: `nullable|string`

### Future Improvements
*   Integration with system services (e.g., Docker commands) to restart background queues directly from UI.

### UX Recommendations
*   Standard left-rail settings tabs (Profile, Theme, Integrations, Backups) with modern CSS transition states.
