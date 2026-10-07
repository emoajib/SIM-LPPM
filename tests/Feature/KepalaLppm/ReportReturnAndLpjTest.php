<?php

use App\Enums\ProposalStatus;
use App\Enums\ReportStatus;
use App\Livewire\KepalaLppm\FinancialApproval;
use App\Livewire\Research\FinalReport\Show as ResearchFinalReportShow;
use App\Models\DailyNote;
use App\Models\Faculty;
use App\Models\Identity;
use App\Models\MandatoryOutput;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ProposalOutput;
use App\Models\Research;
use App\Models\User;
use App\Notifications\LpjReturned;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->faculty = Faculty::factory()->create();

    $this->dosen = User::factory()->create();
    $this->dosen->assignRole('dosen');
    Identity::factory()->create(['user_id' => $this->dosen->id, 'faculty_id' => $this->faculty->id, 'type' => 'dosen']);

    $this->dekan = User::factory()->create();
    $this->dekan->assignRole('dekan');
    Identity::factory()->create(['user_id' => $this->dekan->id, 'faculty_id' => $this->faculty->id]);

    $this->kepala = User::factory()->create();
    $this->kepala->assignRole('kepala lppm');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin lppm');

    $research = Research::factory()->create();
    $this->proposal = Proposal::factory()->create([
        'submitter_id' => $this->dosen->id,
        'detailable_type' => Research::class,
        'detailable_id' => $research->id,
        'status' => ProposalStatus::COMPLETED,
    ]);

    $this->wajibOutput = ProposalOutput::factory()->create([
        'proposal_id' => $this->proposal->id,
        'category' => 'Wajib',
        'type' => 'Jurnal Nasional',
    ]);
});

test('dosen cannot submit final report when mandatory outputs are empty', function () {
    $this->actingAs($this->dosen);
    session(['active_role' => 'dosen']);

    $report = ProgressReport::factory()->create([
        'proposal_id' => $this->proposal->id,
        'reporting_period' => 'final',
        'reporting_year' => (int) date('Y'),
        'status' => ReportStatus::DRAFT,
    ]);
    $report->addMedia(UploadedFile::fake()->createWithContent('laporan.pdf', "%PDF-1.4\n% Test PDF.\n%%EOF"))->toMediaCollection('substance_file');

    Livewire::test(ResearchFinalReportShow::class, ['proposal' => $this->proposal])
        ->call('submit')
        ->assertHasErrors(['mandatoryOutputs']);

    expect($report->fresh()->status)->toBe(ReportStatus::DRAFT);
});

test('dosen can submit final report when mandatory outputs are filled', function () {
    $this->actingAs($this->dosen);
    session(['active_role' => 'dosen']);

    $report = ProgressReport::factory()->create([
        'proposal_id' => $this->proposal->id,
        'reporting_period' => 'final',
        'reporting_year' => (int) date('Y'),
        'status' => ReportStatus::DRAFT,
    ]);
    $report->addMedia(UploadedFile::fake()->createWithContent('laporan.pdf', "%PDF-1.4\n% Test PDF.\n%%EOF"))->toMediaCollection('substance_file');

    Livewire::test(ResearchFinalReportShow::class, ['proposal' => $this->proposal])
        ->set("form.mandatoryOutputs.{$this->wajibOutput->id}.status_type", 'published')
        ->set("form.mandatoryOutputs.{$this->wajibOutput->id}.journal_title", 'Jurnal Uji Coba')
        ->set("form.mandatoryOutputs.{$this->wajibOutput->id}.article_title", 'Artikel Uji Coba')
        ->call('submit')
        ->assertHasNoErrors();

    expect($report->fresh()->status)->toBe(ReportStatus::SUBMITTED);
});

test('kepala lppm cannot approve final report when mandatory outputs are empty', function () {
    $this->actingAs($this->kepala);
    session(['active_role' => 'kepala lppm']);

    $report = ProgressReport::factory()->create([
        'proposal_id' => $this->proposal->id,
        'reporting_period' => 'final',
        'reporting_year' => (int) date('Y'),
        'status' => ReportStatus::APPROVED_BY_DEKAN,
    ]);

    Livewire::test(ResearchFinalReportShow::class, ['proposal' => $this->proposal])
        ->call('approve');

    expect($report->fresh()->status)->toBe(ReportStatus::APPROVED_BY_DEKAN);
});

