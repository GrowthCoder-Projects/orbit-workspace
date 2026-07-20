# 🗺️ Development Roadmap — Workspace OS

> Dokumen ini berisi urutan prioritas pengembangan modul berdasarkan dependensi antar modul, nilai bisnis, dan kemudahan implementasi.
> 
> **Terakhir diperbarui:** 2026-07-15  
> **Status aktif:** Phase 1 (Foundation)

---

## ✅ Sudah Selesai

| Modul | Fitur yang sudah ada |
|---|---|
| **Tasks / Kanban** | Kanban board, List view, drag & drop, sub-task checklist, priority & status, filter & search |
| **Settings** | Profile, Security (2FA, Passkey), Backups (S3), Integrations (Telegram) |

---

## 📐 Prinsip Urutan Prioritas

1. **Dependensi** — Modul yang menjadi *foreign key* modul lain harus dibuat terlebih dahulu
2. **Nilai Harian** — Modul yang sering digunakan sehari-hari didahulukan
3. **Kompleksitas** — Modul sederhana (mandiri) sebelum modul kompleks (banyak relasi)
4. **Business Value** — Modul yang menghasilkan output nyata (Invoice, Finance) diutamakan setelah fondasinya siap

---

## 🚦 Phase 1 — Foundation (Infrastruktur Data)

> **Goal:** Membangun entitas inti yang menjadi fondasi relasi seluruh aplikasi.

### 🔷 Step 1 — Projects Module (10.2)

**Prioritas: 🔴 Tertinggi**

Alasan: `Project` adalah *foreign key* yang dipakai oleh Tasks, Invoices, Documents, Credentials, dan Milestones. Membangun ini dulu membuka koneksi data ke hampir semua modul lainnya.

**Yang perlu dibangun:**
- [ ] Migration: `projects`, `project_milestones`
- [ ] Model: `Project`, `ProjectMilestone` + Factories + Seeders
- [ ] Controller: `ProjectController` (CRUD)
- [ ] Inertia Pages: Index (card grid), Show (detail + milestones), Create/Edit
- [ ] Features:
  - Card grid dengan favicon/initials avatar dari production URL
  - Status label: `active`, `archived`, `pipeline`
  - Milestones tracker dengan visual timeline
  - Progress bar dari task completion
  - Link ke repository URL, production URL, staging URL
  - Copy IP / URL button
- [ ] Tests: Feature tests untuk CRUD Projects & Milestones

---

### 🔷 Step 2 — Clients Module (10.3)

**Prioritas: 🔴 Tinggi**

Alasan: `Client` adalah *foreign key* untuk `Invoice` dan `FinanceTransaction`. Harus ada sebelum modul keuangan dibangun.

**Yang perlu dibangun:**
- [ ] Migration: `clients`
- [ ] Model: `Client` + Factory + Seeder
- [ ] Controller: `ClientController` (CRUD)
- [ ] Inertia Pages: Index (split-pane list + detail), Show, Create/Edit
- [ ] Features:
  - Split-pane layout: list kiri, detail kanan
  - Info: nama, perusahaan, email, telepon, tax ID, billing address
  - Rich Text notes area
  - Aggregasi invoices: paid, unpaid, overdue, lifetime value
  - Riwayat project yang terkait
- [ ] Tests: Feature tests untuk CRUD Clients

---

## 🧠 Phase 2 — Knowledge & Productivity

> **Goal:** Modul harian yang meningkatkan produktivitas developer tanpa bergantung ke Phase 1.

### 🔷 Step 3 — Notes Module (10.6)

**Prioritas: 🟠 Tinggi**

Alasan: Modul mandiri, tidak bergantung ke Projects/Clients. Sering digunakan sehari-hari. Relatif cepat dibangun.

**Yang perlu dibangun:**
- [ ] Migration: `notes`, `folders`, `note_backlinks`
- [ ] Model: `Note`, `Folder`, `NoteBacklink` + Factories
- [ ] Controller: `NoteController`, `FolderController` (CRUD)
- [ ] Inertia Pages: Index (sidebar folder + note list), Editor (full-screen)
- [ ] Features:
  - WYSIWYG Markdown Editor (TipTap atau Milkdown)
  - Auto-save dengan debounce 500ms
  - Folder & tag hierarchy
  - `[[Note Name]]` backlink syntax
  - Favorite & archive toggle
  - Full-text search
