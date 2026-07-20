# 14. Suggested Database Domains

### Schema Layout

```
users
- id (BIGINT, PK)
- name (VARCHAR)
- email (VARCHAR, UNIQUE)
- password (VARCHAR)
- timestamps

projects
- id (BIGINT, PK)
- client_id (BIGINT, FK -> clients.id, nullable)
- name (VARCHAR)
- slug (VARCHAR, UNIQUE)
- repository_url (VARCHAR, nullable)
- production_url (VARCHAR, nullable)
- staging_url (VARCHAR, nullable)
- status (VARCHAR: active, archived, pipeline)
- progress_percent (INT, default 0)
- timestamps

clients
- id (BIGINT, PK)
- name (VARCHAR)
- company_name (VARCHAR, nullable)
- email (VARCHAR)
- phone (VARCHAR, nullable)
- tax_id (VARCHAR, nullable)
- billing_address (TEXT, nullable)
- notes (TEXT, nullable)
- timestamps

tasks
- id (BIGINT, PK)
- project_id (BIGINT, FK -> projects.id, nullable)
- title (VARCHAR)
- description (TEXT, nullable)
- status (VARCHAR: todo, in_progress, blocked, done)
- priority (VARCHAR: low, medium, high, urgent)
- due_date (DATE, nullable)
- is_recurring (BOOLEAN, default false)
- recurrence_rule (VARCHAR, nullable)
- completed_at (TIMESTAMP, nullable)
- timestamps

task_checklists
- id (BIGINT, PK)
- task_id (BIGINT, FK -> tasks.id)
- item_text (VARCHAR)
- is_completed (BOOLEAN, default false)
- sort_order (INT, default 0)
- timestamps

task_attachments
- id (BIGINT, PK)
- task_id (BIGINT, FK -> tasks.id)
- file_path (VARCHAR)
- file_name (VARCHAR)
- file_size (INT)
- timestamps

calendar_events
- id (BIGINT, PK)
- project_id (BIGINT, FK -> projects.id, nullable)
- title (VARCHAR)
- description (TEXT, nullable)
- start_time (TIMESTAMP)
- end_time (TIMESTAMP)
- is_all_day (BOOLEAN, default false)
- is_recurring (BOOLEAN, default false)
- recurrence_rule (VARCHAR, nullable)
- reminder_lead_time_minutes (INT, nullable)
- timestamps

notes
- id (BIGINT, PK)
- folder_id (BIGINT, FK -> folders.id, nullable)
- title (VARCHAR)
- slug (VARCHAR, UNIQUE)
- content (TEXT, nullable)
- is_archived (BOOLEAN, default false)
- is_favorite (BOOLEAN, default false)
- timestamps

folders
- id (BIGINT, PK)
- name (VARCHAR)
- parent_id (BIGINT, FK -> folders.id, nullable)
- timestamps

note_backlinks
- id (BIGINT, PK)
- source_note_id (BIGINT, FK -> notes.id)
- target_note_id (BIGINT, FK -> notes.id)
- timestamps

kb_articles
- id (BIGINT, PK)
- title (VARCHAR)
- slug (VARCHAR, UNIQUE)
- content (TEXT)
- parent_article_id (BIGINT, FK -> kb_articles.id, nullable)
- category (VARCHAR: deployment, server, logic, general)
- timestamps

documents
- id (BIGINT, PK)
- folder_id (BIGINT, FK -> folders.id, nullable)
- project_id (BIGINT, FK -> projects.id, nullable)
- name (VARCHAR)
- file_path (VARCHAR)
- mime_type (VARCHAR)
- file_size (INT)
- version (INT, default 1)
- timestamps

credentials
- id (BIGINT, PK)
- project_id (BIGINT, FK -> projects.id, nullable)
- title (VARCHAR)
- category (VARCHAR: ssh, api_key, server, db, cloudflare, github, other)
- host (VARCHAR, nullable)
- username (VARCHAR, nullable)
- encrypted_secret (TEXT)
- expires_at (DATE, nullable)
- notes (TEXT, nullable)
- timestamps

finance_transactions
- id (BIGINT, PK)
- client_id (BIGINT, FK -> clients.id, nullable)
- type (VARCHAR: income, expense)
- amount (DECIMAL 10,2)
- category (VARCHAR)
- transaction_date (DATE)
- is_recurring (BOOLEAN, default false)
- recurrence_period (VARCHAR: monthly, annual, nullable)
- description (TEXT, nullable)
- timestamps

invoices
- id (BIGINT, PK)
- client_id (BIGINT, FK -> clients.id)
- project_id (BIGINT, FK -> projects.id, nullable)
- invoice_number (VARCHAR, UNIQUE)
- issue_date (DATE)
- due_date (DATE)
- tax_rate (DECIMAL 5,2, default 0.00)
- subtotal (DECIMAL 10,2)
- total (DECIMAL 10,2)
- status (VARCHAR: draft, sent, overdue, paid)
- timestamps

invoice_items
- id (BIGINT, PK)
- invoice_id (BIGINT, FK -> invoices.id)
- description (VARCHAR)
- quantity (DECIMAL 10,2)
- unit_price (DECIMAL 10,2)
- total (DECIMAL 10,2)
- timestamps

bookmarks
- id (BIGINT, PK)
- url (VARCHAR)
- title (VARCHAR, nullable)
- description (TEXT, nullable)
- preview_image_url (VARCHAR, nullable)
- type (VARCHAR: documentation, tool, video, article, other)
- timestamps

tags
- id (BIGINT, PK)
- name (VARCHAR, UNIQUE)
- timestamps

taggables
- tag_id (BIGINT, FK -> tags.id)
- taggable_id (BIGINT)
- taggable_type (VARCHAR)

activity_logs
- id (BIGINT, PK)
- action (VARCHAR)
- subject_id (BIGINT, nullable)
- subject_type (VARCHAR, nullable)
- payload (JSON, nullable)
- ip_address (VARCHAR, nullable)
- timestamps

settings
- id (BIGINT, PK)
- key (VARCHAR, UNIQUE)
- value (JSON, nullable)
- timestamps
```
