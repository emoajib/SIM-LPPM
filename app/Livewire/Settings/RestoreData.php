<?php

namespace App\Livewire\Settings;

use App\Services\DatabaseRestoreService;
use App\Services\StorageRestoreService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class RestoreData extends Component
{
    use WithFileUploads;

    protected const MAX_OUTPUT_LENGTH = 102400;

    public $sqlFile = null;

    public $zipFile = null;

    public string $output = '';

    public bool $isRunning = false;

    public bool $hasPreview = false;

    public array $preview = [];

    public array $warnings = [];

    public bool $hasWarnings = false;

    public ?string $uploadedSqlPath = null;

    public ?string $uploadedZipPath = null;

    public bool $replaceMode = true;

    public bool $zipReplaceMode = true;

    public array $availableZipFolders = [];

    public array $selectedZipFolders = [];

    public ?string $uploadErrorMessage = null;

    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    public array $serverFiles = [];

    public ?string $selectedServerFile = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->activeHasRole('admin lppm'), 403);
        $this->scanServerFiles();
    }

    public function updatedSqlFile(): void
    {
        $this->resetStates();

        // CATATAN: jangan pakai rule "mimes:sql" untuk file .sql.
        // Validator mimes memakai UploadedFile::guessExtension() yang diturunkan dari MIME
        // hasil finfo. File dump .sql hampir selalu terdeteksi "text/plain", dan Symfony
        // memetakan text/plain -> ekstensi pertama "txt", sehingga "mimes:sql" selalu GAGAL
        // walau file benar-benar SQL. Rule "extensions" memakai ekstensi nama file asli.
        try {
            $this->validate([
                'sqlFile' => 'file|extensions:sql,txt|max:524288',
            ]);
        } catch (ValidationException $e) {
            $this->uploadErrorMessage = $e->getMessage();
            throw $e;
        }

        $backupDir = storage_path('app/backup');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = 'upload_restore_'.now()->format('Ymd_His').'.sql';
        $this->uploadedSqlPath = $backupDir.'/'.$filename;

        $sourcePath = $this->sqlFile->getRealPath();
        if ($sourcePath === false || ! copy($sourcePath, $this->uploadedSqlPath)) {
            $this->output = "❌ Gagal menyimpan file ke {$backupDir}.\n";

            return;
        }

        // Pengaman tambahan: rule "extensions" hanya melihat nama file, jadi pastikan
        // isinya benar-benar dump SQL (bukan file lain yang hanya diberi ekstensi .sql).
        if (! $this->looksLikeSqlDump($this->uploadedSqlPath)) {
            File::delete($this->uploadedSqlPath);
            $this->uploadedSqlPath = null;

            $message = 'File tidak dikenali sebagai dump SQL. Pastikan file berisi pernyataan SQL seperti INSERT INTO atau CREATE TABLE.';
            $this->uploadErrorMessage = $message;

            throw ValidationException::withMessages(['sqlFile' => $message]);
        }

        $service = app(DatabaseRestoreService::class);
        $preview = $service->preview($this->uploadedSqlPath);
        $this->preview = collect($preview)->except(['statements', 'blocked'])->toArray();
        $this->warnings = $preview['warnings'] ?? [];
        $this->hasWarnings = ! empty($this->warnings);
        $this->hasPreview = true;

        $this->logSqlPreview($filename);
    }

    public function updatedZipFile(): void
    {
        Log::info('RestoreData: Starting ZIP upload process');
        $this->resetStates();

        try {
            Log::info('RestoreData: Validating ZIP file');
            $this->validate([
                'zipFile' => 'file|mimes:zip|max:524288',
            ]);
        } catch (ValidationException $e) {
            Log::warning('RestoreData: Validation failed', ['errors' => $e->errors()]);
            $this->uploadErrorMessage = $e->getMessage();
            throw $e;
        }

        $backupDir = storage_path('app/backup');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = 'upload_restore_'.now()->format('Ymd_His').'.zip';
        $this->uploadedZipPath = $backupDir.'/'.$filename;
        Log::info('RestoreData: Saving ZIP to '.$this->uploadedZipPath);

        $sourcePath = $this->zipFile->getRealPath();
        if ($sourcePath === false || ! copy($sourcePath, $this->uploadedZipPath)) {
            $this->output = "❌ Gagal menyimpan file ke {$backupDir}.\n";
            Log::error('RestoreData: Copy failed', ['source' => $sourcePath]);

            return;
        }

        Log::info('RestoreData: Starting ZIP preview analysis');
        $service = app(StorageRestoreService::class);
        try {
            $validation = $service->preview($this->uploadedZipPath);
        } catch (\Exception $e) {
            Log::error('RestoreData: Preview service error', ['msg' => $e->getMessage()]);
            $this->output = '❌ Error Analysis: '.$e->getMessage();

            return;
        }

        $this->hasPreview = true;
        $this->preview = $validation['validation'];
        $this->preview['total_entries'] = $validation['total_entries'] ?? 0;
        $this->preview['total_size'] = $validation['total_size'] ?? 0;
        $this->availableZipFolders = $validation['folders'] ?? [];
        $this->selectedZipFolders = $this->availableZipFolders;

        Log::info('RestoreData: ZIP ready for UI', [
            'folders' => count($this->availableZipFolders),
            'entries' => $validation['total_entries'],
        ]);

        $this->output = "File: {$filename}\n";
        $this->output .= "Entries: {$validation['total_entries']}\n";
        $this->output .= 'Ukuran: '.$this->formatSize($validation['total_size'])."\n";

        if (! $validation['validation']['valid']) {
            $this->output .= "\n⚠️ Masalah terdeteksi:\n";
            foreach ($validation['validation']['issues'] as $issue) {
                $this->output .= "  ❌ {$issue}\n";
            }
        } else {
            $this->output .= "\n✅ File ZIP siap. Silakan pilih folder dan mode di bawah.";
        }
    }

    public function updatedOutput(): void
    {
        if (strlen($this->output) > self::MAX_OUTPUT_LENGTH) {
            $this->output = '⏎ [Output dipotong, '.strlen($this->output).' chars]'.PHP_EOL
                .substr($this->output, -intdiv(self::MAX_OUTPUT_LENGTH, 2));
        }
    }

    private function resetStates(): void
    {
        $this->output = '';
        $this->uploadErrorMessage = null;
        $this->hasPreview = false;
        $this->preview = [];
        $this->warnings = [];
        $this->hasWarnings = false;
        $this->uploadedSqlPath = null;
        $this->uploadedZipPath = null;
        $this->availableZipFolders = [];
        $this->selectedZipFolders = [];
        $this->selectedServerFile = null;
    }

    private function logSqlPreview(string $filename): void
    {
        $mode = $this->replaceMode ? 'Sinkron' : 'Tambah';
        $this->output = "Mode: {$mode}\n";
        $this->output .= "File: {$filename}\n";
        $this->output .= 'Tabel: '.count($this->preview['tables'])."\n";
        $this->output .= "Baris: {$this->preview['allowed']}\n";

        if (($this->preview['allowed'] ?? 0) === 0) {
            $this->output .= "\n⚠️ Tidak ada pernyataan yang dapat dipulihkan. "
                ."Hanya pernyataan INSERT INTO / REPLACE INTO / SET yang diizinkan.\n";
        }
    }

    /**
     * Pastikan isi file benar-benar dump SQL, bukan file lain yang diberi ekstensi .sql.
     */
    private function looksLikeSqlDump(string $path): bool
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }

        $sample = (string) fread($handle, 65536);
        fclose($handle);

        if ($sample === '' || str_contains($sample, "\0")) {
            return false;
        }

        return (bool) preg_match(
            '/\b(INSERT\s+(IGNORE\s+)?INTO|REPLACE\s+INTO|CREATE\s+TABLE|DROP\s+TABLE|SET\s+@|\/\*!)/i',
            $sample
        );
    }

    public function getPhpLimitsProperty(): array
    {
        return [
            'post_max' => ini_get('post_max_size'),
            'upload_max' => ini_get('upload_max_filesize'),
        ];
    }

    public function executeRestore(): void
    {
        abort_unless(Auth::user()?->activeHasRole('admin lppm'), 403);

        if ($this->isRunning) {
            return;
        }

        if ($this->uploadedZipPath && empty($this->selectedZipFolders)) {
            $this->output = '⚠️ Silakan pilih minimal satu folder storage untuk dipulihkan.';

            return;
        }

        $this->isRunning = true;
        $this->output = '';

        try {
            if ($this->uploadedSqlPath) {
                $modeLabel = $this->replaceMode ? 'Sinkron' : 'Tambah';
                $this->output .= "Memulihkan database (mode: {$modeLabel})...\n";
                $service = app(DatabaseRestoreService::class);

                $result = $this->replaceMode
                    ? $service->restoreWithReplace($this->uploadedSqlPath, true)
                    : $service->restore($this->uploadedSqlPath, true);

                $this->output .= $result['message']."\n";

                if (! empty($result['errors'])) {
                    foreach (array_slice($result['errors'], 0, 10) as $e) {
                        $this->output .= "  ⚠️ {$e['error']}\n";
                    }
                }
            }

            if ($this->uploadedZipPath) {
                $modeLabel = $this->zipReplaceMode ? 'Sinkron' : 'Tambah';
                $this->output .= "\nMemulihkan file storage (mode: {$modeLabel})...\n";
                $service = app(StorageRestoreService::class);
                $result = $service->restore($this->uploadedZipPath, $this->selectedZipFolders, $this->zipReplaceMode);

                $this->output .= $result['message']."\n";

                if (! empty($result['skipped'])) {
                    foreach (array_slice($result['skipped'], 0, 5) as $s) {
                        $this->output .= "  ⚠️ Dilewati: {$s}\n";
                    }
                }
            }

            $this->output .= "\n✅ Proses pulihkan selesai!";
        } catch (\Throwable $e) {
            $this->output .= "\n❌ Error: {$e->getMessage()}";
        }

        $this->isRunning = false;
        $this->hasPreview = false;
        $this->preview = [];
        $this->scanServerFiles();
    }

    public function resetUpload(): void
    {
        $this->sqlFile = null;
        $this->zipFile = null;
        $this->output = '';
        $this->hasPreview = false;
        $this->preview = [];
        $this->warnings = [];
        $this->hasWarnings = false;
        $this->uploadedSqlPath = null;
        $this->uploadedZipPath = null;
        $this->availableZipFolders = [];
        $this->selectedZipFolders = [];
        $this->selectedServerFile = null;
        $this->uploadErrorMessage = null;
        $this->scanServerFiles();
    }

    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    public function scanServerFiles(): void
    {
        abort_unless(Auth::user()?->activeHasRole('admin lppm'), 403);

        $backupDir = storage_path('app/backup');
        if (! is_dir($backupDir)) {
            $this->serverFiles = [];

            return;
        }

        $pattern = $backupDir.'/*.*';
        $allFiles = glob($pattern) ?: [];
        $fileList = [];

        foreach ($allFiles as $file) {
            $basename = basename($file);
            if (! preg_match('/^[A-Za-z0-9_\-\.]+\.(sql|zip)$/i', $basename)) {
                continue;
            }

            $size = (int) @filesize($file);
            $mtime = (int) @filemtime($file);
            $extension = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));

            $fileList[] = [
                'filename' => $basename,
                'extension' => $extension,
                'size' => $size,
                'formatted_size' => $this->formatSize($size),
                'modified_at' => date('d M Y H:i:s', $mtime),
                'timestamp' => $mtime,
            ];
        }

        usort($fileList, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        $this->serverFiles = $fileList;
    }

    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    public function selectServerFile(string $filename): void
    {
        abort_unless(Auth::user()?->activeHasRole('admin lppm'), 403);

        $basename = basename($filename);
        if (! preg_match('/^[A-Za-z0-9_\-\.]+\.(sql|zip)$/i', $basename)) {
            $this->output = "❌ Nama file tidak valid.\n";

            return;
        }

        $filePath = storage_path('app/backup/'.$basename);
        if (! file_exists($filePath)) {
            $this->output = "❌ File tidak ditemukan di server: {$basename}\n";
            $this->scanServerFiles();

            return;
        }

        $this->resetStates();
        $this->selectedServerFile = $basename;
        $extension = strtolower((string) pathinfo($basename, PATHINFO_EXTENSION));

        if ($extension === 'sql') {
            $this->uploadedSqlPath = $filePath;
            $service = app(DatabaseRestoreService::class);
            try {
                $preview = $service->preview($this->uploadedSqlPath);
                $this->preview = collect($preview)->except(['statements', 'blocked'])->toArray();
                $this->warnings = $preview['warnings'] ?? [];
                $this->hasWarnings = ! empty($this->warnings);
                $this->hasPreview = true;
                $this->logSqlPreview($basename);
            } catch (\Exception $e) {
                $this->output = '❌ Error Analysis Database: '.$e->getMessage();
            }
        } elseif ($extension === 'zip') {
            $this->uploadedZipPath = $filePath;
            $service = app(StorageRestoreService::class);
            try {
                $validation = $service->preview($this->uploadedZipPath);
                $this->hasPreview = true;
                $this->preview = $validation['validation'];
                $this->preview['total_entries'] = $validation['total_entries'] ?? 0;
                $this->preview['total_size'] = $validation['total_size'] ?? 0;
                $this->availableZipFolders = $validation['folders'] ?? [];
                $this->selectedZipFolders = $this->availableZipFolders;

                $this->output = "File Server: {$basename}\n";
                $this->output .= "Entries: {$validation['total_entries']}\n";
                $this->output .= 'Ukuran: '.$this->formatSize($validation['total_size'])."\n";

                if (! $validation['validation']['valid']) {
                    $this->output .= "\n⚠️ Masalah terdeteksi:\n";
                    foreach ($validation['validation']['issues'] as $issue) {
                        $this->output .= "  ❌ {$issue}\n";
                    }
                } else {
                    $this->output .= "\n✅ File ZIP siap. Silakan pilih folder dan mode di bawah.";
                }
            } catch (\Exception $e) {
                $this->output = '❌ Error Analysis Storage: '.$e->getMessage();
            }
        }
    }

    public function render(): View
    {
        return view('livewire.settings.restore-data');
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