test('kepala lppm can approve final report when mandatory outputs are filled', function () {
    $this->actingAs($this->kepala);
    session(['active_role' => 'kepala lppm']);

    $report = ProgressReport::factory()->create([
        'proposal_id' => $this->proposal->id,
        'reporting_period' => 'final',
        'reporting_year' => (int) date('Y'),
        'status' => ReportStatus::APPROVED_BY_DEKAN,
    ]);
    MandatoryOutput::factory()->create([
        'progress_report_id' => $report->id,
        'proposal_output_id' => $this->wajibOutput->id,
        'status_type' => 'published',
        'journal_title' => 'Jurnal Uji Coba',
        'article_title' => 'Artikel Uji Coba',
    ]);

    Livewire::test(ResearchFinalReportShow::class, ['proposal' => $this->proposal])
        ->call('approve')
        ->assertRedirect(route('kepala-lppm.report-approval'));

    expect($report->fresh()->status)->toBe(ReportStatus::APPROVED);
});

test('kepala lppm can return approved report to dosen for completion', function () {
    $this->actingAs($this->kepala);
    session(['active_role' => 'kepala lppm']);

    $report = ProgressReport::factory()->create([
        'proposal_id' => $this->proposal->id,
        'reporting_period' => 'final',
        'reporting_year' => (int) date('Y'),
        'status' => ReportStatus::APPROVED,
    ]);

    Livewire::test(ResearchFinalReportShow::class, ['proposal' => $this->proposal])
        ->set('approvalNotes', 'Luaran wajib belum dilengkapi, mohon dilengkapi dahulu.')
        ->call('reject')
        ->assertHasNoErrors();

    $report->refresh();
    expect($report->status)->toBe(ReportStatus::REJECTED)
        ->and($report->rejection_notes)->toBe('Luaran wajib belum dilengkapi, mohon dilengkapi dahulu.')
        ->and($report->rejected_by)->toBe($this->kepala->id);
});

test('returned report reopens outputs form for dosen', function () {
    $this->actingAs($this->dosen);
    session(['active_role' => 'dosen']);

    ProgressReport::factory()->create([
        'proposal_id' => $this->proposal->id,
        'reporting_period' => 'final',
        'reporting_year' => (int) date('Y'),
        'status' => ReportStatus::REJECTED,
        'rejection_notes' => 'Luaran wajib belum dilengkapi.',
    ]);

    Livewire::test(ResearchFinalReportShow::class, ['proposal' => $this->proposal])
        ->assertSet('canEdit', true)
        ->assertSet('isFinalReportDraft', true)
        ->assertSee('Luaran Wajib', false);
});

test('dekan can correct own approval by returning report', function () {
    $this->actingAs($this->dekan);
    session(['active_role' => 'dekan']);

    $report = ProgressReport::factory()->create([
        'proposal_id' => $this->proposal->id,
        'reporting_period' => 'final',
        'reporting_year' => (int) date('Y'),
        'status' => ReportStatus::APPROVED_BY_DEKAN,
    ]);

    Livewire::test(ResearchFinalReportShow::class, ['proposal' => $this->proposal])
        ->set('approvalNotes', 'Ternyata ada data yang kurang tepat, mohon diperbaiki.')
        ->call('reject')
        ->assertHasNoErrors();

    expect($report->fresh()->status)->toBe(ReportStatus::REJECTED);
});

