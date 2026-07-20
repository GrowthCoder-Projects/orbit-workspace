# Perencanaan Pengembangan Modularitas Proyek (Future Plan)

Dokumen ini menjelaskan rancangan arsitektur untuk menerapkan sistem modularitas pada aplikasi. Rencana ini dibagi menjadi dua bagian utama:
1. **Runtime Control (User-level):** Halaman Manajemen Fitur di menu Settings untuk mengaktifkan/menonaktifkan fitur secara dinamis per user.
2. **Installation Control (Developer-level):** Kustom Artisan Command dengan Laravel Prompts untuk memilih modul yang ingin diinstal pada saat setup awal proyek.

---

## Bagian 1: Halaman Manajemen Fitur di Settings (Runtime Control)

Pendekatan ini memungkinkan pengguna (user) untuk menyesuaikan tampilan dashboard dan menu sidebar mereka sendiri secara dinamis melalui UI.

### 1.1 Struktur Database & Model
Kita akan menggunakan tabel `settings` yang sudah ada. Karena model [Setting](file:///c:/www/growthcoder-workspace/app/Models/Setting.php) menggunakan trait [BelongsToUser](file:///c:/www/growthcoder-workspace/app/Concerns/BelongsToUser.php), pengaturan ini otomatis terisolasi untuk tiap user.

* **Key:** `enabled_modules`
* **Value (JSON):**
  ```json
  {
    "projects": true,
    "tasks": true,
    "habits": false,
    "finance": true,
    "kb": false
  }
  ```

### 1.2 Integrasi Backend (Middleware & Inertia)
Kita akan mengirimkan status modul yang aktif ke frontend melalui [HandleInertiaRequests.php](file:///c:/www/growthcoder-workspace/app/Http/Middleware/HandleInertiaRequests.php).

```php
// app/Http/Middleware/HandleInertiaRequests.php

public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        'auth' => [
            'user' => $request->user(),
            'modules' => $request->user() 
                ? \App\Models\Setting::getValue('enabled_modules', [
                    'projects' => true,
                    'tasks' => true,
                    'habits' => true,
                    'finance' => true,
                    'kb' => true,
                    'bookmarks' => true,
                ])
                : [],
        ],
    ]);
}
```

### 1.3 Penyaringan Menu di Sidebar (Frontend)
Di file [AppSidebar.vue](file:///c:/www/growthcoder-workspace/resources/js/components/AppSidebar.vue), kita menyaring item menu berdasarkan prop `auth.modules` yang dibagikan oleh Inertia.

```vue
<!-- resources/js/components/AppSidebar.vue -->
<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const enabledModules = computed(() => page.props.auth.modules);

const filteredWorkNavItems = computed(() => {
    return workNavItems.filter(item => {
        if (item.title === 'Projects') return enabledModules.value.projects;
        if (item.title === 'Tasks') return enabledModules.value.tasks;
        if (item.title === 'Clients') return enabledModules.value.projects; // bergantung pada modul projects
        if (item.title === 'Invoices') return enabledModules.value.finance;
        return true;
    });
});
</script>
```

### 1.4 Halaman UI Settings (Manajemen Fitur)
Kita akan membuat halaman baru bernama `Features.vue` di dalam folder [settings](file:///c:/www/growthcoder-workspace/resources/js/pages/settings) dengan antarmuka berbasis daftar kartu switch toggle:

```vue
<!-- resources/js/pages/settings/Features.vue -->
<template>
  <SettingsLayout>
    <div class="space-y-6">
      <div>
        <h3 class="text-lg font-medium">Manajemen Fitur</h3>
        <p class="text-sm text-muted-foreground">Aktifkan atau nonaktifkan modul aplikasi sesuai dengan kebutuhan produktivitas Anda.</p>
      </div>
      
      <div class="space-y-4">
        <div v-for="(label, key) in availableModules" :key="key" class="flex items-center justify-between p-4 border rounded-lg">
          <div>
            <h4 class="font-semibold capitalize">{{ key }}</h4>
            <p class="text-sm text-muted-foreground">{{ label.description }}</p>
          </div>
          <Switch :checked="form[key]" @update:checked="toggleModule(key)" />
        </div>
      </div>
    </div>
  </SettingsLayout>
</template>
```

### 1.5 Pengamanan Rute (Middleware Akses)
Kita akan membuat middleware khusus `CheckModuleEnabled` untuk memblokir akses langsung jika user mengetik URL modul yang nonaktif secara manual.

```php
// app/Http/Middleware/CheckModuleEnabled.php

public function handle(Request $request, Closure $next, string $module)
{
    $enabledModules = Setting::getValue('enabled_modules', [...]);
    
    if (!($enabledModules[$module] ?? false)) {
        return redirect()->route('dashboard')->with('error', 'Modul ini dinonaktifkan.');
    }

    return $next($request);
}
```

---

## Bagian 2: Kustom Artisan Command + Laravel Prompts (Installation Control)

Pendekatan ini ditujukan untuk developer saat pertama kali melakukan kloning repositori dan ingin menyaring database serta struktur aplikasi sejak proses instalasi (`install-time`).

### 2.1 Restrukturisasi Direktori Migrasi
Untuk mendukung instalasi modular, file migrasi database akan dipisahkan berdasarkan folder modul:

```
database/
├── migrations/
│   ├── core/               # Tabel utama (users, settings, sessions, dll)
│   ├── projects/           # Tabel projects, tasks, milestones, clients
│   ├── finance/            # Tabel accounts, transactions, bills, invoices
│   └── habits/             # Tabel habits, habit_logs
```

### 2.2 Pembuatan Command `app:install`
Kita akan membuat command kustom menggunakan Artisan Command bawaan Laravel yang memanfaatkan **Laravel Prompts** untuk kenyamanan interaksi CLI.

#### Contoh Implementasi Class Command:
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;

class AppInstallCommand extends Command
{
    protected $signature = 'app:install';
    protected $description = 'Setup awal aplikasi secara modular';

    public function handle()
    {
        info('Selamat datang di Instalasi Modular GrowthCoder!');

        // 1. Tanya user modul apa saja yang ingin dipasang
        $modules = multiselect(
            label: 'Pilih modul yang ingin Anda aktifkan di sistem ini:',
            options: [
                'projects' => 'Project & Task Management',
                'finance'  => 'Finance Tracker & Accounts Book',
                'habits'   => 'Habit Tracker',
                'kb'       => 'Knowledge Base & Bookmarks',
            ],
            default: ['projects', 'finance'],
            required: true
        );

        // 2. Jalankan Migrasi Core secara otomatis
        spin(
            fn () => Artisan::call('migrate', [
                '--path' => 'database/migrations/core',
                '--force' => true
            ]),
            'Menyiapkan tabel sistem utama...'
        );

        // 3. Jalankan Migrasi Modul yang dipilih
        foreach ($modules as $module) {
            $path = "database/migrations/{$module}";
            if (File::exists(database_path("migrations/{$module}"))) {
                spin(
                    fn () => Artisan::call('migrate', [
                        '--path' => $path,
                        '--force' => true
                    ]),
                    "Memasang modul: {$module}..."
                );
            }
        }

        // 4. Tulis konfigurasi awal ke file config atau .env
        $this->updateSystemConfiguration($modules);

        info('Instalasi selesai! Aplikasi Anda siap digunakan dengan modul yang dipilih.');
    }

    private function updateSystemConfiguration(array $modules): void
    {
        // Menyimpan status modul global ke .env atau file config/modules.php
        $moduleList = implode(',', $modules);
        
        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            $envContent = File::get($envPath);
            if (str_contains($envContent, 'ENABLED_MODULES=')) {
                $envContent = preg_replace('/ENABLED_MODULES=.*/', "ENABLED_MODULES={$moduleList}", $envContent);
            } else {
                $envContent .= "\nENABLED_MODULES={$moduleList}\n";
            }
            File::put($envPath, $envContent);
        }
    }
}
```

### 2.3 Konfigurasi Menu Global (Fallback)
Jika modul tidak diaktifkan pada tingkat instalasi (.env), maka sistem secara keseluruhan tidak akan me-load rute dan menu untuk modul tersebut.

```php
// config/modules.php
return [
    'active' => explode(',', env('ENABLED_MODULES', 'projects,finance,habits,kb')),
];
```

Pada file rute (`routes/web.php`):
```php
if (in_array('finance', config('modules.active'))) {
    Route::middleware(['auth'])->group(function () {
        // Semua rute finansial didaftarkan di sini
        Route::get('/finance', [FinanceController::class, 'index']);
    });
}
```

Hal ini sangat berguna karena jika modul `finance` tidak diinstal/diaktifkan di `.env`, maka rute `/finance` tidak akan terdaftar sama sekali di aplikasi, sehingga performa routing lebih optimal dan aman.
