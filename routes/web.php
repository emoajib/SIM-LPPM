<?php

use App\Exports\UsersTemplateExport;
use App\Http\Controllers\AdminLppm\ManualBookUploadController;
use App\Http\Controllers\DailyNoteExportController;
use App\Http\Controllers\DocumentSignatureVerificationController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\LetterTypeController;
use App\Http\Controllers\LetterVerificationController;
use App\Http\Controllers\MediaDownloadController;
use App\Http\Controllers\ProposalExportController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\ReportVerificationController;
use App\Http\Controllers\ReviewExportController;
use App\Http\Controllers\RoleSwitcherController;
use App\Http\Controllers\Settings\BackupDownloadController;
use App\Http\Controllers\Settings\ProposalTemplateUploadController;
use App\Http\Controllers\SintaExportController;
use App\Livewire\Admin\Archive\ManageArchives;
use App\Livewire\Admin\EligibilityDashboard;
use App\Livewire\AdminLppm\ExportSinta;
use App\Livewire\AdminLppm\Letter\Archive;
use App\Livewire\AdminLppm\Letter\LetterTypeManagement;
use App\Livewire\AdminLppm\ManualBook\Form as ManualBookForm;
use App\Livewire\AdminLppm\ManualBook\Index as ManualBookIndex;
use App\Livewire\AdminLppm\Monev\MonevIndex;
use App\Livewire\AdminLppm\ReviewerAssignment;
use App\Livewire\AdminLppm\ReviewerWorkload;
use App\Livewire\AdminLppm\ReviewMonitoring;
use App\Livewire\AdminLppm\SyncSinta;
use App\Livewire\Dashboard;
use App\Livewire\Dashboard\Dosen\LetterDashboard;
use App\Livewire\Dashboard\Dosen\LetterManualRequest;
use App\Livewire\Dashboard\KepalaLppm\LetterApproval;
use App\Livewire\Dekan\ApprovalHistory;
use App\Livewire\Dekan\ProposalIndex as DekanProposalIndex;
use App\Livewire\Dekan\ReportIndex;
use App\Livewire\Iku\IkuDashboard;
use App\Livewire\Iku\IkuVerification;
use App\Livewire\Installer\InstallerWizard;
use App\Livewire\Kaprodi\ProposalValidation;
use App\Livewire\KepalaLppm\FinalDecision;
use App\Livewire\KepalaLppm\InitialApproval;
use App\Livewire\KepalaLppm\Monev\MonevRecap;
use App\Livewire\KepalaLppm\ReportApproval;
use App\Livewire\ManualBook\Index as ManualBookUserIndex;
use App\Livewire\Notifications\NotificationCenter;
use App\Livewire\Rektor\MonevDashboard;
use App\Livewire\Reports\CommunityService;
use App\Livewire\Reports\IkuReport;
use App\Livewire\Reports\InstitutionalReportMonitoring;
use App\Livewire\Reports\MonevReport;
use App\Livewire\Reports\OutputReports;
use App\Livewire\Reports\PartnerCollaboration;
use App\Livewire\Reports\Research;
use App\Livewire\Reports\ReviewerReport;
use App\Livewire\Reports\Show;
use App\Livewire\Research\Proposal\Create;
use App\Livewire\Research\Proposal\Edit;
use App\Livewire\Research\Proposal\Index;
use App\Livewire\Review\CommunityService as ReviewCommunityService;
use App\Livewire\Review\Research as ReviewResearch;
use App\Livewire\Review\ReviewHistory;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\AuditLog;
use App\Livewire\Settings\ManualBookSetting;
use App\Livewire\Settings\MasterData;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\ProposalSchedule;
use App\Livewire\Settings\ProposalTemplate;
use App\Livewire\Settings\SettingsIndex;
use App\Livewire\Settings\TwoFactor;
use App\Livewire\Users\Create as UsersCreate;
use App\Livewire\Users\Edit as UsersEdit;
use App\Livewire\Users\Import;
use App\Livewire\Users\Index as UsersIndex;
use App\Livewire\Users\Show as UsersShow;
use App\Models\Letter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Fortify\Features;
use Maatwebsite\Excel\Facades\Excel;