- [ ] Tests: Feature tests Notes & Folders

---

### 🔷 Step 4 — Knowledge Base Module (10.7)

**Prioritas: 🟠 Sedang-Tinggi**

Alasan: Berguna untuk dokumentasi teknis jangka panjang. Mirip dengan Notes namun lebih terstruktur (hierarki artikel, kategori).

**Yang perlu dibangun:**
- [ ] Migration: `kb_articles`
- [ ] Model: `KbArticle` + Factory
- [ ] Controller: `KbArticleController` (CRUD)
- [ ] Inertia Pages: Index (tree navigation kiri + konten kanan), Show, Create/Edit
- [ ] Features:
  - Tree navigation kiri (seperti GitBook/Readme.io)
  - Table of contents sticky di kanan
  - Code snippet dengan copy-to-clipboard
  - Kategori: `deployment`, `server`, `logic`, `general`
  - Syntax highlighting
- [ ] Tests: Feature tests KB Articles

---

### 🔷 Step 5 — Bookmarks Module (10.12)

**Prioritas: 🟡 Sedang**

Alasan: Mandiri, cepat dibangun, berguna harian untuk developer yang sering menyimpan referensi.

**Yang perlu dibangun:**
- [ ] Migration: `bookmarks`, `tags`, `bookmark_tag` (pivot)
- [ ] Model: `Bookmark`, `Tag` + Factories
- [ ] Controller: `BookmarkController` (CRUD)
- [ ] Inertia Pages: Index (card grid dengan rich preview)
- [ ] Features:
  - Auto-fetch metadata (title, description, favicon) saat paste URL — via queued Job
  - Card grid dengan rich snippet preview
  - Filter by type: `documentation`, `tool`, `video`, `article`, `other`
  - Tag system
  - Full-text search
- [ ] Tests: Feature tests Bookmarks

---

### 🔷 Step 6 — Habit Tracker Module (10.15)

**Prioritas: 🟡 Sedang**

Alasan: Modul produktivitas mandiri yang tidak bergantung pada Projects maupun Clients. Menambah nilai penggunaan harian aplikasi.

**Yang perlu dibangun:**
- [ ] Migration: `habits`, `habit_logs`
- [ ] Model: `Habit`, `HabitLog` + Factories
- [ ] Controller: `HabitController` (CRUD)
- [ ] Inertia Pages: `Habits/Index` (checklist harian + layout grid heatmap), `Habits/CreateEdit` (overlay/modal)
- [ ] Features:
  - Form habit dengan opsi frekuensi: harian, hari tertentu (Mon, Wed, Fri), atau jumlah mingguan
  - Kalkulasi otomatis streak (streak saat ini dan streak terpanjang) secara real-time
  - GitHub-style contributions calendar/grid untuk melacak riwayat 365 hari ke belakang
  - Catatan penyelesaian debounced opsional untuk log harian
- [ ] Tests: Feature tests CRUD Habits & HabitLogs, kalkulasi streak

---

## 📅 Phase 3 — Scheduling & Time Management

> **Goal:** Modul yang membutuhkan Projects & Clients sudah ada.

### 🔷 Step 7 — Calendar Module (10.5)

**Prioritas: 🟠 Sedang-Tinggi**

Alasan: Bergantung ke Projects & Clients untuk menampilkan milestone dan deadline. Harus dibangun setelah Phase 1 selesai.

**Yang perlu dibangun:**
- [ ] Migration: `calendar_events`
- [ ] Model: `CalendarEvent` + Factory
- [ ] Controller: `CalendarEventController` (CRUD)
- [ ] Inertia Pages: Index (monthly/weekly/daily grid), Create/Edit overlay
- [ ] Features:
  - Monthly, weekly, daily view (CSS grid native)
  - Recurring events (daily, weekly, monthly)
  - Unified schedule: task deadlines + project milestones + calendar events
  - Reminder lead time (Telegram notification 15 menit sebelum)
  - Drag-to-reschedule (opsional, Phase berikutnya)
