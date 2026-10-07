<?php

// Regresi: eligibilitas pisah tipe + bukti luaran video.
use App\Enums\ProposalStatus;
use App\Enums\ReportStatus;
use App\Livewire\Forms\ReportForm;
use App\Livewire\Research\FinalReport\Show;
use App\Models\CommunityService;
use App\Models\MandatoryOutput;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ProposalOutput;
use App\Models\Research;
use App\Models\User;
use App\Services\LecturerEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function prevSemesterWindow(): array
{
    $now = now();
    $year = (int) $now->format('Y');
    $month = (int) $now->format('n');

    if ($month >= 9 || $month <= 2) {
        return [$month >= 9 ? $year : $year - 1, 'genap'];
    }

    return [$year - 1, 'ganjil'];
}

beforeEach(function () {
    $this->dosen = User::factory()->create();
    $this->svc = app(LecturerEligibilityService::class);
    [$this->prevYear, $this->prevSemester] = prevSemesterWindow();

    // Buat proposal periode lalu dengan satu luaran wajib video.
    $this->makeDebtProposal = function (string $detailable) {
        $detail = $detailable === Research::class
            ? Research::factory()->create()
            : CommunityService::factory()->create();

        $proposal = Proposal::factory()->create([
            'submitter_id' => $this->dosen->id,
            'detailable_type' => $detailable,
            'detailable_id' => $detail->id,
            'status' => ProposalStatus::APPROVED,
            'start_year' => $this->prevYear,
            'semester' => $this->prevSemester,
        ]);

        $this->videoOutput = ProposalOutput::factory()->create([
            'proposal_id' => $proposal->id,
            'category' => 'Wajib',
            'group' => 'Video',
            'type' => 'Video Kegiatan (Publikasi Youtube/Medsos)',
        ]);

        return $proposal;
    };

    $this->makeFinalReport = function (Proposal $proposal, $status) {
        return ProgressReport::factory()->create([
            'proposal_id' => $proposal->id,
            'reporting_period' => 'final',
            'reporting_year' => (int) date('Y'),
            'status' => $status,
        ]);
    };
});

test('hutang penelitian hanya memblokir usulan penelitian', function () {
    ($this->makeDebtProposal)(Research::class);

    $research = $this->svc->checkEligibility($this->dosen, 'research');
    $pkm = $this->svc->checkEligibility($this->dosen, 'pkm');
    $aliased = $this->svc->checkEligibility($this->dosen, 'community-service');

    expect($research['eligible'])->toBeFalse()
        ->and($pkm['eligible'])->toBeTrue()
        ->and($aliased['eligible'])->toBeTrue();
});

test('hutang pengabdian hanya memblokir usulan pengabdian', function () {
    ($this->makeDebtProposal)(CommunityService::class);

    expect($this->svc->checkEligibility($this->dosen, 'pkm')['eligible'])->toBeFalse()
        ->and($this->svc->checkEligibility($this->dosen, 'research')['eligible'])->toBeTrue();
});

test('laporan approved_by_dekan belum melunasi kewajiban', function () {
    $proposal = ($this->makeDebtProposal)(Research::class);
    $report = ($this->makeFinalReport)($proposal, ReportStatus::APPROVED_BY_DEKAN);
    MandatoryOutput::factory()->create([
        'progress_report_id' => $report->id,
        'proposal_output_id' => $this->videoOutput->id,
        'status_type' => 'submitted',
        'video_url' => 'https://youtu.be/contoh123',
    ]);

    $result = $this->svc->checkEligibility($this->dosen, 'research');

    expect($result['eligible'])->toBeFalse()
        ->and(implode(' ', $result['reasons']))->toContain('Laporan Akhir');
});

test('laporan approved plus link video melunasi kewajiban', function () {
    $proposal = ($this->makeDebtProposal)(Research::class);
    $report = ($this->makeFinalReport)($proposal, ReportStatus::APPROVED);
    MandatoryOutput::factory()->create([
        'progress_report_id' => $report->id,
        'proposal_output_id' => $this->videoOutput->id,
        'status_type' => 'submitted',
        'video_url' => 'https://youtu.be/contoh123',
    ]);

    expect($this->svc->checkEligibility($this->dosen, 'research')['eligible'])->toBeTrue()
        ->and($this->svc->checkEligibility($this->dosen, 'pkm')['eligible'])->toBeTrue();
});