// Installer Route - Available only when not installed
Route::get('install', InstallerWizard::class)
    ->name('install');

// Route removed: /dev/migrate was a security risk - use CLI instead

Route::get('/health-check', HealthCheckController::class)->name('health.check');

Route::redirect('/', 'dashboard', 302);

Route::get('dashboard', Dashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('laporan-penelitian', Research::class)
        ->middleware(['permission:module_laporan'])
        ->name('reports.research');

    Route::get('laporan-pkm', CommunityService::class)
        ->middleware(['permission:module_laporan'])
        ->name('reports.pkm');

    Route::get('laporan-luaran', OutputReports::class)
        ->middleware(['permission:module_laporan'])
        ->name('reports.outputs');

    Route::get('laporan-mitra', PartnerCollaboration::class)
        ->middleware(['permission:module_laporan'])
        ->name('reports.partners');

    Route::get('/reports/iku', IkuReport::class)
        ->middleware(['permission:module_laporan'])
        ->name('reports.iku');

    Route::get('/reports/monitoring', InstitutionalReportMonitoring::class)
        ->middleware(['permission:module_laporan'])
        ->name('reports.monitoring');

    Route::get('laporan-monev', MonevReport::class)
        ->middleware(['permission:module_laporan'])
        ->name('reports.monev');

    Route::get('laporan-reviewer', ReviewerReport::class)
        ->middleware(['permission:module_laporan'])
        ->name('reports.reviewer');

    // User Management Routes
    Route::middleware(['role:admin lppm|superadmin'])->prefix('users')->name('users.')->group(function () {
        Route::get('/', UsersIndex::class)->name('index');
        Route::get('import', Import::class)->name('import');
        Route::get('import/template', function () {
            if (ob_get_level()) {
                ob_end_clean();
            }

            return Excel::download(new UsersTemplateExport, 'template-import-users.xlsx');
        })->name('import-template');
        Route::get('sync-sinta', SyncSinta::class)->name('sync-sinta');
        Route::get('create', UsersCreate::class)->name('create');
        Route::get('{user}', UsersShow::class)->name('show');
        Route::get('{user}/edit', UsersEdit::class)->name('edit');
    });

    // SINTA Export Page (Livewire)
    Route::get('export-sinta', ExportSinta::class)
        ->middleware(['permission:module_export_sinta'])
        ->name('export-sinta');

    // SINTA Export Direct Downloads (HTTP Controller — proper file response)
    Route::middleware(['permission:module_export_sinta'])->prefix('export-sinta')->name('export-sinta.')->group(function () {
        Route::get('research', [SintaExportController::class, 'downloadResearch'])
            ->name('research');
        Route::get('pkm', [SintaExportController::class, 'downloadPkm'])
            ->name('pkm');
    });

    // Research Routes
    Route::middleware(['permission:module_penelitian'])->prefix('research')->name('research.')->group(function () {
        Route::get('/', Index::class)->name('proposal.index');

        // Only dosen can create proposals
        Route::get('proposal/create', Create::class)
            ->middleware('role:dosen')
            ->name('proposal.create');

        Route::get('proposal/{proposal}', App\Livewire\Research\Proposal\Show::class)->name('proposal.show');
        Route::get('proposal/{proposal}/edit', Edit::class)->name('proposal.edit');

        Route::get('proposal-revision', App\Livewire\Research\ProposalRevision\Index::class)->name('proposal-revision.index');
        Route::get('proposal-revision/{proposal}', App\Livewire\Research\ProposalRevision\Show::class)->name('proposal-revision.show');

        // Laporan Kemajuan dihilangkan berdasarkan arahan simplifikasi
        Route::get('progress-report', App\Livewire\Research\ProgressReport\Index::class)->name('progress-report.index');
        Route::get('progress-report/{proposal}', Show::class)
            ->name('progress-report.show')
            ->defaults('type', 'research-progress');

        Route::get('final-report', App\Livewire\Research\FinalReport\Index::class)->name('final-report.index');
        Route::get('final-report/{proposal}', App\Livewire\Research\FinalReport\Show::class)
            ->name('final-report.show');

        Route::get('daily-note', App\Livewire\Research\DailyNote\Index::class)->name('daily-note.index');
        Route::get('daily-note/{proposal}', App\Livewire\Research\DailyNote\Show::class)->name('daily-note.show');
    });

    // Policy & Recognition Routes
    Route::middleware(['permission:module_rekognisi'])->prefix('recognition')->name('recognition.')->group(function () {
        Route::get('policy-involvement', App\Livewire\Lecturer\PolicyInvolvement\Index::class)->name('policy-involvement.index');
    });

    // Community Service Routes
    Route::middleware(['permission:module_pengabdian'])->prefix('community-service')->name('community-service.')->group(function () {
        Route::get('/', App\Livewire\CommunityService\Proposal\Index::class)->name('proposal.index');

        // Only dosen can create proposals
        Route::get('proposal/create', App\Livewire\CommunityService\Proposal\Create::class)
            ->middleware('role:dosen')
            ->name('proposal.create');

        Route::get('proposal/{proposal}', App\Livewire\CommunityService\Proposal\Show::class)->name('proposal.show');
        Route::get('proposal/{proposal}/edit', App\Livewire\CommunityService\Proposal\Edit::class)->name('proposal.edit');

        Route::get('proposal-revision', App\Livewire\CommunityService\ProposalRevision\Index::class)->name('proposal-revision.index');
        Route::get('proposal-revision/{proposal}', App\Livewire\CommunityService\ProposalRevision\Show::class)->name('proposal-revision.show');

        // Laporan Kemajuan dihilangkan berdasarkan arahan simplifikasi
        Route::get('progress-report', App\Livewire\CommunityService\ProgressReport\Index::class)->name('progress-report.index');
        Route::get('progress-report/{proposal}', Show::class)
            ->name('progress-report.show')
            ->defaults('type', 'community-service-progress');

        Route::get('final-report', App\Livewire\CommunityService\FinalReport\Index::class)->name('final-report.index');
        Route::get('final-report/{proposal}', App\Livewire\CommunityService\FinalReport\Show::class)
            ->name('final-report.show');

        Route::get('daily-note', App\Livewire\CommunityService\DailyNote\Index::class)->name('daily-note.index');
        Route::get('daily-note/{proposal}', App\Livewire\CommunityService\DailyNote\Show::class)->name('daily-note.show');
    });

    // IKU Dashboard & Verification (Standardized access for Rektor/Kepala LPPM)
    Route::get('/iku', IkuDashboard::class)
        ->middleware(['role:admin lppm|rektor|kepala lppm'])
        ->name('accreditation.hub');

    Route::get('/iku/verification', IkuVerification::class)
        ->middleware(['role:admin lppm|rektor|kepala lppm'])
        ->name('accreditation.verification');

    // Dekan Routes
    Route::middleware(['permission:module_persetujuan_dekan'])->prefix('dekan')->name('dekan.')->group(function () {
        Route::get('proposals', DekanProposalIndex::class)->name('proposals.index');
        Route::get('reports', ReportIndex::class)->name('reports.index');
        Route::get('riwayat-persetujuan', ApprovalHistory::class)->name('approval-history');
    });

    // Kaprodi Routes
    Route::middleware(['permission:module_persetujuan_kaprodi'])->prefix('kaprodi')->name('kaprodi.')->group(function () {
        Route::get('proposals', ProposalValidation::class)->name('proposals.index');
    });

    // Review Routes
    Route::middleware(['permission:module_review'])->prefix('review')->name('review.')->group(function () {
        Route::get('research', ReviewResearch::class)->name('research');
        Route::get('community-service', ReviewCommunityService::class)->name('community-service');
        Route::get('riwayat-review', ReviewHistory::class)->name('review-history');
        Route::get('monev', App\Livewire\Reviewer\Monev\Index::class)->name('monev');
    });

    // Letter Download
    Route::get('letters/{letter}/download', function (Letter $letter) {
        abort_unless(auth()->user()->can('download', $letter), 403);
        abort_unless($letter->file_path, 404, 'File PDF belum tersedia.');

        $filePath = Storage::disk('public')->path($letter->file_path);
        $fileName = 'surat-'.Str::slug($letter->id).'.pdf';

        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    })->name('letter.download');

    // Letter View (inline PDF in browser)
    Route::get('letters/{letter}/view', function (Letter $letter) {
        abort_unless(auth()->user()->can('download', $letter), 403);
        abort_unless($letter->file_path, 404, 'File PDF belum tersedia. Surat belum disetujui oleh Kepala LPPM.');

        $filePath = Storage::disk('public')->path($letter->file_path);
        $pdfContent = file_get_contents($filePath);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
        ]);
    })->name('letter.view');

    // Dosen - Persuratan Routes
    Route::middleware(['auth', 'letter.active'])->prefix('surat')->name('letters.')->group(function () {
        Route::get('/buat', LetterManualRequest::class)->name('manual-request');
        Route::get('/riwayat', fn () => redirect()->route('dashboard.dosen.surat.dashboard'))->name('history');
    });

    // Dosen - Dashboard Surat Routes
    Route::middleware(['auth', 'letter.active'])->prefix('dosen/persuratan')->name('dashboard.dosen.surat.')->group(function () {
        Route::get('/', LetterDashboard::class)->name('dashboard');
        Route::get('/buat', LetterManualRequest::class)->name('buat');
    });

    // Admin LPPM - Persuratan Routes
    Route::middleware(['auth', 'role:admin lppm', 'letter.active'])->prefix('admin-lppm/persuratan')->name('admin-lppm.letters.')->group(function () {
        Route::get('/', App\Livewire\AdminLppm\Letter\Dashboard::class)->name('dashboard');
        Route::get('/arsip', Archive::class)->name('archive');
    });

    Route::middleware(['auth', 'role:admin lppm', 'letter.active'])->prefix('admin-lppm/persuratan/jenis')->name('admin-lppm.letter-types.')->group(function () {
        Route::get('/', LetterTypeManagement::class)->name('index');
        Route::post('/{letterType}/upload-template', [LetterTypeController::class, 'uploadTemplate'])->name('upload-template');
        Route::get('/{letterType}/download-template', [LetterTypeController::class, 'downloadTemplate'])->name('download-template');
        Route::delete('/{letterType}/delete-template', [LetterTypeController::class, 'deleteTemplate'])->name('delete-template');
    });

    // Kepala LPPM Routes
    Route::middleware(['role:kepala lppm|rektor'])->prefix('kepala-lppm')->name('kepala-lppm.')->group(function () {
        Route::get('persetujuan-awal', InitialApproval::class)->name('initial-approval');
        Route::get('persetujuan-akhir', FinalDecision::class)->name('final-decision');
        Route::get('report-approval', ReportApproval::class)->name('report-approval');
        Route::get('persetujuan-surat', LetterApproval::class)->middleware('letter.active')->name('letter-approval');
        Route::get('monev/recap', MonevRecap::class)->name('monev.recap');
        Route::get('monev/dashboard', MonevDashboard::class)->name('rektor.monev-dashboard');
    });

    // Admin LPPM Routes
    Route::prefix('admin-lppm')->name('admin-lppm.')->group(function () {
        // Reviewer Management
        Route::middleware(['permission:module_reviewer_management'])->group(function () {
            Route::get('penugasan-reviewer', ReviewerAssignment::class)->name('assign-reviewers');
            Route::get('beban-kerja-reviewer', ReviewerWorkload::class)->name('reviewer-workload');
            Route::get('monitoring-review', ReviewMonitoring::class)->name('review-monitoring');
        });

        // Monev
        Route::get('monev', MonevIndex::class)
            ->middleware('permission:module_monev')
            ->name('monev.index');
        // route for global audit log access outside settings tab
        Route::get('audit-log', AuditLog::class)
            ->name('audit-log');

        // Manual Books - Per-role view (all authenticated users)
        Route::middleware(['auth', 'verified'])->prefix('manual-books')->name('manual-books.')->group(function () {
            Route::get('/', ManualBookUserIndex::class)->name('index');
        });

        // Manual Books CRUD (Admin LPPM)
        Route::middleware(['permission:module_manual_book'])->prefix('manual-books/admin')->name('manual-books.admin.')->group(function () {
            Route::get('/', ManualBookIndex::class)->name('index');
            Route::get('create', ManualBookForm::class)->name('create');
            Route::get('{manualBook}/edit', ManualBookForm::class)->name('edit');
            Route::post('{manualBook}/upload', [ManualBookUploadController::class, 'upload'])
                ->name('upload');
        });
    });

    // Manual Books - Per-role view (all authenticated users)
    Route::middleware(['auth', 'verified'])->prefix('manual-books')->name('manual-books.')->group(function () {
        Route::get('/', ManualBookUserIndex::class)->name('index');
    });

    Route::get('settings', SettingsIndex::class)
        ->middleware(['auth', 'verified'])
        ->name('settings');

    // Archive Management
    Route::get('admin/archives', ManageArchives::class)
        ->middleware(['permission:module_arsip_data'])
        ->name('admin.archives');

    Route::redirect('settings/profile', '/settings')->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)
        ->middleware(['permission:module_pengaturan'])
        ->name('settings.appearance');

    Route::middleware(['role:admin lppm|superadmin|dekan|kaprodi'])->group(function () {
        Route::get('settings/master-data', MasterData::class)->name('settings.master-data');
    });

    Route::middleware(['role:admin lppm|superadmin'])->group(function () {
        Route::get('settings/proposal-schedule', ProposalSchedule::class)->name('settings.proposal-schedule');
        Route::get('settings/proposal-template', ProposalTemplate::class)->name('settings.proposal-template');
        Route::get('settings/manual-books', ManualBookSetting::class)->name('settings.manual-books');
        Route::get('admin/eligibility-dashboard', EligibilityDashboard::class)->name('admin.eligibility-dashboard');

        // Template upload via traditional POST (bypass Livewire WAF block on /livewire/upload-file)
        Route::post(
            'settings/proposal-template/upload/{type}',
            [ProposalTemplateUploadController::class, 'upload']
        )->name('settings.proposal-template.upload');
    });

    // Backup Download Routes (Bypass Livewire WAF issues — safe approach: filename from cache, not URL)
    Route::middleware(['auth', 'verified'])->prefix('settings')->name('settings.')->group(function () {
        Route::get('download-backup-db', [BackupDownloadController::class, 'downloadDatabaseBackup'])
            ->name('download-backup-db');
        Route::get('download-db', [BackupDownloadController::class, 'downloadDatabase'])
            ->name('download-db');
        Route::get('download-storage', [BackupDownloadController::class, 'downloadStorage'])
            ->name('download-storage');
    });

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    // Notification Routes
    Route::get('notifications', NotificationCenter::class)
        ->middleware(['auth', 'verified'])
        ->name('notifications');

    // Role Switcher Route
    Route::post('role/switch', [RoleSwitcherController::class, 'switch'])
        ->name('role.switch');

    // PDF Export Route
    Route::get('proposals/{proposal}/export-pdf', [ProposalExportController::class, 'download'])
        ->name('proposals.export-pdf');

    Route::get('proposals/{proposal}/preview-pdf', [ProposalExportController::class, 'preview'])
        ->name('proposals.preview-pdf');

    Route::get('reports/{proposal}/export-pdf', [ProposalExportController::class, 'downloadReport'])
        ->name('reports.export-pdf');

    Route::get('reviewers/{proposalReviewer}/export-pdf', [ReviewExportController::class, 'download'])
        ->name('reviewers.export-pdf');

    Route::get('daily-notes/{proposal}/export-pdf', [DailyNoteExportController::class, 'download'])
        ->name('daily-notes.export-pdf');

    Route::get('media/{media:uuid}/download', [MediaDownloadController::class, 'download'])
        ->middleware(['auth'])
        ->name('media.download');

    Route::get('monev/{id}/ba-pdf', [ReportExportController::class, 'monevBaPdf'])
        ->name('export.monev.ba');
});

