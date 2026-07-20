# 13. Non Functional Requirements

### 13.1 Performance
*   **Lighthouse Metrics:** Achieve 90+ across Performance, Accessibility, and Best Practices.
*   **Server Response Latency:** Inertia requests must complete in under 100ms for read operations and under 200ms for writes on local development environments.
*   **Optimistic UI Updates:** Task checking, bookmark additions, and quick edits must update the UI state instantly before receiving server status confirmations, rolling back gracefully if transactions fail.

### 13.2 Security
*   **Field-Level Encryption:** `encrypted_secret` column values in the `credentials` table must be encrypted using PHP’s OpenSSL extension configured with AES-256-GCM. The encryption key must derive from the environment `APP_KEY` or a user-provided master password.
*   **Security Lockout:** Decrypted secrets in memory or temporary screens must clear and lock after 5 minutes of browser focus inactivity.

### 13.3 Accessibility
*   **WCAG 2.1 AA Compliance:** Full keyboard focus trapping on modal components, semantic HTML structures, and a minimum contrast ratio of 4.5:1 on text elements.
*   **Tab Navigation:** Ensure the entire application interface can be fully navigated and operated using only standard `Tab`, `Shift+Tab`, and `Enter` inputs.

### 13.4 Scalability & Caching
*   **Query Indexing:** All primary foreign key columns, status enums, and slugs must have indexing constraints in PostgreSQL.
*   **Redis Integration:** Cache settings records, static articles, and active configurations in Redis. Utilize cache invalidation listeners on model updates.

### 13.5 Logging & Diagnostics
*   **Laravel Pail Integration:** Ensure easy tailing of application log profiles directly within active terminal queues.
*   **Log Rotation:** Local application log files must enforce maximum storage rules (daily rotation, keeping at most 7 days of logs).

### 13.6 Backup & Recovery
*   **Automated Backups:** Daily cron script executes `pg_dump`, encrypts the resulting SQL payload, and exports it to the configured S3 backup directory.
*   **Storage Limits:** Retain daily backups for 7 days, weekly for 4 weeks, and monthly for 12 months.
