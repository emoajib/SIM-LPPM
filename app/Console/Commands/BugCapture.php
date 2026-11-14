<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DevEvent;
use App\Services\DevEventRecorder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Mencatat kelas bug baru ke registry + stub test + dev_events (W4-H2.7).
 *
 * Tujuan: saat bug ditemukan, konteksnya langsung tersimpan (bukan hanya di
 * percakapan) sehingga bisa dicek ulang lewat `history:brief <area>` dan
 * dijaga oleh test yang ditandai `Regression: BC-xx`.
 */
class BugCapture extends Command
{
    protected $signature = 'bug:capture
        {gejala : Gejala singkat yang terlihat pengguna}
        {--area=umum : Area kerja (modern, final-report, amandemen, ...)}
        {--bc= : Kode BC yang dipakai (default: nomor berikutnya)}
        {--cause= : Akar masalah (opsional)}
        {--fix= : Perbaikan yang sudah/akan dilakukan (opsional)}
        {--test= : Path test penjaga (default: tests/Feature/Regression/BCxxTest.php)}
        {--no-test : Jangan buat stub test}
        {--dry-run : Tampilkan rencana tanpa menulis file/DB}';

    protected $description = 'Catat kelas bug baru (docs/regressions) + stub test + dev_event';

    public function handle(): int
    {
        $gejala = trim((string) $this->argument('gejala'));
        if ($gejala === '') {
            $this->error('Gejala tidak boleh kosong.');

            return self::FAILURE;
        }

        $area = trim((string) $this->option('area')) ?: 'umum';

        $code = $this->resolveCode();
        if ($code === null) {
            return self::FAILURE;
        }

        $slug = Str::limit(Str::slug($gejala), 60, '');
        if ($slug === '') {
            $slug = 'tanpa-judul';
        }

        $bcPath = base_path("docs/regressions/{$code}-{$slug}.md");

        $testPath = null;
        if (! $this->option('no-test')) {
            $testPath = trim((string) $this->option('test')) ?: 'tests/Feature/Regression/'.$this->testClassName($code).'.php';

            if (! str_starts_with($testPath, 'tests/') || ! str_ends_with($testPath, '.php')) {
                $this->error('Path test harus di dalam tests/ dan berakhiran .php.');

                return self::FAILURE;
            }

            if (File::exists(base_path($testPath))) {
                $this->error("Test sudah ada: {$testPath}. Gunakan --test=<path lain> atau --no-test.");

                return self::FAILURE;
            }
        }

        if (File::exists($bcPath)) {
            $this->error("Entri sudah ada: docs/regressions/{$code}-{$slug}.md");

            return self::FAILURE;
        }

        $cause = trim((string) $this->option('cause')) ?: '(belum dianalisa)';
        $fix = trim((string) $this->option('fix')) ?: '(belum diperbaiki)';

        if ($this->option('dry-run')) {
            $this->info('=== DRY RUN (tidak ada file/DB yang ditulis) ===');
            $this->line("Kode BC   : {$code}");
            $this->line("Area      : {$area}");
            $this->line("Entri     : docs/regressions/{$code}-{$slug}.md");
            $this->line('Stub test : '.($testPath ?? '(tidak dibuat)'));

            return self::SUCCESS;
        }

        File::ensureDirectoryExists(dirname($bcPath));
        File::put($bcPath, $this->bugClassDocument($code, $gejala, $area, $cause, $fix, $testPath));

        if ($testPath !== null) {
            File::ensureDirectoryExists(dirname(base_path($testPath)));
            File::put(base_path($testPath), $this->testStub($code, $gejala));
        }

        $event = app(DevEventRecorder::class)->record(DevEvent::TYPE_BUG, "{$code}: {$gejala}", [
            'area' => $area,
            'bc_code' => $code,
            'status' => 'open',
            'payload' => array_filter([
                'gejala' => $gejala,
                'cause' => $cause,
                'fix' => $fix,
                'test' => $testPath,
            ]),
        ]);

        $this->info("Kelas bug {$code} dicatat.");
        $this->line("  Entri     : docs/regressions/{$code}-{$slug}.md");
        $this->line('  Stub test : '.($testPath ?? '(tidak dibuat)'));
        $this->line('  Dev event : '.($event instanceof DevEvent
            ? $event->id
            : '(tidak tercatat — tabel dev_events belum ada?)'));

        $this->newLine();
        $this->line('<comment>Langkah berikutnya:</comment>');
        $this->line('  1. Tulis assertion yang GAGAL mereproduksi bug (ganti markTestIncomplete).');
        $this->line('  2. Perbaiki bug, pastikan assertion LULUS.');
        $this->line("  3. Commit: fix: <ringkasan> [{$code}] (body: Why / BC / Test).");
        $this->line('  4. php artisan regression:check');
        $this->line('  5. Bila kelas bug terbukti berulang, tambahkan barisnya ke tabel docs/regressions/README.md.');

        return self::SUCCESS;
    }

