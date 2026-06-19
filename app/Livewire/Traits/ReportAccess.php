<?php

declare(strict_types=1);

namespace App\Livewire\Traits;

use App\Enums\ProposalUserStatus;
use App\Models\ProgressReport;
use App\Models\Proposal;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

/**
 * Trait ReportAccess
 *
 * Handles report access control and loading logic
 */
trait ReportAccess
{
    public Proposal $proposal;

    public ?ProgressReport $progressReport = null;

    public bool $canEdit = false;

    #[On('report-saved')]
    public function onReportSaved(): void
    {
        $this->dispatch('$refresh');
    }

    /**
     * Check if current user has access to view and edit the report
     */
    protected function checkAccess(): void
    {
        if (! $this->canView()) {
            $user = Auth::user();
            $message = 'Anda tidak memiliki akses untuk melihat laporan ini.';

            if ($user?->hasRole('dekan')) {
                if (! $user->identity) {
                    $message = 'Profil Anda belum lengkap (Identity tidak ditemukan). Silakan lengkapi profil Anda terlebih dahulu.';
                } else {
                    $message = 'Maaf, Anda tidak memiliki akses ke fakultas dosen yang bersangkutan.';
                }
            }

            abort(403, $message);
        }

        $this->canEdit = $this->canEditReport($this->proposal);
    }

    /**
     * Check if current user can view the report
     * User can view if they are submitter or accepted team member
     */
    protected function canView(): bool
    {
        $user = Auth::user();
        // dd($user->getRoleNames());

        if ($user->hasAnyRole(['admin lppm', 'kepala lppm', 'rektor'])) {
            return true;
        }

        if ($user->hasRole('dekan')) {
            $dekanFacultyId = $user->identity?->faculty_id;
            $submitterFacultyId = $this->proposal->submitter->identity?->faculty_id;

            return $dekanFacultyId && $dekanFacultyId === $submitterFacultyId;
        }

        return $this->proposal->submitter_id === $user->id
            || $this->proposal->teamMembers()
                ->where('user_id', $user->id)
                ->where('status', ProposalUserStatus::ACCEPTED->value)
                ->exists();
    }

    /**
     * Load latest progress report for the proposal, filtered by type
     */
    protected function loadReport(string $type = 'progress'): void
    {
        $query = $this->proposal->progressReports();

        if (str_contains($type, 'final')) {
            /** @var ProgressReport|null $report */
            $report = $query->where('reporting_period', 'final')->latest()->first();
            $this->progressReport = $report;
        } else {
            // For progress, we take anything that isn't 'final' (semester_1, semester_2, annual)
            /** @var ProgressReport|null $report */
            $report = $query->where('reporting_period', '!=', 'final')->latest()->first();
            $this->progressReport = $report;
        }
    }
}
