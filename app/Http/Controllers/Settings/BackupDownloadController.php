<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Process;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupDownloadController extends Controller
{
    /**
     * Download database backup.
     *
     * Filename diambil dari cache, BUKAN dari URL — mencegah directory traversal.
     * Fallback: jika cache kosong, cari file terbaru langsung di storage.
     */
    public function downloadDatabase(): StreamedResponse|RedirectResponse
    {
        abort_unless(Auth::user()?->activeHasRole('admin lppm'), 403);

        $filename = cache('backup_last_db_file');

        if (! $filename) {
            $filename = $this->findLatestBackup('db_', '.sql');
        }

        if (! $filename) {
            return redirect()->to(route('settings'))->with('error', 'Tidak ada backup database tersedia. Buat backup terlebih dahulu.');
        }

        return $this->streamFile($filename, 'application/sql');
    }

    /**
     * Download storage backup.
     *
     * Filename diambil dari cache, BUKAN dari URL — mencegah directory traversal.
     * Fallback: jika cache kosong, cari file terbaru langsung di storage.
     */
    public function downloadStorage(): StreamedResponse|RedirectResponse
    {
        abort_unless(Auth::user()?->activeHasRole('admin lppm'), 403);

        $filename = cache('backup_last_storage_file');

        if (! $filename) {
            $filename = $this->findLatestBackup('storage_', '.zip');
        }

        if (! $filename) {
            return redirect()->to(route('settings'))->with('error', 'Tidak ada backup storage tersedia. Buat backup terlebih dahulu.');
        }

        return $this->streamFile($filename, 'application/zip');
    }

    /**
     * Find latest backup file in storage directory.
     * Uses alternative method for cPanel compatibility.
     */
    private function findLatestBackup(string $prefix, string $extension): ?string
    {
        $backupDir = storage_path('app/backup');

        if (! is_dir($backupDir)) {
            return null;
        }

        if (! is_readable($backupDir)) {
            return null;
        }

        $files = scandir($backupDir);
        if ($files === false) {
            return null;
        }

        $matchingFiles = [];
        foreach ($files as $file) {
            if (is_file($backupDir.'/'.$file) &&
                str_starts_with($file, $prefix) &&
                str_ends_with($file, $extension)) {
                $matchingFiles[] = $file;
            }
        }

        if (empty($matchingFiles)) {
            return null;
        }

        usort($matchingFiles, function ($a, $b) use ($backupDir) {
            return filemtime($backupDir.'/'.$b) - filemtime($backupDir.'/'.$a);
        });

        return $matchingFiles[0];
    }

    /**
     * Download database backup dari Admin Dashboard (legacy).
     *
     * Membuat backup baru lalu langsung download.
     */
    public function downloadDatabaseBackup(): StreamedResponse
    {
        abort_unless(Auth::user()?->activeHasRole('admin lppm'), 403);

        $backupDir = storage_path('app/backup');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = 'backup_db_'.date('Y-m-d_His').'.sql';
        $path = "{$backupDir}/{$filename}";

        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        // Never pass the DB password as a CLI argument (visible in `ps`).
        // Use a 0600 temp defaults-file instead.
        $defaultsFile = tempnam(sys_get_temp_dir(), 'mycnf');
        if ($defaultsFile === false) {
            abort(500, 'Gagal membuat file konfigurasi sementara.');
        }

        file_put_contents($defaultsFile, "[client]\nuser={$dbUser}\npassword={$dbPass}\n");
        chmod($defaultsFile, 0600);

        $escapedDefaults = escapeshellarg($defaultsFile);
        $escapedDbName = escapeshellarg($dbName);
        $escapedPath = escapeshellarg($path);

        try {
            $result = Process::run("mysqldump --defaults-extra-file={$escapedDefaults} --complete-insert {$escapedDbName} > {$escapedPath}");

            if (! $result->successful()) {
                abort(500, 'Gagal membuat file backup database.');
            }
        } finally {
            @unlink($defaultsFile);
        }

        if (! file_exists($path) || filesize($path) === 0) {
            abort(500, 'Gagal membuat file backup database.');
        }

        return response()->streamDownload(
            function () use ($path) {
                readfile($path);
                @unlink($path);
            },
            $filename,
            ['Content-Type' => 'application/sql']
        );
    }

    /**
     * Stream file dari folder backup dengan validasi keamanan.
     */
    private function streamFile(string $filename, string $mime): StreamedResponse|RedirectResponse
    {
        $backupDir = storage_path('app/backup');
        $fullPath = $backupDir.'/'.$filename;

        if (! file_exists($fullPath) || ! is_readable($fullPath)) {
            return redirect()->to(route('settings'))->with('error', 'File backup tidak ditemukan atau tidak dapat dibaca.');
        }

        $size = filesize($fullPath);
        if ($size === 0) {
            return redirect()->to(route('settings'))->with('error', 'File backup kosong.');
        }

        return response()->streamDownload(
            fn () => @readfile($fullPath),
            $filename,
            [
                'Content-Type' => $mime,
                'Content-Length' => $size,
                'Accept-Ranges' => 'bytes',
            ]
        );
    }
}
