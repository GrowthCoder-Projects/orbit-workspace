<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    /**
     * Show the backups settings page.
     */
    public function edit(): Response
    {
        $diskName = config('backup.backup.destination.disks.0', 'local');
        $backupName = config('backup.backup.name', 'laravel-backup');

        // Check if directory exists
        $files = [];
        if (Storage::disk($diskName)->exists($backupName)) {
            $files = Storage::disk($diskName)->files($backupName);
        }

        $backups = collect($files)->map(function ($path) use ($diskName) {
            $size = Storage::disk($diskName)->size($path);
            $lastModified = Storage::disk($diskName)->lastModified($path);

            return [
                'filename' => basename($path),
                'size' => $this->formatBytes($size),
                'created_at' => date('Y-m-d H:i:s', $lastModified),
                'created_at_diff' => Carbon::createFromTimestamp($lastModified)->diffForHumans(),
            ];
        })->sortByDesc('created_at')->values()->all();

        return Inertia::render('settings/Backups', [
            'backups' => $backups,
            'activeDisk' => $diskName,
            'status' => session('status'),
        ]);
    }

    /**
     * Run a database backup now.
     */
    public function run(): RedirectResponse
    {
        $logPath = storage_path('logs/backup.log');
        $artisanPath = base_path('artisan');

        // Gunakan PHP CLI binary secara dinamis (ubah php-cgi/php-fpm ke php cli)
        $phpBinary = PHP_BINARY;
        if (str_contains($phpBinary, 'php-cgi')) {
            $phpBinary = str_replace('php-cgi', 'php', $phpBinary);
        } elseif (str_contains($phpBinary, 'php-fpm')) {
            $phpBinary = 'php';
        }

        if (! file_exists($phpBinary)) {
            $phpBinary = 'php';
        }

        // Normalisasi path separator agar sesuai dengan OS (terutama Windows CMD)
        $logPath = str_replace('/', DIRECTORY_SEPARATOR, $logPath);
        $artisanPath = str_replace('/', DIRECTORY_SEPARATOR, $artisanPath);
        if ($phpBinary !== 'php') {
            $phpBinary = str_replace('/', DIRECTORY_SEPARATOR, $phpBinary);
        }

        // Siapkan command backup
        $fullCommand = "\"{$phpBinary}\" \"{$artisanPath}\" backup:run --only-db --disable-notifications >> \"{$logPath}\" 2>&1";

        Log::info('Initiating database backup. Command: '.$fullCommand);

        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Di Windows, jalankan via popen + start /B cmd /c agar redirection >> diproses oleh shell di background
                pclose(popen("start /B \"\" cmd /c \"{$fullCommand}\"", 'r'));
                Log::info('Backup process spawned in background (Windows).');
            } else {
                // Di Linux/macOS, jalankan dengan menambahkan & di akhir command
                exec($fullCommand.' &');
                Log::info('Backup process spawned in background (Linux/macOS).');
            }

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('Database backup started in the background. Check logs/backup.log for progress and refresh the page in a few seconds.'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to spawn background backup process: '.$e->getMessage()."\n".$e->getTraceAsString());

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Failed to start backup: :message', ['message' => $e->getMessage()]),
            ]);
        }

        return back();
    }

    /**
     * Download a specific backup file.
     */
    public function download(string $filename): StreamedResponse
    {
        $filename = basename($filename);
        $diskName = config('backup.backup.destination.disks.0', 'local');
        $backupName = config('backup.backup.name', 'laravel-backup');
        $path = $backupName.'/'.$filename;

        if (Storage::disk($diskName)->exists($path)) {
            return Storage::disk($diskName)->download($path);
        }

        abort(404, __('Backup file not found.'));
    }

    /**
     * Delete a specific backup file.
     */
    public function destroy(string $filename): RedirectResponse
    {
        $filename = basename($filename);
        $diskName = config('backup.backup.destination.disks.0', 'local');
        $backupName = config('backup.backup.name', 'laravel-backup');
        $path = $backupName.'/'.$filename;

        if (Storage::disk($diskName)->exists($path)) {
            Storage::disk($diskName)->delete($path);

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('Backup file deleted successfully.'),
            ]);
        } else {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Backup file not found.'),
            ]);
        }

        return back();
    }

    /**
     * Format file size in bytes to human readable format.
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }
}