require __DIR__.'/auth.php';

Route::get('/verify/reports/{institutionalReport}', [ReportVerificationController::class, 'show'])
    ->middleware(['signed'])
    ->name('reports.verify');

Route::get('/verify/signatures/{documentSignature}', [DocumentSignatureVerificationController::class, 'show'])
    ->middleware(['signed'])
    ->name('signatures.verify');

Route::get('/verify/letters/{letter}', [LetterVerificationController::class, 'show'])
    ->middleware(['signed'])
    ->name('letters.verify');

// Rute Ekspor Laporan (Dekan & Role dengan module_laporan)
Route::group(['middleware' => ['auth', 'verified', 'permission:module_laporan']], function () {
    Route::get('/laporan-penelitian/export/pdf', [ReportExportController::class, 'researchPdf'])->name('reports.research.pdf');
    Route::get('/laporan-penelitian/export/excel', [ReportExportController::class, 'researchExcel'])->name('reports.research.excel');

    Route::get('/laporan-pkm/export/pdf', [ReportExportController::class, 'pkmPdf'])->name('reports.pkm.pdf');
    Route::get('/laporan-pkm/export/excel', [ReportExportController::class, 'pkmExcel'])->name('reports.pkm.excel');

    Route::get('/laporan-luaran/export/pdf', [ReportExportController::class, 'outputPdf'])->name('reports.output.pdf');
    Route::get('/laporan-luaran/export/excel', [ReportExportController::class, 'outputExcel'])->name('reports.output.excel');

    Route::get('/laporan-mitra/export/pdf', [ReportExportController::class, 'partnerPdf'])->name('reports.partner.pdf');
    Route::get('/laporan-mitra/export/excel', [ReportExportController::class, 'partnerExcel'])->name('reports.partner.excel');
});