test('baris luaran video tanpa url tidak dihitung memenuhi', function () {
    $proposal = ($this->makeDebtProposal)(Research::class);
    $report = ($this->makeFinalReport)($proposal, ReportStatus::APPROVED);
    $record = MandatoryOutput::factory()->create([
        'progress_report_id' => $report->id,
        'proposal_output_id' => $this->videoOutput->id,
        'status_type' => 'submitted',
        'video_url' => null,
        'journal_title' => '',
        'article_title' => '',
    ]);

    expect(LecturerEligibilityService::mandatoryOutputHasEvidence($record))->toBeFalse();

    $result = $this->svc->checkEligibility($this->dosen, 'research');

    expect($result['eligible'])->toBeFalse()
        ->and(implode(' ', $result['reasons']))->toContain('luaran wajib');
});

test('kelengkapan luaran dinilai per tipe', function () {
    expect(ReportForm::mandatoryDataIsComplete(
        ['status_type' => 'submitted'], 'Video Kegiatan (Publikasi Youtube/Medsos)', 'Video'
    ))->toBeFalse()
        ->and(ReportForm::mandatoryDataIsComplete(
            ['status_type' => 'submitted', 'video_url' => 'https://youtu.be/x'], 'Video Kegiatan (Publikasi Youtube/Medsos)', 'Video'
        ))->toBeTrue()
        ->and(ReportForm::mandatoryDataIsComplete(
            ['status_type' => 'published'], 'Jurnal Nasional', 'Jurnal'
        ))->toBeFalse()
        ->and(ReportForm::mandatoryDataIsComplete(
            ['status_type' => 'published', 'journal_title' => 'J', 'article_title' => 'A'], 'Jurnal Nasional', 'Jurnal'
        ))->toBeTrue();
});

test('normalisasi tipe menerima alias', function () {
    expect(LecturerEligibilityService::normalizeType('research'))->toBe('research')
        ->and(LecturerEligibilityService::normalizeType('pkm'))->toBe('pkm')
        ->and(LecturerEligibilityService::normalizeType('community-service'))->toBe('pkm')
        ->and(LecturerEligibilityService::normalizeType('community_service'))->toBe('pkm')
        ->and(LecturerEligibilityService::normalizeType(null))->toBeNull()
        ->and(LecturerEligibilityService::normalizeType('bogus'))->toBeNull();
});

test('dosen tidak bisa submit laporan akhir video tanpa url', function () {
    $this->actingAs($this->dosen);
    session(['active_role' => 'dosen']);

    $research = Research::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $this->dosen->id,
        'detailable_type' => Research::class,
        'detailable_id' => $research->id,
        'status' => ProposalStatus::COMPLETED,
    ]);
    $output = ProposalOutput::factory()->create([
        'proposal_id' => $proposal->id,
        'category' => 'Wajib',
        'group' => 'Video',
        'type' => 'Video Kegiatan (Publikasi Youtube/Medsos)',
    ]);
    $report = ProgressReport::factory()->create([
        'proposal_id' => $proposal->id,
        'reporting_period' => 'final',
        'reporting_year' => (int) date('Y'),
        'status' => ReportStatus::DRAFT,
    ]);
    $report->addMedia(UploadedFile::fake()->createWithContent('laporan.pdf', "%PDF-1.4\n% Test.\n%%EOF"))->toMediaCollection('substance_file');

    Livewire\Livewire::test(Show::class, ['proposal' => $proposal])
        ->set("form.mandatoryOutputs.{$output->id}.status_type", 'submitted')
        ->call('submit')
        ->assertHasErrors(['mandatoryOutputs']);

    expect($report->fresh()->status)->toBe(ReportStatus::DRAFT);
});

test('laporan akhir pkm bisa diajukan tanpa file rps (opsional)', function () {
    $this->actingAs($this->dosen);

    $detail = CommunityService::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $this->dosen->id,
        'detailable_type' => CommunityService::class,
        'detailable_id' => $detail->id,
        'status' => ProposalStatus::COMPLETED,
    ]);
    $report = ProgressReport::factory()->create([
        'proposal_id' => $proposal->id,
        'reporting_period' => 'final',
        'reporting_year' => (int) date('Y'),
        'status' => ReportStatus::DRAFT,
    ]);
    $report->addMedia(UploadedFile::fake()->createWithContent('laporan.pdf', "%PDF-1.4\n% Test.\n%%EOF"))->toMediaCollection('substance_file');

    Livewire\Livewire::test(App\Livewire\CommunityService\FinalReport\Show::class, ['proposal' => $proposal])
        ->call('submit')
        ->assertHasNoErrors();

    expect($report->fresh()->status)->toBe(ReportStatus::SUBMITTED);
});

