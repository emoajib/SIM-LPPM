<?php

namespace App\Livewire\Research\DailyNote;

use App\Livewire\Concerns\HasToast;
use App\Livewire\Traits\HasReportTemplates;
use App\Models\BudgetGroup;
use App\Models\DailyNote;
use App\Models\ProgressReport;
use App\Models\Proposal;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Show extends Component
{
    use HasReportTemplates;
    use HasToast;
    use WithFileUploads;

    public Proposal $proposal;

    #[Validate('required|date|before_or_equal:today')]
    public string $activity_date = '';

    #[Validate('required|string|min:10')]
    public string $activity_description = '';

    #[Validate('required|integer|min:0|max:100')]
    public int $progress_percentage = 0;

    #[Validate('nullable|string')]
    public string $notes = '';

    #[Validate('nullable|exists:budget_groups,id')]
    public ?int $budget_group_id = null;

    #[Validate('nullable|numeric|min:0')]
    public $amount = 0;

    #[Validate(['evidence.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120'])] // 5MB max per file
    public $evidence = [];

    public ?string $editingId = null;

    public $logbookApprovalFile;

    public function mount(Proposal $proposal): void
    {
        if (! $this->canAccess($proposal)) {
            abort(403);
        }

        // Eager load relationships to prevent N+1 queries
        $proposal->load(['dailyNotes', 'progressReports', 'teamMembers', 'reviewers.user']);
        $this->proposal = $proposal;
        $this->activity_date = date('Y-m-d');
    }

    protected function canAccess(Proposal $proposal): bool
    {
        $user = Auth::user();

        if ($user->hasAnyRole(['admin lppm', 'kepala lppm', 'rektor', 'superadmin', 'dekan'])) {
            return true;
        }

        return $proposal->submitter_id === $user->id ||
            $proposal->teamMembers()->where('user_id', $user->id)->exists();
    }

    public function canManage(Proposal $proposal): bool
    {
        $userId = Auth::id();

        return $proposal->submitter_id === $userId ||
            $proposal->teamMembers()->where('user_id', $userId)->exists();
    }

    public function create(): void
    {
        if (! $this->canManage($this->proposal)) {
            abort(403);
        }

        $this->reset(['activity_description', 'progress_percentage', 'notes', 'evidence', 'editingId', 'budget_group_id', 'amount']);
        $this->activity_date = date('Y-m-d');
        $this->dispatch('open-modal', modalId: 'daily-note-modal');
    }

    public function save(): void
    {
        if (! $this->canManage($this->proposal)) {
            abort(403);
        }

        $this->validate();

        // Check budget constraint if group and amount is selected
        $amount = (float) $this->amount;
        if ($this->budget_group_id && $amount > 0) {
            $allocatedBudget = (float) $this->proposal->budgetItems()
                ->where('budget_group_id', $this->budget_group_id)
                ->sum('total_price');

            $usedBudgetQuery = $this->proposal->dailyNotes()
                ->where('budget_group_id', $this->budget_group_id);

            // Exclude current note if editing
            if ($this->editingId) {
                $usedBudgetQuery->where('id', '!=', $this->editingId);
            }

            $usedBudget = (float) $usedBudgetQuery->sum('amount');
            $remainingConstraint = $allocatedBudget - $usedBudget;

            if ($amount > $remainingConstraint) {
                $this->addError('amount', 'Nominal pengeluaran (Rp '.number_format($amount, 0, ',', '.').') melebihi sisa anggaran (Rp '.number_format($remainingConstraint, 0, ',', '.').') untuk kategori ini.');

                return;
            }
        }

        $data = [
            'proposal_id' => $this->proposal->id,
            'activity_date' => $this->activity_date,
            'activity_description' => $this->activity_description,
            'progress_percentage' => $this->progress_percentage,
            'notes' => $this->notes,
            'budget_group_id' => $this->budget_group_id,
            'amount' => $amount,
        ];

        if ($this->editingId) {
            $note = DailyNote::findOrFail($this->editingId);
            $note->update($data);
        } else {
            $note = DailyNote::create($data);
        }

        if ($this->evidence) {
            foreach ($this->evidence as $file) {
                $note->addMedia($file->getRealPath())
                    ->usingName($file->getClientOriginalName())
                    ->usingFileName($file->getClientOriginalName())
                    ->toMediaCollection('evidence');
            }
        }

        // Clear only if everything is successful
        $this->reset(['activity_description', 'progress_percentage', 'notes', 'evidence', 'editingId', 'budget_group_id', 'amount']);
        $this->activity_date = date('Y-m-d');
        $this->dispatch('note-saved');
        $this->dispatch('close-modal', modalId: 'daily-note-modal');
        $message = 'Catatan harian berhasil disimpan.';
        session()->flash('success', $message);
        $this->toastSuccess($message);
    }

    public function edit(string $id): void
    {
        if (! $this->canManage($this->proposal)) {
            abort(403);
        }

        $note = DailyNote::findOrFail($id);

        // Authorization check (optional but recommended)
        if ($note->proposal_id !== $this->proposal->id) {
            abort(403);
        }

        $this->editingId = $id;
        $this->activity_date = $note->activity_date->format('Y-m-d');
        $this->activity_description = $note->activity_description;
        $this->progress_percentage = $note->progress_percentage;
        $this->notes = $note->notes ?? '';
        $this->budget_group_id = $note->budget_group_id !== null ? (int) $note->budget_group_id : null;
        $this->amount = $note->amount;

        $this->dispatch('open-modal', modalId: 'daily-note-modal');
    }

    public function delete(string $id): void
    {
        if (! $this->canManage($this->proposal)) {
            abort(403);
        }

        $note = DailyNote::findOrFail($id);
        if ($note->proposal_id !== $this->proposal->id) {
            abort(403);
        }
        $note->delete();
        $message = 'Catatan harian berhasil dihapus.';
        session()->flash('success', $message);
        $this->toastSuccess($message);
    }

    public function deleteEvidence(string $mediaId): void
    {
        if (! $this->canManage($this->proposal)) {
            abort(403);
        }

        $media = Media::findOrFail($mediaId);

        // Check if the media belongs to a note in this proposal
        $note = DailyNote::find($media->model_id);

        if ($note && $note->proposal_id === $this->proposal->id) {
            $media->delete();
            $message = 'File bukti berhasil dihapus.';
            session()->flash('success', $message);
            $this->toastSuccess($message);
        } else {
            abort(403);
        }
    }

    public function removeEvidence(int $index): void
    {
        if (isset($this->evidence[$index])) {
            unset($this->evidence[$index]);
            $this->evidence = array_values($this->evidence);
        }
    }

    public function cancelEdit(): void
    {
        $this->reset(['activity_description', 'progress_percentage', 'notes', 'evidence', 'editingId', 'budget_group_id', 'amount']);
        $this->activity_date = date('Y-m-d');
        $this->dispatch('close-modal', modalId: 'daily-note-modal');
    }

    public function render()
    {
        return view('livewire.research.daily-note.show', [
            'notes_list' => $this->proposal->dailyNotes()->with(['media.model', 'budgetGroup'])->latest('activity_date')->get(),
            'budget_groups' => BudgetGroup::whereIn('id', $this->proposal->budgetItems()->pluck('budget_group_id'))->get(),
            'budget_summaries' => $this->proposal->budgetItems()
                ->selectRaw('budget_group_id, sum(total_price) as total_budget')
                ->groupBy('budget_group_id')
                ->get()
                ->keyBy('budget_group_id'),
            'total_proposed_budget' => $this->proposal->budgetItems()->sum('total_price'),
        ]);
    }

    #[On('sign-logbook')]
    public function signLogbook()
    {
        if (! $this->canManage($this->proposal)) {
            abort(403);
        }

        $this->proposal->update(['logbook_signed_at' => now()]);

        // Invalidate cached reports so the signature appears on newly downloaded final reports
        $reports = $this->proposal->progressReports()->get();
        /** @var ProgressReport $report */
        foreach ($reports as $report) {
            $files = glob(storage_path('app/pdf_cache/reports/report_'.$report->id.'_*.pdf'));
            if (is_array($files)) {
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
        }

        $message = 'Catatan harian dan laporan keuangan berhasil ditandatangani.';
        session()->flash('success', $message);
        $this->toastSuccess($message);

        // Invalidate cached financial PDF so signature updates
        $financialFiles = glob(storage_path('app/pdf_cache/financial/financial_'.$this->proposal->id.'*.pdf'));
        if (is_array($financialFiles)) {
            foreach ($financialFiles as $file) {
                @unlink($file);
            }
        }

        return redirect()->route('financial-reports.export-pdf', ['proposal' => $this->proposal, 'download' => 'true']);
    }

    public function canApprove(Proposal $proposal): bool
    {
        return Auth::user()->hasRole('kepala lppm');
    }

    #[On('approve-logbook')]
    public function approveLogbook(): void
    {
        if (! $this->canApprove($this->proposal)) {
            abort(403);
        }

        $this->proposal->update(['logbook_approved_at' => now()]);
        $this->clearFinancialPdfCache();

        $message = 'Catatan harian berhasil divalidasi oleh Kepala LPPM.';
        session()->flash('success', $message);
        $this->toastSuccess($message);
    }

    public function saveLogbookApprovalFile(): void
    {
        if (! $this->canManage($this->proposal)) {
            abort(403);
        }

        $this->validate([
            'logbookApprovalFile' => 'required|file|mimes:pdf|max:10240',
        ], [
            'logbookApprovalFile.required' => 'Berkas scan lembar pengesahan wajib dipilih.',
            'logbookApprovalFile.mimes' => 'Format berkas harus PDF.',
            'logbookApprovalFile.max' => 'Ukuran berkas maksimal 10MB.',
        ]);

        $this->proposal->clearMediaCollection('logbook_approval_file');
        /** @var TemporaryUploadedFile $file */
        $file = $this->logbookApprovalFile;
        $this->proposal->addMedia($file->getRealPath())
            ->usingFileName($file->getClientOriginalName())
            ->toMediaCollection('logbook_approval_file');

        $this->reset('logbookApprovalFile');
        $this->clearFinancialPdfCache();

        $message = 'Berkas scan lembar pengesahan basah berhasil diunggah.';
        session()->flash('success', $message);
        $this->toastSuccess($message);
    }

    public function removeLogbookApprovalFile(): void
    {
        if (! $this->canManage($this->proposal)) {
            abort(403);
        }

        $this->proposal->clearMediaCollection('logbook_approval_file');
        $this->clearFinancialPdfCache();

        $message = 'Berkas scan lembar pengesahan basah berhasil dihapus.';
        session()->flash('info', $message);
        $this->toastSuccess($message);
    }

    protected function clearFinancialPdfCache(): void
    {
        $financialFiles = glob(storage_path('app/pdf_cache/financial/financial_'.$this->proposal->id.'*.pdf'));
        if (is_array($financialFiles)) {
            foreach ($financialFiles as $file) {
                @unlink($file);
            }
        }
    }
}
