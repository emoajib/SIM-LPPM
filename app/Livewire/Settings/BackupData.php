<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Process;
use Illuminate\View\View;
use Livewire\Component;
use ZipArchive;

class BackupData extends Component
{
    protected const MAX_OUTPUT_LENGTH = 102400;

    public string $output = '';

    public bool $isRunning = false;

    public string $lastBackup = '';

    public ?string $lastDbFile = null;

    public ?string $lastStorageFile = null;

    public array $availableFolders = [];

    public array $selectedFolders = [];

    public function updatedOutput(): void
    {
        if (strlen($this->output) > self::MAX_OUTPUT_LENGTH) {
            $this->output = '⏎ [Output dipotong, '.strlen($this->output).' chars]'.PHP_EOL
                .substr($this->output, -intdiv(self::MAX_OUTPUT_LENGTH, 2));
        }
    }

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole('admin lppm'), 403);

        $this->lastBackup = cache('backup_last_time', 'Belum pernah');
        $this->lastDbFile = cache('backup_last_db_file');
        $this->lastStorageFile = cache('backup_last_storage_file');

        $this->loadAvailableFolders();
    }

    private function loadAvailableFolders(): void
    {
        $storagePath = storage_path('app/public');
        if (is_dir($storagePath)) {
            $folders = glob($storagePath.'/*', GLOB_ONLYDIR);
            $this->availableFolders = array_map(function ($path) {
                return basename($path);
            }, $folders);

            // Default select all
            $this->selectedFolders = $this->availableFolders;
        }
    }

    public function backupDatabase(): void
    {
        abort_unless(Auth::user()?->hasRole('admin lppm'), 403);

        if ($this->isRunning) {
            return;
        }

        $this->isRunning = true;
        $this->output = "Membuat backup database...\n";

        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port');

        $backupDir = storage_path('app/backup');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = now()->format('Ymd_His');
        $filename = "db_{$timestamp}.sql";
        $path = "{$backupDir}/{$filename}";

        $this->output .= "Database : {$dbName}\n";
        $this->output .= "Menulis ke: {$filename}\n\n";

        $this->cleanOldBackups('db_*');

        $mysqldumpPath = $this->findMysqldump();
        if (! $mysqldumpPath) {
            $this->output .= "\n❌ perintah `mysqldump` tidak tersedia di server ini.";
            $this->output .= "\n   Gunakan alternatif: export manual via phpMyAdmin.";
            $this->isRunning = false;

            return;
        }

        $this->output .= "mysqldump: {$mysqldumpPath}\n\n";

        // Fix: Paksa menggunakan TCP jika host adalah localhost atau 127.0.0.1
        // untuk menghindari error 'Can't connect to local server through socket'
        $finalHost = $dbHost;
        if ($dbHost === 'localhost' || empty($dbHost)) {
            $finalHost = '127.0.0.1';
        }

        $cmd = [$mysqldumpPath];
        $cmd[] = '-h';
        $cmd[] = $finalHost;

        if ($dbPort) {
            $cmd[] = '-P';
            $cmd[] = (string) $dbPort;
        }

        $cmd[] = '--protocol=tcp'; // Memastikan tidak menggunakan socket
        $cmd[] = '-u';
        $cmd[] = $dbUser;
        if ($dbPass) {
            $cmd[] = "-p{$dbPass}";
        }
        $cmd[] = '--single-transaction';
        $cmd[] = '--quick';
        $cmd[] = '--complete-insert';
        $cmd[] = '--lock-tables=false';
        $cmd[] = $dbName;

        $result = Process::timeout(600)->run($cmd, function ($type, $line) {
            $this->output .= $line."\n";
        });

        if ($result->successful()) {
            file_put_contents($path, $result->output());

            if (file_exists($path) && filesize($path) > 0) {
                cache([
                    'backup_last_time' => now()->format('d M Y H:i:s'),
                    'backup_last_db_file' => $filename,
                ]);
                $this->lastBackup = cache('backup_last_time');
                $this->lastDbFile = $filename;

                $size = $this->formatSize(filesize($path));
                $this->output .= "\n✅ Backup database berhasil! ({$size})";
                $this->output .= "\n📁 File: {$filename}";
            } else {
                $this->output .= "\n❌ File backup kosong atau gagal ditulis.";
            }
        } else {
            $error = $result->errorOutput();
            $output = $result->output();

            if (empty($error) && empty($output)) {
                $this->output .= "\n❌ Backup gagal. Proses timeout atau error tak terduga.";
                $this->output .= "\n   Tips: Coba lagi atau gunakan export manual via phpMyAdmin.";
            } elseif (str_contains($error, 'not found') || str_contains($error, 'command not found')) {
                $this->output .= "\n❌ perintah `mysqldump` tidak tersedia di server ini.";
                $this->output .= "\n   Gunakan alternatif: export manual via phpMyAdmin.";
            } elseif (str_contains($error, 'Access denied') || str_contains($error, 'password')) {
                $this->output .= "\n❌ Gagal koneksi ke database: akses ditolak.";
                $this->output .= "\n   Periksa username & password database di file .env";
            } elseif (str_contains($error, 'Unknown database')) {
                $this->output .= "\n❌ Database tidak ditemukan: {$dbName}";
                $this->output .= "\n   Periksa nama database di file .env";
            } else {
                $this->output .= "\n❌ Backup database gagal:";
                $this->output .= "\n   ".($error ?: $output);
            }
        }

        $this->isRunning = false;
    }

    public function backupStorage(): void
    {
        abort_unless(Auth::user()?->hasRole('admin lppm'), 403);

        if ($this->isRunning) {
            return;
        }

        if (empty($this->selectedFolders)) {
            $this->output = '⚠️ Silakan pilih minimal satu folder untuk di-backup.';

            return;
        }

        $this->isRunning = true;
        $this->output = 'Membuat backup file storage (Folder terpilih: '.implode(', ', $this->selectedFolders).")...\n";

        $backupDir = storage_path('app/backup');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = now()->format('Ymd_His');
        $filename = "storage_{$timestamp}.zip";
        $path = "{$backupDir}/{$filename}";
        $storagePath = storage_path('app/public');

        if (! is_dir($storagePath)) {
            $this->output .= "\n❌ Folder storage/app/public tidak ditemukan.";
            $this->output .= "\n   Pastikan symlink storage sudah dibuat.";
            $this->isRunning = false;

            return;
        }

        $this->cleanOldBackups('storage_*');

        // Optimasi: Gunakan perintah native 'zip' jika tersedia
        $zipPath = $this->findZipCommand();
        if ($zipPath) {
            $this->output .= "Menggunakan perintah native zip: {$zipPath}\n";

            // Hanya zip folder yang dipilih
            $cmd = [$zipPath, '-r', $path];
            foreach ($this->selectedFolders as $folder) {
                $cmd[] = $folder;
            }

            $result = Process::path($storagePath)->timeout(900)->run($cmd, function ($type, $line) {
                if (str_contains($line, 'adding:')) {
                    static $count = 0;
                    if (++$count % 50 === 0) {
                        $this->output .= "Memproses file... ({$count} file)\n";
                    }
                }
            });

            if ($result->successful() && file_exists($path) && filesize($path) > 0) {
                $this->finalizeStorageBackup($filename, $path);

                return;
            } else {
                $this->output .= "\n⚠️ Gagal menggunakan perintah native zip. Mencoba fallback PHP ZipArchive...\n";
            }
        }

        $this->backupStoragePhpFallback($path, $storagePath, $filename);
    }

    private function finalizeStorageBackup(string $filename, string $path): void
    {
        cache(['backup_last_storage_file' => $filename]);
        $this->lastStorageFile = $filename;

        $size = $this->formatSize(filesize($path));
        $this->output .= "\n✅ Backup storage berhasil! ({$size})";
        $this->output .= "\n📁 File: {$filename}";
        $this->isRunning = false;
    }

    private function backupStoragePhpFallback(string $path, string $storagePath, string $filename): void
    {
        try {
            set_time_limit(900);
            ini_set('memory_limit', '1024M');

            $zip = new ZipArchive;
            if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                $this->output .= "\n❌ Gagal membuat file zip.";
                $this->isRunning = false;

                return;
            }

            $count = 0;
            foreach ($this->selectedFolders as $folder) {
                $targetFolder = $storagePath.'/'.$folder;
                if (! is_dir($targetFolder)) {
                    continue;
                }

                $this->output .= "Menyiapkan folder: {$folder}...\n";

                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($targetFolder),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($files as $file) {
                    if (! $file->isFile()) {
                        continue;
                    }
                    // relative path must include the folder name as the root in ZIP
                    $relativePath = $folder.'/'.substr($file->getPathname(), strlen($targetFolder) + 1);
                    $zip->addFile($file->getPathname(), $relativePath);
                    $count++;

                    if ($count % 50 === 0) {
                        $this->output .= "Memasukkan file ke ZIP... ({$count})\n";
                    }
                }
            }

            $zip->close();

            if ($count > 0 && file_exists($path) && filesize($path) > 0) {
                $this->finalizeStorageBackup($filename, $path);
            } else {
                @unlink($path);
                $this->output .= "\n⚠️ Tidak ada file dalam folder terpilih untuk di-backup.";
                $this->isRunning = false;
            }
        } catch (\Exception $e) {
            $this->output .= "\n❌ Error: ".$e->getMessage();
            @unlink($path);
            $this->isRunning = false;
        }
    }

    public function render(): View
    {
        return view('livewire.settings.backup-data');
    }

    private function cleanOldBackups(string $pattern): void
    {
        $files = glob(storage_path("app/backup/{$pattern}"));
        if ($files) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }

    private function findZipCommand(): ?string
    {
        $paths = ['/usr/bin/zip', '/usr/local/bin/zip', 'zip'];

        foreach ($paths as $path) {
            $result = Process::run(['which', $path]);
            if ($result->successful()) {
                return trim($result->output());
            }
        }

        return null;
    }

    private function findMysqldump(): ?string
    {
        $paths = [
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/opt/cpanel/ea-mysql*/bin/mysqldump',
            'mysqldump',
        ];

        foreach ($paths as $path) {
            if (str_contains($path, '*')) {
                $matches = glob($path);
                if (! empty($matches)) {
                    return $matches[0];
                }
            } else {
                $result = Process::run(['which', $path]);
                if ($result->successful()) {
                    return trim($result->output());
                }
            }
        }

        $result = Process::run(['which', 'mysqldump']);
        if ($result->successful()) {
            return 'mysqldump';
        }

        return null;
    }

    private function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
