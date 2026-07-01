<?php

namespace App\Livewire\Research\Proposal;

use App\Enums\ProposalStatus;
use App\Livewire\Concerns\HasToast;
use App\Models\Proposal;
use App\Models\ProposalReviewer;
use App\Models\ProposalStatusLog;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * @property-read Proposal|null $proposal
 * @property-read bool $canDecide
 * @property-read Collection|ProposalReviewer[] $pendingReviewers
 * @property-read Collection $reviewSummary
 */
class KepalaLppmFinalDecision extends Component
{
    use HasToast;

    public string $proposalId = '';

    public string $decision = '';

    public string $notes = '';

    public function mount(string $proposalId): void
    {
        $this->proposalId = $proposalId;
    }

    #[Computed]
    public function proposal(): ?Proposal
    {
        return Proposal::with(['reviewers.user', 'teamMembers'])->find($this->proposalId);
    }

    #[Computed]
    public function canDecide(): bool
    {
        $user = Auth::user();
        $isKepalaLppm = $user->hasRole(['kepala lppm']);
        $proposal = $this->proposal;
        $status = $proposal->status;

        if ($status === ProposalStatus::REVISION_SUBMITTED) {
            return $isKepalaLppm;
        }

        return $isKepalaLppm && $status === ProposalStatus::REVIEWED && $proposal->allReviewsCompleted();
    }

    /**
     * True if proposal is in initial reviewed stage (before any revision round).
     * At this stage, Kepala LPPM can only send back to revision or reject.
     */
    #[Computed]
    public function isInitialReviewedStage(): bool
    {
        return $this->proposal?->status === ProposalStatus::REVIEWED;
    }

    /**
     * @return Collection<int, ProposalReviewer>
     */
    #[Computed]
    public function pendingReviewers(): Collection
    {
        return $this->proposal->pendingReviewers()->get();
    }

    /**
     * @return Collection<(int|string), int<0, max>>
     */
    #[Computed]
    public function reviewSummary(): Collection
    {
        return $this->proposal->reviewers()
            ->select('recommendation')
            ->get()
            ->groupBy('recommendation')
            ->map->count();
    }

    public function openDecisionModal(string $decision): void
    {
        $this->decision = $decision;
        $this->notes = '';
        $this->dispatch('open-final-decision-modal');
    }

    public function cancelDecision(): void
    {
        $this->decision = '';
        $this->notes = '';
    }

    #[On('confirm-final-decision')]
    public function processDecision(): void
    {
        $user = Auth::user();
        $isKepalaLppm = $user->hasRole(['kepala lppm']);

        if (! $isKepalaLppm) {
            $message = 'Anda tidak memiliki akses untuk membuat keputusan';
            session()->flash('error', $message);
            $this->toastError($message);

            return;
        }

        $proposal = $this->proposal;
        $allowedStatuses = [ProposalStatus::REVIEWED, ProposalStatus::REVISION_SUBMITTED];

        if (! in_array($proposal->status, $allowedStatuses)) {
            $message = 'Proposal tidak dalam status yang dapat diputuskan';
            session()->flash('error', $message);
            $this->toastError($message);

            return;
        }

        if ($proposal->status === ProposalStatus::REVIEWED && ! $proposal->allReviewsCompleted()) {
            $message = 'Semua reviewer harus menyelesaikan review terlebih dahulu';
            session()->flash('error', $message);
            $this->toastError($message);

            return;
        }

        // Saat status REVIEWED: Kepala LPPM WAJIB mengembalikan ke perbaikan terlebih dahulu.
        // Keputusan 'completed' hanya boleh setelah dosen mengajukan ulang (REVISION_SUBMITTED).
        $validDecisions = $proposal->status === ProposalStatus::REVIEWED
            ? ['revision_needed', 'rejected']
            : ['completed', 'revision_needed', 'rejected'];

        if (! in_array($this->decision, $validDecisions)) {
            $message = $proposal->status === ProposalStatus::REVIEWED
                ? 'Proposal harus dikembalikan ke dosen untuk perbaikan terlebih dahulu sebelum dapat disetujui.'
                : 'Keputusan tidak valid';
            session()->flash('error', $message);
            $this->toastError($message);

            return;
        }

        // Catatan wajib diisi saat mengembalikan ke perbaikan
        if ($this->decision === 'revision_needed' && empty(trim($this->notes))) {
            $message = 'Catatan perbaikan wajib diisi agar dosen mengetahui apa yang harus diperbaiki.';
            session()->flash('error', $message);
            $this->toastError($message);

            return;
        }

        try {
            $newStatus = match ($this->decision) {
                'completed' => ProposalStatus::COMPLETED,
                'rejected' => ProposalStatus::REJECTED,
                'revision_needed' => ProposalStatus::REVISION_NEEDED,
            };

            // Validate transition
            if (! $proposal->status->canTransitionTo($newStatus)) {
                $message = 'Transisi status tidak diizinkan';
                session()->flash('error', $message);
                $this->toastError($message);

                return;
            }

            // Store decision notes in status log (before update)
            $oldStatus = $proposal->status;

            $proposal->update([
                'status' => $newStatus,
            ]);

            ProposalStatusLog::create([
                'proposal_id' => $proposal->id,
                'user_id' => $user->id,
                'status_before' => $oldStatus,
                'status_after' => $newStatus,
                'notes' => $this->notes,
                'at' => now(),
            ]);

            Log::info('Kepala LPPM final decision', [
                'proposal_id' => $proposal->id,
                'user_id' => $user->id,
                'decision' => $this->decision,
                'new_status' => $newStatus->value,
                'notes' => $this->notes,
            ]);

            // Send notifications
            $this->sendNotifications($proposal, $this->decision, $user);

            $message = match ($this->decision) {
                'completed' => 'Proposal berhasil disetujui dan selesai.',
                'rejected' => 'Proposal telah ditolak.',
                'revision_needed' => 'Proposal memerlukan perbaikan dan dikembalikan ke pengusul.',
                default => 'Keputusan berhasil diproses.',
            };

            session()->flash('success', $message);
            $this->toastSuccess($message);
            $this->dispatch('close-final-decision-modal');
            $this->dispatch('proposal-final-decided', proposalId: $proposal->id, decision: $this->decision);
            $this->cancelDecision();
        } catch (\Exception $e) {
            Log::error('Kepala LPPM final decision failed', [
                'proposal_id' => $proposal->id,
                'error' => $e->getMessage(),
            ]);

            $message = 'Terjadi kesalahan saat membuat keputusan: '.$e->getMessage();
            session()->flash('error', $message);
            $this->toastError($message);
        }
    }

    /**
     * Send notifications to stakeholders
     */
    protected function sendNotifications(Proposal $proposal, string $decision, User $kepalaLppm): void
    {
        $notificationService = app(NotificationService::class);

        $faculty = $proposal->submitter->identity?->faculty;
        $dekan = $faculty->deanUser ?? User::role('dekan')->whereHas('identity', function ($query) use ($faculty) {
            $query->where('faculty_id', $faculty?->id);
        })->first();

        // Get recipients
        $recipients = collect()
            ->push($proposal->submitter) // Submitter
            ->push($dekan) // Relevant Dekan
            ->push(User::role('admin lppm')->first()) // Admin LPPM
            ->merge($proposal->teamMembers) // Team Members
            ->filter()
            ->unique('id')
            ->values();

        $notificationService->notifyFinalDecision(
            $proposal,
            $decision,
            $kepalaLppm,
            $recipients
        );
    }

    public function render(): View
    {
        return view('livewire.research.proposal.kepala-lppm-final-decision');
    }
}