- [ ] Tests: Feature tests Calendar Events

---

## 💼 Phase 4 — Business & Finance

> **Goal:** Modul keuangan. Membutuhkan Clients & Projects sudah ada.

### 🔷 Step 8 — Finance Module (10.10)

**Prioritas: 🟠 Sedang**

Alasan: Mengelola seluruh aspek keuangan pribadi secara komprehensif, mulai dari pencatatan transaksi harian, pengelolaan aset & utang, hingga analitik investasi.

**Yang perlu dibangun:**
- [ ] Migration: `finance_accounts`, `finance_transactions`, `finance_budgets`, `finance_savings`, `finance_goals`, `finance_investments`, `finance_assets`, `finance_liabilities`, `finance_categories`
- [ ] Model: `FinanceAccount`, `FinanceTransaction`, `FinanceBudget`, `FinanceSaving`, `FinanceGoal`, `FinanceInvestment`, `FinanceAsset`, `FinanceLiability`, `FinanceCategory` + Factories
- [ ] Controller: `FinanceDashboardController`, `FinanceAccountController`, `FinanceTransactionController`, `FinanceBudgetController`, `FinanceSavingController`, `FinanceGoalController`, `FinanceInvestmentController`, `FinanceAssetController`, `FinanceLiabilityController`, `FinanceCategoryController`
- [ ] Inertia Pages:
  - `Finance/Dashboard` (Financial Overview, Net Worth, Cash Flow, Health Score, Upcoming Bills)
  - `Finance/Accounts` (Cash, Bank, E-Wallet, Credit Card, Investment, Transfer)
  - `Finance/Transactions` (Ledger, Income/Expense/Transfer CRUD, Split, Notes/Tags, Search/Filter)
  - `Finance/Budget` (Monthly & Category Budgets, Budget Progress & Alerts)
  - `Finance/Savings` & `Finance/Goals` (Savings Target, Goals Progress & Recommendations)
  - `Finance/Investments` (Stocks, Mutual Funds, Gold, Crypto, Bonds, Portfolio ROI)
  - `Finance/Assets` & `Finance/Liabilities` (Asset List & Depreciation, Loans/PayLater/Installments)
  - `Finance/Reports` (Spending Trends, Saving Trends, Net Worth Growth)
- [ ] Features:
  - Dashboard keuangan komprehensif & Health Score
  - Dukungan multi-rekening & transfer antar rekening
  - Transaksi berulang (recurring), transaksi terpisah (split), lampiran resi, pencarian, dan tag
  - Anggaran bulanan dan per kategori dengan sistem peringatan
  - Tabungan berganda & pelacakan target tanggal pencapaian
  - Portofolio investasi multi-aset (saham, reksa dana, emas, kripto) dengan perhitungan ROI
  - Daftar aset & depresiasi nilai, serta manajemen liabilitas (Loan, PayLater, Cicilan)
  - Otomatisasi (auto reminder, scheduled transaction)
- [ ] Tests: Feature tests untuk modul Finance (Accounts, Transactions, Budgets, Savings, Goals, Investments, Assets, Liabilities)

---

### 🔷 Step 9 — Invoice Module (10.11)

**Prioritas: 🟠 Sedang**

Alasan: Bergantung ke `Client` dan optionally `Project`. Berjalan beriringan dengan Finance.

**Yang perlu dibangun:**
- [ ] Migration: `invoices` (termasuk kolom untuk `template_name`, `color_accent`, dan logo), `invoice_items`
- [ ] Model: `Invoice`, `InvoiceItem` + Factories
- [ ] Controller: `InvoiceController` (CRUD), `InvoicePdfController` (rendering PDF berdasarkan pilihan template)
- [ ] Inertia Pages: Index (status list), Show (live preview editor dengan pemilih template), Create/Edit
- [ ] Features:
  - Line item editor (jam × tarif, flat rate, pajak)
  - Pilihan preset template (e.g. `modern`, `classic`) dengan dynamic CSS styling (warna aksen & kustomisasi logo)
  - Live preview template interaktif di halaman show/edit
  - Status: `draft`, `sent`, `overdue`, `paid`
  - PDF compiler (CSS print template yang responsif terhadap pilihan preset)
  - Auto-pull client billing address & tax ID
  - Invoice numbering otomatis
