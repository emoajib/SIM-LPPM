<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use ZipArchive;

class StorageRestoreService
{
    protected array $blockedExtensions = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8',
        'shtml', 'htaccess', 'htpasswd', 'user.ini', 'web.config',
        'pl', 'py', 'sh', 'bash', 'cgi', 'asp', 'aspx', 'jsp',
        'exe', 'bat', 'cmd', 'dll', 'so',
    ];

    protected int $maxUploadSize = 524288000;

    protected int $maxExtractedSize = 2147483648;

    protected int $maxCompressionRatio = 100;

    public function restore(string $zipPath, array $selectedFolders = [], bool $replaceMode = true): array
    {
        if (! file_exists($zipPath)) {
            throw new \RuntimeException('File ZIP tidak ditemukan.');
        }

        $filesize = filesize($zipPath);
        if ($filesize > $this->maxUploadSize) {
            throw new \RuntimeException('File ZIP terlalu besar. Maksimal '.($this->maxUploadSize / 1048576).' MB.');
        }

        $zip = new ZipArchive;
        $result = $zip->open($zipPath);

        if ($result !== true) {
            throw new \RuntimeException('Gagal membuka file ZIP (kode: '.$result.').');
        }

        $validation = $this->validateZipEntries($zip);

        if (! $validation['valid']) {
            $zip->close();

            return [
                'success' => false,
                'message' => $validation['message'],
                'extracted' => 0,
                'issues' => $validation['issues'],
            ];
        }

        $targetDir = storage_path('app/public');
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // If replace mode is on, clean selected folders first
        if ($replaceMode && ! empty($selectedFolders)) {
            foreach ($selectedFolders as $folder) {
                if ($folder === '.') continue;
                $folderPath = $targetDir . '/' . $folder;
                if (is_dir($folderPath)) {
                    $this->deleteDirectory($folderPath);
                }
            }
        }

        $extracted = 0;
        $skipped = [];
        $errors = [];

        for ($i = 0; $i < $zip->count(); $i++) {
            $entry = $zip->getNameIndex($i);
            if ($entry === false) {
                continue;
            }

            // Check if entry belongs to selected folders
            if (!empty($selectedFolders)) {
                $belongsToSelected = false;
                foreach ($selectedFolders as $folder) {
                    if ($folder === '.') {
                        // Root files
                        if (!str_contains($entry, '/')) {
                            $belongsToSelected = true;
                            break;
                        }
                    } elseif (str_starts_with($entry, $folder . '/')) {
                        $belongsToSelected = true;
                        break;
                    }
                }
                if (!$belongsToSelected) continue;
            }

            if (str_ends_with($entry, '/')) {
                $dirPath = $targetDir.'/'.$entry;
                if (! is_dir($dirPath)) {
                    mkdir($dirPath, 0755, true);
                }

                continue;
            }

            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (in_array($ext, $this->blockedExtensions)) {
                $skipped[] = $entry.' (ekstensi diblokir)';

                continue;
            }

            $resolvedPath = $targetDir.'/'.$entry;
            // Basic security check for path traversal
            if (str_contains($entry, '..')) {
                $errors[] = $entry.' (path traversal detected)';
                continue;
            }

            $targetPath = $targetDir.'/'.$entry;
            $targetDirPath = dirname($targetPath);

            if (! is_dir($targetDirPath)) {
                mkdir($targetDirPath, 0755, true);
            }

            if (copy('zip://'.$zipPath.'#'.$entry, $targetPath)) {
                $extracted++;
            } else {
                $errors[] = $entry.' (gagal mengekstrak)';
            }
        }

        $zip->close();

        $message = "✅ {$extracted} file berhasil dipulihkan.";
        if (! empty($skipped)) {
            $message .= ' '.count($skipped).' file dilewati.';
        }
        if (! empty($errors)) {
            $message .= ' '.count($errors).' error.';
        }

        Log::info('Storage restore completed', [
            'file' => basename($zipPath),
            'extracted' => $extracted,
            'skipped' => count($skipped),
            'errors' => count($errors),
        ]);

        return [
            'success' => true,
            'message' => $message,
            'extracted' => $extracted,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    public function preview(string $zipPath): array
    {
        if (! file_exists($zipPath)) {
            throw new \RuntimeException('File ZIP tidak ditemukan.');
        }

        $zip = new ZipArchive;
        $result = $zip->open($zipPath);

        if ($result !== true) {
            throw new \RuntimeException('Gagal membuka file ZIP.');
        }

        $entries = [];
        $totalSize = 0;
        $folders = [];
        $hasRootFiles = false;

        for ($i = 0; $i < $zip->count(); $i++) {
            $entry = $zip->getNameIndex($i);
            $stat = $zip->statIndex($i);

            if ($entry === false) {
                continue;
            }

            $parts = explode('/', $entry);
            if (count($parts) > 1) {
                $topFolder = $parts[0];
                if (!in_array($topFolder, $folders)) {
                    $folders[] = $topFolder;
                }
            } else {
                $hasRootFiles = true;
            }

            if ($i < 100) { // Limit preview items
                $entries[] = [
                    'name' => $entry,
                    'size' => $stat['size'] ?? 0,
                    'compressed' => $stat['comp_size'] ?? 0,
                    'is_dir' => str_ends_with($entry, '/'),
                ];
            }

            $totalSize += $stat['size'] ?? 0;
        }

        if ($hasRootFiles) {
            $folders[] = '.'; // Symbol for root files
        }

        $totalEntries = $zip->count();
        $validation = $this->validateZipEntries($zip);
        $zip->close();

        return [
            'total_entries' => $totalEntries,
            'total_size' => $totalSize,
            'entries' => $entries,
            'folders' => $folders,
            'validation' => $validation,
        ];
    }

    private function deleteDirectory(string $dir): bool
    {
        if (! file_exists($dir)) {
            return true;
        }

        if (! is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (! $this->deleteDirectory($dir.DIRECTORY_SEPARATOR.$item)) {
                return false;
            }
        }

        return rmdir($dir);
    }


    protected function validateZipEntries(ZipArchive $zip): array
    {
        $issues = [];
        $totalUncompressed = 0;
        $totalCompressed = 0;

        for ($i = 0; $i < $zip->count(); $i++) {
            $entry = $zip->getNameIndex($i);
            $stat = $zip->statIndex($i);

            if ($entry === false) {
                continue;
            }

            if (preg_match('@(^|/)\.\.(/|$)@', $entry) || str_starts_with($entry, '/')) {
                $issues[] = 'path_traversal: '.$entry;
            }

            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (in_array($ext, $this->blockedExtensions)) {
                $issues[] = 'blocked_extension: '.$entry;
            }

            $uncompressedSize = $stat['size'] ?? 0;
            $compressedSize = $stat['comp_size'] ?? 1;

            $totalUncompressed += $uncompressedSize;
            $totalCompressed += $compressedSize;

            if ($compressedSize > 0 && $uncompressedSize / $compressedSize > $this->maxCompressionRatio) {
                $issues[] = 'high_compression: '.$entry.' ('.round($uncompressedSize / $compressedSize).'x)';
            }
        }

        if ($totalUncompressed > $this->maxExtractedSize) {
            $issues[] = 'total_size_exceeded: '.$this->formatBytes($totalUncompressed).' (max '.$this->formatBytes($this->maxExtractedSize).')';
        }

        return [
            'valid' => empty($issues),
            'message' => empty($issues) ? 'File ZIP aman.' : 'Ditemukan '.count($issues).' masalah.',
            'issues' => $issues,
        ];
    }

    protected function formatBytes(int $bytes): string
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
