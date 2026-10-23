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
use Barryvdh\DomPDF\Facade\Pdf;
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

    /**
     * Test that financial approval page template can be generated and has 1 page.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    public function test_export_financial_approval_template_generates_single_page_pdf()
    {
        $research = Research::factory()->create();
        $proposal = Proposal::factory()->create([
            'submitter_id' => $this->dosen->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'title' => 'Uji Template Pengesahan Finansial',
        ]);

        $service = app(ProposalPdfService::class);
        $pdfPath = $service->exportFinancialApprovalTemplate($proposal);

        $this->assertFileExists($pdfPath);
        $fpdi = new Fpdi;
        $pageCount = $fpdi->setSourceFile($pdfPath);
        $this->assertEquals(1, $pageCount, 'Financial approval template must be exactly 1 page.');
    }

    /**
     * Test that media files are found after the identity-based folder changes.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    public function test_get_local_pdf_path_resolves_relocated_identity_folder()
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
            'summary_update' => 'Uji relokasi folder media.',
        ]);

        $dummyPdfPath = tempnam(sys_get_temp_dir(), 'test_relocated_').'.pdf';
        file_put_contents(
            $dummyPdfPath,
            Pdf::loadHTML('<h1>Relocated Attachment</h1>')->output()
        );

        $media = $report->addMedia(new UploadedFile($dummyPdfPath, 'relocated.pdf', 'application/pdf', null, true))
            ->toMediaCollection('official_report_pkm');

        $oldRelativePath = 'official_report_pkm/0058120320025-hasan-mashuri/'.$media->id.'/'.$media->file_name;
        Storage::disk('public')->move($media->getPathRelativeToRoot(), $oldRelativePath);

        $this->assertFalse(Storage::disk('public')->exists($media->getPathRelativeToRoot()));
        $this->assertTrue(Storage::disk('public')->exists($oldRelativePath));

        $resolved = app(ProposalPdfService::class)->getLocalPdfPath($media->fresh());

        $this->assertNotNull($resolved);
        $this->assertSame(
            realpath(Storage::disk('public')->path($oldRelativePath)),
            realpath($resolved)
        );
    }

    /**
     * Test that a relocated attachment is merged into the exported report PDF.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    public function test_export_report_includes_attachment_relocated_after_profile_change()
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
            'summary_update' => 'Uji lampiran setelah perubahan profil.',
        ]);

        request()->query->remove('preview');
        request()->query->remove('refresh');

        $service = app(ProposalPdfService::class);
        $basePdfPath = $service->exportReport($proposal, $report, false);
        $basePages = (new Fpdi)->setSourceFile($basePdfPath);

        $dummyPdfPath = tempnam(sys_get_temp_dir(), 'test_profile_change_').'.pdf';
        file_put_contents(
            $dummyPdfPath,
            Pdf::loadHTML('<h1>Attachment After Profile Change</h1>')->output()
        );

        $media = $report->addMedia(new UploadedFile($dummyPdfPath, 'profile-change.pdf', 'application/pdf', null, true))
            ->toMediaCollection('official_report_pkm');

        $oldRelativePath = 'official_report_pkm/0058120320025-hasan-mashuri/'.$media->id.'/'.$media->file_name;
        Storage::disk('public')->move($media->getPathRelativeToRoot(), $oldRelativePath);

        $this->dosen->update(['name' => 'Hasan Mashuri']);
        $this->dosen->identity->update(['identity_id' => '7700015028']);

        $relocatedMedia = $report->fresh()->getFirstMedia('official_report_pkm');
        $this->assertNotNull($relocatedMedia);
        $this->assertNotNull($service->getLocalPdfPath($relocatedMedia));

        request()->merge(['preview' => 1, 'refresh' => 1]);
        $pdfPath = $service->exportReport($proposal, $report->fresh(), true);

        $this->assertFileExists($pdfPath);
        $this->assertGreaterThan($basePages, (new Fpdi)->setSourceFile($pdfPath));
    }

    /**
     * Test that submitter can download financial approval template via route.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    public function test_dosen_can_download_approval_template_route()
    {
        $research = Research::factory()->create();
        $proposal = Proposal::factory()->create([
            'submitter_id' => $this->dosen->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
        ]);

        $response = $this->actingAs($this->dosen)
            ->get(route('financial-reports.approval-template', ['proposal' => $proposal, 'download' => 'true']));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
