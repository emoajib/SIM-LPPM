<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\CommunityService;
use App\Models\Faculty;
use App\Models\Identity;
use App\Models\Institution;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\User;
use App\Services\ProposalPdfService;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class ReportPdfPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected User $dosen;

    protected function setUp(): void
    {
        parent::setUp();

        if (! file_exists(storage_path('app/.installed'))) {
            file_put_contents(storage_path('app/.installed'), '');
        }

        config(['document-signatures.current_kid' => 'v1']);
        config(['document-signatures.keys.v1' => 'test-secret-standard-itsnu']);

        $this->seed(RoleSeeder::class);
        $this->seed(InstitutionSeeder::class);

        $institution = Institution::first();
        $faculty = Faculty::factory()->create(['institution_id' => $institution->id]);

        $this->dosen = User::factory()->create(['name' => 'Dosen']);
        $this->dosen->assignRole('dosen');
        $this->dosen->markEmailAsVerified();
        Identity::factory()->create(['user_id' => $this->dosen->id, 'faculty_id' => $faculty->id]);
    }

    public static function reportTypes(): array
    {
        return [
            'research' => [Research::class],
            'community_service' => [CommunityService::class],
        ];
    }

    /**
     * Regression: "8. RINGKASAN AKHIR" must start on the page AFTER the
     * Halaman Pengesahan page — it must not be orphaned at the bottom of the
     * approval page. Applies to both Penelitian and Pengabdian final reports.
     */
    #[DataProvider('reportTypes')]
    public function test_ringkasan_starts_on_new_page_after_approval_page(string $detailableClass)
    {
        if (! $this->hasPdftotext()) {
            $this->markTestSkipped('pdftotext (poppler-utils) is not available in this environment.');
        }

        $detailable = $detailableClass::factory()->create();
        $proposal = Proposal::factory()->create([
            'submitter_id' => $this->dosen->id,
            'detailable_id' => $detailable->id,
            'detailable_type' => $detailableClass,
        ]);

        $report = ProgressReport::create([
            'proposal_id' => $proposal->id,
            'reporting_period' => 'final',
            'reporting_year' => date('Y'),
            'status' => ReportStatus::APPROVED->value,
            'summary_update' => 'Ringkasan uji paginasi halaman baru.',
        ]);

        $pdfPath = app(ProposalPdfService::class)->exportReport($proposal, $report);

        $this->assertFileExists($pdfPath);

        $pages = $this->extractPages($pdfPath);

        $approvalPage = $this->pageContaining($pages, 'Dicetak pada');
        $ringkasanPage = $this->pageContaining($pages, 'RINGKASAN AKHIR');
        $summaryBodyPage = $this->pageContaining($pages, 'Ringkasan uji paginasi halaman baru.');

        $this->assertNotNull($approvalPage, 'Approval page marker ("Dicetak pada") not found in the PDF.');
        $this->assertNotNull($ringkasanPage, '"RINGKASAN AKHIR" heading not found in the PDF.');
        $this->assertNotNull($summaryBodyPage, 'Ringkasan body text not found in the PDF.');

        $this->assertGreaterThan(
            $approvalPage,
            $ringkasanPage,
            '"RINGKASAN AKHIR" must be on a page AFTER the Halaman Pengesahan page.'
        );
        $this->assertSame(
            $ringkasanPage,
            $summaryBodyPage,
            'The RINGKASAN heading must stay glued to its body text (page-break-after: avoid).'
        );
    }

    public function test_report_pdf_cache_filename_embeds_template_version()
    {
        $research = Research::factory()->create();
        $proposal = Proposal::factory()->create([
            'submitter_id' => $this->dosen->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
        ]);

        $report = ProgressReport::create([
            'proposal_id' => $proposal->id,
            'reporting_period' => 'final',
            'reporting_year' => date('Y'),
            'status' => ReportStatus::APPROVED->value,
            'summary_update' => 'Test cache key',
        ]);

        $service = app(ProposalPdfService::class);

        $first = $service->exportReport($proposal, $report);
        $second = $service->exportReport($proposal, $report);

        $this->assertStringContainsString('_t', basename($first), 'Cache file name must embed the template version.');
        $this->assertSame($first, $second, 'Repeated export of unchanged data must hit the same cache file.');
    }

    private function hasPdftotext(): bool
    {
        $out = [];
        exec('command -v pdftotext 2>/dev/null', $out, $code);

        return $code === 0 && ! empty($out);
    }

    /**
     * Extract per-page text using poppler's pdftotext.
     *
     * @return array<int, string> 1-based page number => extracted text
     */
    private function extractPages(string $pdfPath): array
    {
        $fpdi = new Fpdi;
        $pageCount = $fpdi->setSourceFile($pdfPath);

        $pages = [];
        for ($i = 1; $i <= $pageCount; $i++) {
            $out = [];
            exec('pdftotext -f '.$i.' -l '.$i.' -layout '.escapeshellarg($pdfPath).' - 2>/dev/null', $out, $code);
            $pages[$i] = $code === 0 ? implode("\n", $out) : '';
        }

        return $pages;
    }

    private function pageContaining(array $pages, string $needle): ?int
    {
        foreach ($pages as $page => $text) {
            if (str_contains($text, $needle)) {
                return $page;
            }
        }

        return null;
    }
}