test('kepala lppm can return lpj with notes readable by dosen', function () {
    Notification::fake();

    $this->actingAs($this->kepala);
    session(['active_role' => 'kepala lppm']);

    DailyNote::factory()->create([
        'proposal_id' => $this->proposal->id,
        'amount' => 500000,
        'activity_date' => now(),
        'activity_description' => 'Belanja ATK',
    ]);

    Livewire::test(FinancialApproval::class)
        ->call('openReturnModal', $this->proposal->id)
        ->set('returnNotes', 'Nominal bulan 3 tidak cocok dengan nota, mohon diperbaiki.')
        ->call('returnToDosen')
        ->assertHasNoErrors();

    $this->proposal->refresh();
    expect($this->proposal->logbook_approved_at)->toBeNull()
        ->and($this->proposal->logbook_rejection_notes)->toBe('Nominal bulan 3 tidak cocok dengan nota, mohon diperbaiki.')
        ->and($this->proposal->logbook_rejected_by)->toBe($this->kepala->id)
        ->and($this->proposal->logbook_rejected_at)->not->toBeNull();

    Notification::assertSentTo($this->dosen, LpjReturned::class);
});

test('admin lppm can return lpj to dosen', function () {
    Notification::fake();

    $this->actingAs($this->admin);
    session(['active_role' => 'admin lppm']);

    DailyNote::factory()->create([
        'proposal_id' => $this->proposal->id,
        'amount' => 500000,
        'activity_date' => now(),
        'activity_description' => 'Belanja ATK',
    ]);

    Livewire::test(FinancialApproval::class)
        ->call('openReturnModal', $this->proposal->id)
        ->set('returnNotes', 'Scan LPJ buram, mohon unggah ulang yang jelas.')
        ->call('returnToDosen')
        ->assertHasNoErrors();

    $this->proposal->refresh();
    expect($this->proposal->logbook_rejection_notes)->toBe('Scan LPJ buram, mohon unggah ulang yang jelas.')
        ->and($this->proposal->logbook_rejected_by)->toBe($this->admin->id);

    Notification::assertSentTo($this->dosen, LpjReturned::class);
});

test('return lpj requires meaningful notes', function () {
    $this->actingAs($this->kepala);
    session(['active_role' => 'kepala lppm']);

    Livewire::test(FinancialApproval::class)
        ->call('openReturnModal', $this->proposal->id)
        ->set('returnNotes', 'kurang')
        ->call('returnToDosen')
        ->assertHasErrors(['returnNotes']);

    expect($this->proposal->fresh()->logbook_rejection_notes)->toBeNull();
});

test('approve lpj is blocked when empty and clears return notes on re-approve', function () {
    $this->actingAs($this->kepala);
    session(['active_role' => 'kepala lppm']);

    // Kosong: tanpa catatan & tanpa scan → diblokir
    Livewire::test(FinancialApproval::class)
        ->call('approveLpj', $this->proposal->id);

    expect($this->proposal->fresh()->logbook_approved_at)->toBeNull();

    // Ada isi + bekas dikembalikan → sahkan ulang menghapus catatan
    DailyNote::factory()->create([
        'proposal_id' => $this->proposal->id,
        'amount' => 500000,
        'activity_date' => now(),
        'activity_description' => 'Belanja ATK',
    ]);
    $this->proposal->update([
        'logbook_rejection_notes' => 'Catatan lama yang sudah diperbaiki.',
        'logbook_rejected_by' => $this->kepala->id,
        'logbook_rejected_at' => now(),
    ]);

    Livewire::test(FinancialApproval::class)
        ->call('approveLpj', $this->proposal->id)
        ->assertHasNoErrors();

    $this->proposal->refresh();
    expect($this->proposal->logbook_approved_at)->not->toBeNull()
        ->and($this->proposal->logbook_rejection_notes)->toBeNull()
        ->and($this->proposal->logbook_rejected_by)->toBeNull()
        ->and($this->proposal->logbook_rejected_at)->toBeNull();
});

test('report state machine allows approved to be returned but not resubmitted directly', function () {
    expect(ReportStatus::APPROVED->canTransitionTo(ReportStatus::REJECTED))->toBeTrue()
        ->and(ReportStatus::APPROVED->canTransitionTo(ReportStatus::SUBMITTED))->toBeFalse()
        ->and(ReportStatus::APPROVED_BY_DEKAN->canTransitionTo(ReportStatus::REJECTED))->toBeTrue();
});
