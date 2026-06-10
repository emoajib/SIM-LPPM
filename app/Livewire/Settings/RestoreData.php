<?php

namespace App\Livewire\Settings;

use App\Services\DatabaseRestoreService;
use App\Services\StorageRestoreService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class RestoreData extends Component
{
    use WithFileUploads;

    public $sqlFile = null;

    public $zipFile = null;

    public string $output = '';

    public bool $isRunning = false;

    public bool $hasPreview = false;

    public array $preview = [];

    public ?string $uploadedSqlPath = null;

    public ?string $uploadedZipPath = null;

    public bool $replaceMode = true;

    public bool $zipReplaceMode = true;

    public array $availableZipFolders = [];

    public array $selectedZipFolders = [];

    public ?string $uploadErrorMessage = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole('admin lppm'), 403);
    }

    public function updatedSqlFile(): void
    {
        $this->resetStates();

        try {
            $this->validate([
                'sqlFile' => 'file|mimes:sql,text,plain|max:524288',
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

        $service = app(DatabaseRestoreService::class);
        $this->preview = $service->preview($this->uploadedSqlPath);
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

    private function resetStates(): void
    {
        $this->output = '';
        $this->uploadErrorMessage = null;
        $this->hasPreview = false;
        $this->preview = [];
        $this->uploadedSqlPath = null;
        $this->uploadedZipPath = null;
        $this->availableZipFolders = [];
        $this->selectedZipFolders = [];
    }

    private function logSqlPreview(string $filename): void
    {
        $mode = $this->replaceMode ? 'Sinkron' : 'Tambah';
        $this->output = "Mode: {$mode}\n";
        $this->output .= "File: {$filename}\n";
        $this->output .= 'Tabel: '.count($this->preview['tables'])."\n";
        $this->output .= "Baris: {$this->preview['allowed']}\n";
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
        abort_unless(Auth::user()?->hasRole('admin lppm'), 403);

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
    }

    public function resetUpload(): void
    {
        $this->sqlFile = null;
        $this->zipFile = null;
        $this->output = '';
        $this->hasPreview = false;
        $this->preview = [];
        $this->uploadedSqlPath = null;
        $this->uploadedZipPath = null;
        $this->availableZipFolders = [];
        $this->selectedZipFolders = [];
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