test('file rps tersimpan dan laporan pkm bisa diajukan', function () {
    $this->actingAs($this->dosen);

    $detail = CommunityService::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $this->dosen->id,
        'detailable_type' => CommunityService::class,
        'detailable_id' => $detail->id,
        'status' => ProposalStatus::COMPLETED,
    ]);

    $component = Livewire\Livewire::test(App\Livewire\CommunityService\FinalReport\Show::class, ['proposal' => $proposal])
        ->set('form.summaryUpdate', 'Ringkasan akhir PKM dengan RPS')
        ->set('form.keywordsInput', 'pkm; rps; test')
        ->set('substanceFile', UploadedFile::fake()->createWithContent('laporan.pdf', "%PDF-1.4\n% Test.\n%%EOF"))
        ->set('rpsFile', UploadedFile::fake()->createWithContent('rps.pdf', "%PDF-1.4\n% Test.\n%%EOF"))
        ->call('save');

    $component->assertHasNoErrors();

    $report = $proposal->progressReports()->where('reporting_period', 'final')->first();
    expect($report)->not->toBeNull()
        ->and($report->hasMedia('rps_file'))->toBeTrue();

    $component->call('submit')->assertHasNoErrors();

    expect($report->fresh()->status)->toBe(ReportStatus::SUBMITTED);
});

test('file poster lama tetap dibaca sebagai rps', function () {
    $this->actingAs($this->dosen);

    $detail = CommunityService::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $this->dosen->id,
        'detailable_type' => CommunityService::class,
        'detailable_id' => $detail->id,
        'status' => ProposalStatus::COMPLETED,
    ]);
    $report = ProgressReport::factory()->create([
        'proposal_id' => $proposal->id,
        'reporting_period' => 'final',
        'reporting_year' => (int) date('Y'),
        'status' => ReportStatus::DRAFT,
    ]);
    $report->addMedia(UploadedFile::fake()->createWithContent('laporan.pdf', "%PDF-1.4\n% Test.\n%%EOF"))->toMediaCollection('substance_file');
    $report->addMedia(UploadedFile::fake()->createWithContent('poster.pdf', "%PDF-1.4\n% Test.\n%%EOF"))->toMediaCollection('presentation_file');

    Livewire\Livewire::test(App\Livewire\CommunityService\FinalReport\Show::class, ['proposal' => $proposal])
        ->call('submit')
        ->assertHasNoErrors();

    expect($report->fresh()->status)->toBe(ReportStatus::SUBMITTED);
});

test('luaran tambahan video tanpa url memblokir submit laporan akhir', function () {
    $this->actingAs($this->dosen);

    $research = Research::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $this->dosen->id,
        'detailable_type' => Research::class,
        'detailable_id' => $research->id,
        'status' => ProposalStatus::COMPLETED,
    ]);
    $videoTambahan = ProposalOutput::factory()->create([
        'proposal_id' => $proposal->id,
        'category' => 'Tambahan',
        'group' => 'Video',
        'type' => 'Video Kegiatan (Publikasi Youtube/Medsos)',
    ]);
    $report = ProgressReport::factory()->create([
        'proposal_id' => $proposal->id,
        'reporting_period' => 'final',
        'reporting_year' => (int) date('Y'),
        'status' => ReportStatus::DRAFT,
    ]);
    $report->addMedia(UploadedFile::fake()->createWithContent('laporan.pdf', "%PDF-1.4\n% Test.\n%%EOF"))->toMediaCollection('substance_file');

    $component = Livewire\Livewire::test(Show::class, ['proposal' => $proposal])
        ->call('submit')
        ->assertHasErrors(['additionalOutputs']);

    expect($report->fresh()->status)->toBe(ReportStatus::DRAFT);

    $component
        ->set("form.additionalOutputs.{$videoTambahan->id}.video_url", 'https://youtu.be/contoh123')
        ->call('submit')
        ->assertHasNoErrors();

    expect($report->fresh()->status)->toBe(ReportStatus::SUBMITTED);
});