    /**
     * Kode BC eksplisit (divalidasi) atau nomor berikutnya dari registry.
     */
    private function resolveCode(): ?string
    {
        $explicit = trim((string) $this->option('bc'));

        if ($explicit !== '') {
            if (! preg_match('/^BC-\d{2,3}$/', $explicit)) {
                $this->error('Format kode BC tidak valid. Contoh: BC-09');

                return null;
            }

            if ($this->codeAlreadyExists($explicit)) {
                $this->error("Kode {$explicit} sudah dipakai di docs/regressions/.");

                return null;
            }

            return $explicit;
        }

        $max = 0;
        foreach (File::glob(base_path('docs/regressions/BC-*.md')) ?: [] as $file) {
            if (preg_match('/BC-(\d+)/', basename($file), $matches) === 1) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return 'BC-'.str_pad((string) ($max + 1), 2, '0', STR_PAD_LEFT);
    }

    private function codeAlreadyExists(string $code): bool
    {
        return File::glob(base_path("docs/regressions/{$code}-*.md")) !== [];
    }

    private function testClassName(string $code): string
    {
        return str_replace('-', '', $code).'Test';
    }

    private function bugClassDocument(
        string $code,
        string $gejala,
        string $area,
        string $cause,
        string $fix,
        ?string $testPath
    ): string {
        $lines = [];
        $lines[] = "# {$code} — {$gejala}";
        $lines[] = '';
        $lines[] = "**Gejala:** {$gejala}";
        $lines[] = '';
        $lines[] = "**Akar masalah:** {$cause}";
        $lines[] = '';
        $lines[] = '**Perbaikan:**';
        $lines[] = "- {$fix}";
        $lines[] = '';
        $lines[] = '**Penjaga:**';
        $lines[] = '- '.($testPath !== null ? "`{$testPath}`" : '(belum ada — wajib ditambahkan)');
        $lines[] = '';
        $lines[] = '**Aturan:**';
        $lines[] = '1. Test penjaga wajib GAGAL sebelum fix dan LULUS setelah fix.';
        $lines[] = "2. Setiap perubahan terkait wajib menyebut `{$code}` di body commit.";
        $lines[] = '3. Bila pola ini muncul di halaman/modul lain, periksa juga di sana (bukan hanya titik yang dilaporkan).';
        $lines[] = '';
        $lines[] = '**Kasus:** '.now()->format('Y-m-d')." — area `{$area}`";
        $lines[] = '';

        return implode("\n", $lines);
    }

    private function testStub(string $code, string $gejala): string
    {
        $safeTitle = str_replace("'", "\\'", $gejala);

        return <<<PHP
<?php

/**
 * Regression: {$code} — {$gejala}
 *
 * Stub dibuat otomatis oleh `php artisan bug:capture`.
 * Ganti markTestIncomplete() dengan assertion yang GAGAL sebelum fix dan
 * LULUS setelah fix. Jangan hapus marker "Regression: {$code}".
 */
it('{$code}: {$safeTitle}', function () {
    // TODO: reproduksi bug (buat request/aksi yang memicu gejala).
    \$this->markTestIncomplete('Tulis assertion yang mereproduksi {$code} sebelum fix.');
});

PHP;
    }
}