// Rute Ekspor Admin (dashboard, monev, IKU, reviewer)
Route::group(['middleware' => ['auth', 'verified', 'role:admin lppm|rektor|kepala lppm|superadmin']], function () {
    Route::get('/admin/dashboard/export-research', [ReportExportController::class, 'dashboardResearchExport'])->name('admin.dashboard.export-research');
    Route::get('/admin/dashboard/export-community-service', [ReportExportController::class, 'dashboardCommunityServiceExport'])->name('admin.dashboard.export-community-service');

    Route::get('/monev/export-recap', [ReportExportController::class, 'monevRecapExcel'])->name('export.monev.recap');
    Route::get('/laporan-monev/export/pdf', [ReportExportController::class, 'monevPdf'])->name('reports.monev.pdf');

    // IKU Exports
    Route::get('/admin/iku/export-pdf', [ReportExportController::class, 'ikuPdf'])->name('admin.iku.export-pdf');
    Route::get('/admin/iku/export-excel', [ReportExportController::class, 'ikuExcel'])->name('admin.iku.export-excel');

    // Reviewer Report Exports
    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    Route::get('/admin/reviewer/export-pdf', [ReportExportController::class, 'reviewerPdf'])->name('reports.reviewer.pdf');
    Route::get('/admin/reviewer/export-excel', [ReportExportController::class, 'reviewerExcel'])->name('reports.reviewer.excel');
});

// Archive Export Routes - Should match archive module permission
Route::group(['middleware' => ['auth', 'verified', 'permission:module_arsip_data']], function () {
    Route::get('/admin/archives/export', [ReportExportController::class, 'archiveExport'])->name('admin.archives.export');
    Route::get('/admin/archives/template', [ReportExportController::class, 'archiveTemplate'])->name('admin.archives.template');
});

// IKU Dashboard removed from here - moved to shared role-based group above
