<?php

namespace Tests\Feature\Console;

use App\Models\DevEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * W4-H2.7 — `bug:capture` harus menulis tiga hal: entri BC, stub test, dev_event.
 *
 * Kalau salah satu hilang, bug baru tidak akan pernah terjaga (BC-08):
 * entri ada tapi test tidak pernah dibuat, atau sebaliknya.
 */
class BugCaptureTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $baseline = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseline = array_merge(
            glob(base_path('docs/regressions/BC-*.md')) ?: [],
            glob(base_path('tests/Feature/Regression/BC*.php')) ?: []
        );
    }

    protected function tearDown(): void
    {
        $current = array_merge(
            glob(base_path('docs/regressions/BC-*.md')) ?: [],
            glob(base_path('tests/Feature/Regression/BC*.php')) ?: []
        );

        foreach (array_diff($current, $this->baseline) as $file) {
            if (is_file($file)) {
                File::delete($file);
            }
        }

        parent::tearDown();
    }

    public function test_creates_bug_class_entry_and_dev_event(): void
    {
        $this->artisan('bug:capture', [
            'gejala' => 'Tombol simpan bocor di status final',
            '--area' => 'final-report',
            '--cause' => 'state machine tidak dicek di view',
            '--fix' => 'tambah guard status',
        ])->assertExitCode(0);

        $entry = $this->newFile('docs/regressions/BC-*.md', 'tombol-simpan-bocor');

        expect(File::exists($entry))->toBeTrue();

        $contents = File::get($entry);

        expect($contents)
            ->toContain('Tombol simpan bocor di status final')
            ->toContain('state machine tidak dicek di view');

        $this->assertDatabaseHas('dev_events', [
            'type' => DevEvent::TYPE_BUG,
            'area' => 'final-report',
            'status' => 'open',
        ]);
    }

    public function test_generated_stub_is_incomplete_so_ci_stays_green(): void
    {
        $this->artisan('bug:capture', [
            'gejala' => 'Filter tahun hilang di halaman modern',
            '--area' => 'modern',
        ])->assertExitCode(0);

        $stub = $this->newFile('tests/Feature/Regression/BC*.php', 'Test.php');

        $contents = File::get($stub);

        expect($contents)
            ->toContain('Regression: BC-')
            ->toContain('markTestIncomplete')
            ->toContain("it('BC-");
    }

    public function test_dry_run_writes_nothing(): void
    {
        $docs = glob(base_path('docs/regressions/BC-*.md')) ?: [];
        $tests = glob(base_path('tests/Feature/Regression/BC*.php')) ?: [];

        $this->artisan('bug:capture', [
            'gejala' => 'Contoh gejala dry run',
            '--dry-run' => true,
        ])->assertExitCode(0);

        expect(glob(base_path('docs/regressions/BC-*.md')) ?: [])->toBe($docs)
            ->and(glob(base_path('tests/Feature/Regression/BC*.php')) ?: [])->toBe($tests);
    }

    public function test_rejects_used_bug_code(): void
    {
        $this->artisan('bug:capture', [
            'gejala' => 'Gejala pertama',
            '--bc' => 'BC-97',
        ])->assertExitCode(0);

        $this->artisan('bug:capture', [
            'gejala' => 'Gejala kedua',
            '--bc' => 'BC-97',
        ])->assertExitCode(1);
    }

    public function test_rejects_invalid_bug_code_format(): void
    {
        $this->artisan('bug:capture', [
            'gejala' => 'Gejala format salah',
            '--bc' => 'BCX',
        ])->assertExitCode(1);
    }

    /**
     * Cari file yang baru dibuat (tidak ada di baseline).
     */
    private function newFile(string $pattern, string $needle): string
    {
        $files = array_values(array_filter(
            glob(base_path($pattern)) ?: [],
            fn (string $file) => ! in_array($file, $this->baseline, true)
                && str_contains(basename($file), $needle)
        ));

        expect($files)->not->toBeEmpty("Tidak ada file baru untuk pola {$pattern}");

        return $files[0];
    }
}
