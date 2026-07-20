# 17. Suggested Folder Structure

```
workspace/
├── app/
│   ├── Actions/            # Fortify and custom actions
│   ├── Console/            # Scheduled backup/reminder jobs
│   ├── Http/
│   │   ├── Controllers/    # Slim controllers
│   │   ├── Middleware/     # Security and activity log guards
│   │   └── Requests/       # Form Request validation classes
│   ├── Models/             # Eloquent Models with observers
│   ├── Observers/          # Monitors models for Activity logging
│   └── Services/           # Business logic classes (Vault, Crawler, PDF)
├── config/                 # Custom config arrays
├── database/
│   ├── migrations/         # PostgreSQL schema migrations
│   └── seeders/            # Seeder files
├── docs/                   # Product and system documentation
│   └── prd/                # Split PRD section files
├── resources/
│   ├── css/
│   │   └── app.css         # Tailwind v4 configuration file
│   └── js/
│       ├── Layouts/        # MainLayout, FocusModeLayout
│       ├── Pages/          # Vue views (Dashboard, Tasks, Notes, etc.)
│       ├── Components/     # Reusable UI widgets
│       └── Stores/         # Pinia stores (Search, App, Vault)
├── routes/
│   ├── web.php             # Main Inertia application endpoints
│   └── api.php             # Local system integration points
└── tests/                  # Pest test suites
```