- [ ] Tests: Feature tests Invoices untuk memverifikasi CRUD, rendering template PDF, dan validasi data template.

---

## 🔐 Phase 5 — Files

### 🔷 Step 10 — Documents Module (10.8)

Prioritas: 🟡 Sedang

Alasan: File manager dengan upload dan preview. Bergantung ke Projects/Folders.

Yang perlu dibangun:
- [ ] Migration: `documents`, `document_folders`
- [ ] Model: `Document`, `DocumentFolder` + Factories
- [ ] Controller: `DocumentController` (CRUD + upload), `DocumentFolderController`
- [ ] Inertia Pages: Index (grid file manager), Show (inline preview)
- [ ] Features:
  - Drag-and-drop upload
  - Folder hierarchy
  - Inline preview: PDF, image, video (native browser rendering)
  - Version history (upload revisi ke node yang sama)
  - S3 / local storage adaptable via `config/filesystems.php`
- [ ] Tests: Feature tests Documents (mock Storage)

---

## 🏠 Phase 6 — Dashboard & System

### 🔷 Step 11 — Dashboard Module (10.1)

Prioritas: 🟡 Terakhir

Alasan: Dashboard mengagregasi data dari **semua** modul lain. Harus dibangun paling akhir agar semua widget bisa menampilkan data nyata.

Yang perlu dibangun:
- [ ] Controller: `DashboardController@index` (aggregated queries)
- [ ] Inertia Page: `Dashboard.vue` (widget grid)
- [ ] Features:
  - Greeting + tanggal + motivational quote (random)
  - Today's Schedule widget (Calendar + Task deadlines hari ini)
  - Active Tasks widget (tasks overdue + due today + status toggle)
  - Quick Notes card (auto-save dengan debounce)
  - Invoice Tracker widget (pending, paid, overdue summary)
  - Statistics Ring SVG (monthly income vs target, task ratio)
  - Quick Action Drawer: New Task, New Note, New Invoice
- [ ] Tests: Feature tests Dashboard (aggregated data assertions)

---

### 🔷 Step 12 — Notifications & Activity Log (10.13 + 10.14)

Prioritas: 🟢 Penyempurnaan

Alasan: Melengkapi sistem secara keseluruhan. Activity Log bisa ditambahkan secara incremental via Model Observers.

Yang perlu dibangun:
- [ ] Migration: `activity_logs` (jika belum pakai `notifications` table Laravel)
- [ ] Model: `ActivityLog` + Observer base class
- [ ] Controller: `NotificationController`, `ActivityLogController`
- [ ] Service: `ActivityLogService` (dipanggil dari semua controller penting)
- [ ] Inertia Pages: Notification slide-out drawer, Activity Log timeline
- [ ] Features:
  - In-app toast + badge indicator
  - Telegram bot push notification
  - Activity timeline dengan monospace font
  - Log untuk: login, create/update/delete
- [ ] Tests: Feature tests Notifications

---

## 📊 Ringkasan Urutan

```
Phase 1 (Foundation)
  Step 1 → Projects
  Step 2 → Clients

Phase 2 (Knowledge & Productivity)
  Step 3 → Notes
  Step 4 → Knowledge Base
  Step 5 → Bookmarks
  Step 6 → Habit Tracker

Phase 3 (Scheduling)
  Step 7 → Calendar

Phase 4 (Business & Finance)
  Step 8 → Finance
  Step 9 → Invoice

Phase 5 (Files)
  Step 10 → Documents

Phase 6 (System)
  Step 11 → Dashboard (full)
  Step 12 → Notifications + Activity Log
```

---

## 🎨 Catatan UX Global

- Setiap modul harus menggunakan komponen **Shadcn/Vue** yang sudah tersedia
- Gunakan **Wayfinder** untuk semua route dari frontend ke backend
- Tambahkan **Inertia deferred props** untuk data berat (charts, aggregasi)
- Setiap halaman index harus punya **empty state** yang jelas dan menarik
- Warna status harus konsisten: 🔵 In Progress, 🔴 Blocked/Overdue, 🟢 Done/Paid, ⚫ Draft/Todo
