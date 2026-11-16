<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Sumber tunggal registry kelas bug (BC-xx) dan test penjaganya.
 *
 * Dipakai oleh `regression:check`, `regression:health`, dan `digest:weekly`
 * agar definisi "BC punya penjaga" tidak divergen antar command.
 */
class RegressionRegistry
{
    /**
     * Kode BC yang punya entri di docs/regressions.
     *
     * @return array<int, string>
     */
    public function codes(): array
    {
        $codes = [];

        foreach (File::glob(base_path('docs/regressions/BC-*.md')) ?: [] as $file) {
            if (preg_match('/BC-(\d{2,3})/', basename($file), $matches) === 1) {
                $codes[] = 'BC-'.$matches[1];
            }
        }

        $codes = array_values(array_unique($codes));
        sort($codes);

        return $codes;
    }

    /**
     * Kode BC => path entri relatif.
     *
     * @return array<string, string>
     */
    public function entries(): array
    {
        $entries = [];

        foreach (File::glob(base_path('docs/regressions/BC-*.md')) ?: [] as $file) {
            if (preg_match('/BC-(\d{2,3})/', basename($file), $matches) !== 1) {
                continue;
            }

            $entries['BC-'.$matches[1]] = str_replace(base_path().'/', '', $file);
        }

        ksort($entries);

        return $entries;
    }

    /**
     * Kode BC => daftar test yang memuat marker `Regression: BC-xx`.
     *
     * @return array<string, array<int, string>>
     */
    public function guards(): array
    {
        $guards = [];

        foreach (File::allFiles(base_path('tests')) as $file) {
            if (! str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            $content = $file->getContents();

            if (preg_match_all('/Regression:\s*BC-(\d{2,3})/', $content, $matches) === false) {
                continue;
            }

            $relative = str_replace(base_path().'/', '', $file->getPathname());

            foreach ($matches[1] as $code) {
                $guards['BC-'.$code][] = $relative;
            }
        }

        ksort($guards);

        return $guards;
    }

    /**
     * Kode BC yang muncul di test tetapi tidak ada di registry.
     *
     * @return array<int, string>
     */
    public function missingInDocs(): array
    {
        $guards = $this->guards();

        return array_values(array_diff(array_keys($guards), $this->codes()));
    }

    /**
     * Kode BC di registry yang belum punya test penjaga.
     *
     * @return array<int, string>
     */
    public function missingInTests(): array
    {
        return array_values(array_diff($this->codes(), array_keys($this->guards())));
    }

    /**
     * Judul singkat dari baris pertama entri BC.
     */
    public function title(string $code): ?string
    {
        $path = $this->entries()[$code] ?? null;
        if ($path === null) {
            return null;
        }

        $firstLine = strtok((string) File::get(base_path($path)), "\n") ?: '';

        if (preg_match('/^#\s*BC-\d{2,3}\s*[—-]\s*(.+)$/u', trim($firstLine), $matches) === 1) {
            return trim($matches[1]);
        }

        return trim($firstLine);
    }

    /**
     * Commit terakhir yang menyentuh entri BC.
     *
     * @return array{hash: string, date: string, subject: string}|null
     */
    public function lastCommit(string $code): ?array
    {
        $process = new Process([
            'git', '-C', base_path(),
            'log', '-1',
            '--date=short',
            '--pretty=format:%h%x1f%ad%x1f%s',
            '--',
            "docs/regressions/{$code}-*.md",
        ], base_path());

        $process->setTimeout(10);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $parts = explode("\x1f", trim($process->getOutput()), 3);

        if (count($parts) < 3) {
            return null;
        }

        return [
            'hash' => $parts[0],
            'date' => $parts[1],
            'subject' => $parts[2],
        ];
    }
}
