<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\CommunityService;
use App\Models\Faculty;
use App\Models\Identity;
use App\Models\Institution;
use App\Models\Partner;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\User;
use App\Services\ProposalPdfService;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class ReportPdfAttachmentAndFinancialTest extends TestCase
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

        $this->dosen = User::factory()->create(['name' => 'Dosen Penguji']);
        $this->dosen->assignRole('dosen');
        $this->dosen->markEmailAsVerified();
        Identity::factory()->create(['user_id' => $this->dosen->id, 'faculty_id' => $faculty->id]);
    }

    /**
     * Test that image files (e.g. activity_photos_pkm or service_location_map) are successfully merged into PDF.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    public function test_export_report_merges_image_attachment_successfully()
    {
        Storage::fake('public');

        $pkm = CommunityService::factory()->create();
        $proposal = Proposal::factory()->create([
            'submitter_id' => $this->dosen->id,
            'detailable_id' => $pkm->id,
            'detailable_type' => CommunityService::class,
        ]);

        $report = ProgressReport::create([
            'proposal_id' => $proposal->id,
            'reporting_period' => 'final',
            'reporting_year' => date('Y'),
            'status' => ReportStatus::APPROVED->value,
            'summary_update' => 'Uji lampiran gambar laporan akhir.',
        ]);

        // Attach an image file to activity_photos_pkm
        $imageFile = UploadedFile::fake()->image('activity_photo.jpg', 800, 600);
        $report->addMedia($imageFile)
            ->toMediaCollection('activity_photos_pkm');

        $service = app(ProposalPdfService::class);
        $pdfPath = $service->exportReport($proposal, $report, true);

        $this->assertFileExists($pdfPath);

        // Verify page count with FPDI
        $fpdi = new Fpdi;
        $pageCount = $fpdi->setSourceFile($pdfPath);
        $this->assertGreaterThan(1, $pageCount, 'The PDF should contain pages including the merged image.');
    }

    /**
     * Test smart fallback to proposal partner commitment letter when report partner_agreement_letter is empty.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    public function test_export_report_smart_fallback_to_proposal_partner_document()
    {
        Storage::fake('public');

        $pkm = CommunityService::factory()->create();
        $proposal = Proposal::factory()->create([
            'submitter_id' => $this->dosen->id,
            'detailable_id' => $pkm->id,
            'detailable_type' => CommunityService::class,
        ]);

        // Partner with commitment letter image
        $partner = Partner::factory()->create();
        $proposal->partners()->attach($partner);
        $partnerImage = UploadedFile::fake()->image('surat_kesediaan_mitra.png', 600, 800);
        $partner->addMedia($partnerImage)
            ->toMediaCollection('commitment_letter');

        $report = ProgressReport::create([
            'proposal_id' => $proposal->id,
            'reporting_period' => 'final',
            'reporting_year' => date('Y'),
            'status' => ReportStatus::APPROVED->value,
            'summary_update' => 'Uji fallback surat mitra proposal.',
        ]);

        $service = app(ProposalPdfService::class);
        $pdfPath = $service->exportReport($proposal, $report, true);

        $this->assertFileExists($pdfPath);
        $fpdi = new Fpdi;
        $pageCount = $fpdi->setSourceFile($pdfPath);
        $this->assertGreaterThan(1, $pageCount);
    }

    /**
     * Test that financial report exports successfully with image scan approval and daily note evidence.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    public function test_export_financial_report_with_image_scan_and_evidence()
    {
        Storage::fake('public');

        $research = Research::factory()->create();
        $proposal = Proposal::factory()->create([
            'submitter_id' => $this->dosen->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
        ]);

        // Upload a scan approval image
        $scanImage = UploadedFile::fake()->image('scan_pengesahan.jpg', 800, 1000);
        $proposal->addMedia($scanImage)
            ->toMediaCollection('logbook_approval_file');

        $service = app(ProposalPdfService::class);
        $pdfPath = $service->exportFinancialReport($proposal, true);

        $this->assertFileExists($pdfPath);
        $fpdi = new Fpdi;
        $pageCount = $fpdi->setSourceFile($pdfPath);
        $this->assertGreaterThanOrEqual(3, $pageCount, 'Financial report should have cover, budget table, and approval scan.');
    }

    /**
     * Test that research report merges realization_file and partner_cooperation_proof properly.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    public function test_export_research_report_merges_realization_and_proof_files()
    {
        Storage::fake('public');

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
            'summary_update' => 'Uji lampiran bukti kerjasama dan realisasi penelitian.',
        ]);

        // Create a dummy PDF for realization_file
        $fpdiDummy = new Fpdi;
        $fpdiDummy->AddPage();
        $fpdiDummy->SetFont('Helvetica', '', 12);
        $fpdiDummy->Cell(0, 10, 'Realization Document', 0, 1);
        $dummyPdfPath = tempnam(sys_get_temp_dir(), 'test_realization_').'.pdf';
        $fpdiDummy->Output('F', $dummyPdfPath);

        $uploadedPdf = new UploadedFile($dummyPdfPath, 'realisasi.pdf', 'application/pdf', null, true);
        $report->addMedia($uploadedPdf)->toMediaCollection('realization_file');

        // Attach partner proof
        $coopProof = UploadedFile::fake()->image('bukti_kerjasama.jpg', 800, 600);
        $report->addMedia($coopProof)->toMediaCollection('partner_cooperation_proof');

        $service = app(ProposalPdfService::class);
        $pdfPath = $service->exportReport($proposal, $report, true);

        $this->assertFileExists($pdfPath);
        $fpdi = new Fpdi;
        $pageCount = $fpdi->setSourceFile($pdfPath);
        $this->assertGreaterThan(2, $pageCount, 'Research report should include realization and partner proof pages.');
    }
}
