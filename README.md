<div align="center">

# 🪐 Orbit

### **Open by Nature. Organized by Design.**

An open-source, self-hostable, all-in-one personal & professional workspace management system developed and maintained by **Growth Coder Project**.

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Maintained by: Growth Coder Project](https://img.shields.io/badge/Maintained%20by-Growth%20Coder%20Project-007ACC.svg)](#)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?logo=vuedotjs&logoColor=white)](https://vuejs.org)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-3.x-9553E8?logo=inertia&logoColor=white)](https://inertiajs.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-v4-06B6D4?logo=tailwindcss&logoColor=white)](https://tailwindcss.com)

<br />

<!-- Navigation Tabs -->
<p align="center">
  <a href="#-overview"><b>Overview</b></a> &nbsp;•&nbsp;
  <a href="#-key-features--modules"><b>Features & Modules</b></a> &nbsp;•&nbsp;
  <a href="#%EF%B8%8F-technology-stack"><b>Tech Stack</b></a> &nbsp;•&nbsp;
  <a href="#-quick-start-guide"><b>Quick Start</b></a> &nbsp;•&nbsp;
  <a href="#-testing--code-quality"><b>Testing & Quality</b></a> &nbsp;•&nbsp;
  <a href="#-directory-structure"><b>Architecture</b></a> &nbsp;•&nbsp;
  <a href="docs/api.md"><b>REST API Docs</b></a> &nbsp;•&nbsp;
  <a href="#-contributing"><b>Contributing</b></a>
</p>

---

</div>

## 📌 Overview

**Orbit** is an open-source workspace management system developed by **Growth Coder Project**, designed to unify all facets of work and life management into a single, cohesive, modern web application. Whether you are running software projects, tracking clients and invoices, managing personal finances, organizing notes, or maintaining daily habits — Orbit brings everything into your line of sight.

Built with a commitment to **privacy, performance, and modern developer experience**, Orbit is fully open-source and easy to self-host.

---

## ✨ Key Features & Modules

<details open>
<summary><b>🎯 Projects & Milestones</b></summary>
<br />

- Centralized project catalog with live site previews, production/staging URLs, and IP address management.
- Visual milestone timelines and automatic completion ratio metrics.
- Auto-fetched website favicons and quick links to repositories.
</details>

<details open>
<summary><b>👥 Client Management (CRM)</b></summary>
<br />

- Split-pane interface for effortless client browsing and details viewing.
- Track business contacts, tax IDs, billing addresses, and rich text notes.
- Real-time aggregations for paid, unpaid, and overdue invoices alongside client lifetime value (LTV).
</details>

<details open>
<summary><b>📋 Tasks & Kanban</b></summary>
<br />

- Interactive Kanban board and customizable List views.
- Drag-and-drop task movement across customizable statuses.
- Sub-task checklists, priority badges (`Low`, `Medium`, `High`, `Urgent`), and multi-attribute filters.
</details>

<details open>
<summary><b>📝 Notes & Knowledge Base</b></summary>
<br />

- WYSIWYG and Markdown rich-text editor powered by TipTap.
- Networked thought organization with `[[Note Name]]` backlink syntax.
- Structured Knowledge Base articles with sticky table of contents, folder hierarchies, and syntax-highlighted code blocks.
</details>

<details open>
<summary><b>💼 Finance & Wealth Management</b></summary>
<br />

- Comprehensive multi-account ledger (Bank, Cash, E-Wallet, Investment, Crypto).
- Budget tracking with automated alert thresholds.
- Multi-asset investment portfolio tracking with real-time ROI calculations.
- Asset depreciation schedules and liability tracking (Loans, PayLater, Installments).
</details>

<details open>
<summary><b>🧾 Invoicing System</b></summary>
<br />

- Dynamic line-item billing calculator (hourly rates, fixed rates, taxes).
- Interactive template customization (`Modern`, `Classic`) with color accent pickers and logo uploads.
- Automated client billing detail sync and responsive PDF generation.
</details>

<details open>
<summary><b>🔥 Habit Tracker</b></summary>
<br />

- Daily habit checklists with flexible scheduling (daily, specific weekdays, target counts).
- Automatic streak calculation (current vs. longest streaks).
- GitHub-style 365-day contribution heatmaps.
</details>

<details open>
<summary><b>🔖 Bookmarks Vault</b></summary>
<br />

- Queued background job metadata scraper (automatically extracts page title, description, and favicon).
- Rich preview cards, tag filtering, and fast full-text searching.
</details>

<details open>
<summary><b>📅 Unified Calendar</b></summary>
<br />

- Aggregated timeline bringing together task deadlines, project milestones, and custom calendar events into monthly, weekly, and daily views.
</details>

<details open>
<summary><b>🔒 Security & Infrastructure</b></summary>
<br />

- **Fortify Authentication**: Passkeys (WebAuthn), Two-Factor Authentication (2FA / TOTP), and active session control.
- **Backups**: Automated database and file snapshot management powered by Spatie Backup (Local / S3).
</details>

---

## 🛠️ Technology Stack

Orbit leverages the latest bleeding-edge technologies for maximum speed, type safety, and maintainability:

| Layer | Technologies Used |
| :--- | :--- |
| **Backend Framework** | [Laravel 13](https://laravel.com) (PHP 8.3+) |
| **Authentication & Security** | [Laravel Fortify](https://laravel.com/docs/fortify), Passkeys (WebAuthn), 2FA |
| **Frontend Framework** | [Vue 3](https://vuejs.org) (Composition API, `<script setup>`), TypeScript |
| **SPA Bridge** | [Inertia.js v3](https://inertiajs.com) |
| **Routing Bridge** | [Laravel Wayfinder](https://github.com/laravel/wayfinder) |
| **Styling & UI Components** | [Tailwind CSS v4](https://tailwindcss.com), [Shadcn Vue](https://www.shadcn-vue.com) / Reka UI, Lucide Icons |
| **Rich Editors** | TipTap Editor, CKEditor 5 |
| **Charts & Visualization** | Chart.js, Vue-ChartJS |
| **Testing & Quality** | Pest v4, PHPStan (Larastan), Laravel Pint, ESLint, Prettier |

---

## 🚀 Quick Start Guide

<details open>
<summary><b>📋 System Prerequisites</b></summary>
<br />

Ensure your environment meets the following requirements:
- **PHP** >= 8.3 (with `pdo`, `mbstring`, `openssl`, `curl`, `gd`, `zip` extensions)
- **Composer** >= 2.x
- **Node.js** >= 20.x & **NPM** / **PNPM**
- **Database Engine**: PostgreSQL, MySQL 8+, or SQLite 3
</details>

<br />

<details open>
<summary><b>⚙️ Step-by-Step Installation</b></summary>
<br />

1. **Clone the Repository**
   ```bash
   git clone https://github.com/GrowthCoder-Projects/orbit-workspace.git
   cd orbit-workspace
   ```

2. **Install PHP Dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js Dependencies**
   ```bash
   npm install
   ```

4. **Set Up Environment File**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure Database & Storage**
   Update your database credentials in `.env`:
   ```env
   DB_CONNECTION=sqlite
   # Or for PostgreSQL / MySQL:
   # DB_CONNECTION=pgsql
   # DB_HOST=127.0.0.1
   # DB_PORT=5432
   # DB_DATABASE=orbit
   # DB_USERNAME=root
   # DB_PASSWORD=secret
   ```

6. **Run Database Migrations & Seeders**
   ```bash
   php artisan migrate --seed
   ```

7. **Link Storage**
   ```bash
   php artisan storage:link
   ```

8. **Start the Development Server**
   Orbit includes a helper command to run the Laravel server, queue worker, and Vite dev server concurrently:
   ```bash
   composer run dev
   ```

   Alternatively, run them separately:
   ```bash
   # Terminal 1: Application Server
   php artisan serve

   # Terminal 2: Queue Listener
   php artisan queue:listen

   # Terminal 3: Vite Dev Server
   npm run dev
   ```

9. **Access Application**
   Open your browser and navigate to `http://localhost:8000`.
</details>

---

## 🧪 Testing & Code Quality

Orbit maintains high code quality standards through automated testing and strict static analysis.

```bash
# Run feature & unit test suites with Pest
php artisan test

# Check code formatting with Pint & ESLint
composer run lint:check
npm run lint:check

# Run static type checking with PHPStan
composer run types:check

# Execute full CI test pipeline
composer run ci:check
```

---

## 📁 Directory Structure

```
orbit/
├── app/
│   ├── Actions/            # Business logic actions (Fortify, Project, Invoice handlers)
│   ├── Http/
│   │   ├── Controllers/    # HTTP Controllers
│   │   └── Requests/       # Form Validation Requests
│   ├── Models/             # Eloquent Models & Relationships
│   └── Services/           # Domain services (Depreciation, Metrics, Quotes)
├── config/                 # Application configuration files
├── database/
│   ├── factories/          # Model factories for testing & seeding
│   ├── migrations/         # Database schema migrations
│   └── seeders/            # Database seeders
├── docs/                   # Development documentation & architecture roadmaps
├── resources/
│   ├── css/                # Global CSS styles & Tailwind configuration
│   └── js/
│       ├── components/     # Reusable UI & Shadcn components
│       ├── layouts/        # Application layouts (AppLayout, GuestLayout)
│       ├── pages/          # Inertia Vue pages (Dashboard, Projects, Tasks, Finance, etc.)
│       └── types/          # TypeScript interface definitions
├── routes/
│   ├── web.php             # Web routes & Inertia view definitions
│   └── console.php         # Scheduled Artisan tasks & console commands
└── tests/                  # Pest unit & feature tests
```

---

## 🤝 Contributing

Contributions are what make the open-source community an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

1. **Fork** the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Verify tests and formatting (`composer run ci:check`)
5. **Push** to the Branch (`git checkout -b feature/AmazingFeature`)
6. Open a **Pull Request**

---

## 📜 License

Distributed under the **MIT License**. See [`LICENSE`](LICENSE) for more information.

---

<div align="center">

Crafted with ❤️ by **Growth Coder Project** for developers and creators around the world.  
**Orbit** — *Open by Nature. Organized by Design.*

</div>
