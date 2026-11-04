<?php

namespace App\Livewire\Iku;

use App\Enums\PolicyInvolvementStatus;
use App\Livewire\Concerns\HasToast;
use App\Models\AdditionalOutput;
use App\Models\MandatoryOutput;
use App\Models\PolicyInvolvement;
use App\Models\StudyProgram;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class IkuVerification extends Component
{
    use HasToast;
    use WithPagination;

    public string $search = '';

    public string $type = 'all'; // all, publication, hki, product

    public string $status = 'all'; // all, verified, unverified

    protected $queryString = [
        'search' => ['except' => ''],
        'type' => ['except' => 'all'],
        'status' => ['except' => 'all'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function verify(string $id, string $modelType)
    {
        $model = match ($modelType) {
            'mandatory' => MandatoryOutput::find($id),
            'additional' => AdditionalOutput::find($id),
            'policy' => PolicyInvolvement::find($id),
            default => null
        };

        if ($model) {
            $update = [
                'verified_at' => now(),
                'verified_by' => auth()->id(),
            ];

            if ($modelType === 'policy') {
                $update['status'] = 'verified';
            } else {
                $update['is_verified'] = true;
            }

            $model->update($update);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Data berhasil diverifikasi.',
            ]);
        }
    }

    public function unverify(string $id, string $modelType)
    {
        $model = match ($modelType) {
            'mandatory' => MandatoryOutput::find($id),
            'additional' => AdditionalOutput::find($id),
            'policy' => PolicyInvolvement::find($id),
            default => null
        };

        if ($model) {
            $update = [
                'verified_at' => null,
                'verified_by' => null,
            ];

            if ($modelType === 'policy') {
                $update['status'] = PolicyInvolvementStatus::PENDING->value;
            } else {
                $update['is_verified'] = false;
            }

            $model->update($update);

            $this->dispatch('notify', [
                'type' => 'info',
                'message' => 'Verifikasi dibatalkan.',
            ]);
        }
    }

    public function updateRank(string $id, string $modelType, string $rank)
    {
        if ($modelType === 'policy') {
            return;
        }

        $model = $modelType === 'mandatory' ? MandatoryOutput::find($id) : AdditionalOutput::find($id);

        if ($model) {
            $model->update(['rank' => $rank]);
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Peringkat diperbarui.',
            ]);
        }
    }

    /**
     * Update status output oleh dosen (status_type & author_status).
     */
    public function updateStatus(string $id, string $modelType, string $statusType, string $authorStatus): void
    {
        $user = Auth::user();

        if (! $user->activeHasRole('dosen')) {
            $this->toastError('Anda tidak memiliki kewenangan untuk mengubah status luaran.');

            return;
        }

        $model = match ($modelType) {
            'mandatory' => MandatoryOutput::find($id),
            'additional' => AdditionalOutput::find($id),
            default => null,
        };

        if (! $model) {
            $this->toastError('Data tidak ditemukan.');

            return;
        }

        // Dosen hanya bisa update output miliknya sendiri
        $proposal = $model->progressReport?->proposal;
        if ($proposal?->submitter_id !== $user->id && ! $proposal?->teamMembers()->where('user_id', $user->id)->exists()) {
            $this->toastError('Anda tidak memiliki akses untuk mengubah luaran ini.');

            return;
        }

        // Blokir update jika sudah verified
        if ($model->is_verified) {
            $this->toastError('Output sudah diverifikasi, tidak bisa diubah.');

            return;
        }

        $update = [];
        if ($statusType) {
            $update['status_type'] = $statusType;
        }
        if ($authorStatus) {
            $update['author_status'] = $authorStatus;
        }

        if (! empty($update)) {
            $model->update($update);
        }

        $this->toastSuccess('Status luaran berhasil diperbarui.');
    }

    public function render()
    {
        $user = Auth::user();

        $mandatoryQuery = MandatoryOutput::with(['progressReport.proposal.submitter', 'proposalOutput'])
            ->when($this->status === 'unverified', fn ($q) => $q->where('is_verified', false))
            ->when($this->status === 'verified', fn ($q) => $q->where('is_verified', true))
            ->when($this->search, function ($q) {
                $q->where('journal_title', 'like', "%{$this->search}%")
                    ->orWhere('article_title', 'like', "%{$this->search}%")
                    ->orWhereHas('progressReport.proposal', fn ($pq) => $pq->where('title', 'like', "%{$this->search}%"));
            });

        $additionalQuery = AdditionalOutput::with(['progressReport.proposal.submitter', 'proposalOutput'])
            ->when($this->status === 'unverified', fn ($q) => $q->where('is_verified', false))
            ->when($this->status === 'verified', fn ($q) => $q->where('is_verified', true))
            ->when($this->search, function ($q) {
                $q->where('journal_title', 'like', "%{$this->search}%")
                    ->orWhere('book_title', 'like', "%{$this->search}%")
                    ->orWhereHas('progressReport.proposal', fn ($pq) => $pq->where('title', 'like', "%{$this->search}%"));
            });

        // Role-based filtering
        if ($user->activeHasAnyRole(['admin lppm', 'kepala lppm', 'rektor', 'superadmin'])) {
            // global access
        } elseif ($user->activeHasRole('dekan')) {
            if ($user->identity?->faculty_id) {
                $mandatoryQuery->whereHas('progressReport.proposal.submitter.identity', fn ($si) => $si->where('faculty_id', $user->identity->faculty_id));
                $additionalQuery->whereHas('progressReport.proposal.submitter.identity', fn ($si) => $si->where('faculty_id', $user->identity->faculty_id));
            } else {
                $mandatoryQuery->whereRaw('1=0');
                $additionalQuery->whereRaw('1=0');
            }
        } elseif ($user->activeHasRole('kaprodi')) {
            $kaprodiProdiId = StudyProgram::where('kaprodi_user_id', $user->id)->value('id') ?? $user->identity?->study_program_id;
            if ($kaprodiProdiId) {
                $mandatoryQuery->whereHas('progressReport.proposal.submitter.identity', fn ($si) => $si->where('study_program_id', $kaprodiProdiId));
                $additionalQuery->whereHas('progressReport.proposal.submitter.identity', fn ($si) => $si->where('study_program_id', $kaprodiProdiId));
            } else {
                $mandatoryQuery->whereRaw('1=0');
                $additionalQuery->whereRaw('1=0');
            }
        } elseif ($user->activeHasRole('dosen')) {
            $mandatoryQuery->whereHas('progressReport.proposal', function ($q) use ($user) {
                $q->where('submitter_id', $user->id)
                    ->orWhereHas('teamMembers', fn ($t) => $t->where('user_id', $user->id));
            });
            $additionalQuery->whereHas('progressReport.proposal', function ($q) use ($user) {
                $q->where('submitter_id', $user->id)
                    ->orWhereHas('teamMembers', fn ($t) => $t->where('user_id', $user->id));
            });
        } else {
            $mandatoryQuery->whereRaw('1=0');
            $additionalQuery->whereRaw('1=0');
        }

        $mandatory = $mandatoryQuery->get()
            ->map(function (MandatoryOutput $item) {
                $item->model_type = 'mandatory';
                $item->is_verified_status = $item->is_verified;
                $item->display_title = $item->journal_title ?? $item->article_title;
                $item->submitter_name = $item->progressReport?->proposal?->submitter->name ?? 'Unknown';
                $item->document_url = $item->document_file ? asset('storage/'.$item->document_file) : null;

                return $item;
            });

        $additional = $additionalQuery->get()
            ->map(function (AdditionalOutput $item) {
                $item->model_type = 'additional';
                $item->is_verified_status = $item->is_verified;
                $item->display_title = $item->journal_title ?? $item->book_title ?? $item->product_name;
                $item->submitter_name = $item->progressReport?->proposal?->submitter->name ?? 'Unknown';
                $item->document_url = $item->document_file ? asset('storage/'.$item->document_file) : null;

                return $item;
            });

        $policies = PolicyInvolvement::with('user.identity')
            ->when($this->status === 'unverified', fn ($q) => $q->where('status', PolicyInvolvementStatus::PENDING->value))
            ->when($this->status === 'verified', fn ($q) => $q->where('status', 'verified'))
            ->when($this->search, function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$this->search}%"));
            })
            ->get()
            ->map(function (PolicyInvolvement $item) {
                $item->model_type = 'policy';
                $item->is_verified_status = $item->status === 'verified';
                $item->display_title = $item->title;
                $item->submitter_name = $item->user->name ?? 'Unknown';
                $item->document_url = $item->getFirstMediaUrl('supporting_document');

                return $item;
            });

        $combined = $mandatory->concat($additional)->concat($policies)->sortByDesc('created_at');

        // Apply type filter manually on collection
        if ($this->type !== 'all') {
            $combined = $combined->filter(function ($item) {
                if ($item->model_type === 'policy') {
                    return $this->type === 'pakar';
                }

                $cat = $item->proposalOutput->category ?? '';
                if ($this->type === 'publication') {
                    return in_array($cat, ['journal', 'conference']);
                }
                if ($this->type === 'hki') {
                    return $cat === 'hki';
                }
                if ($this->type === 'product') {
                    return $cat === 'product';
                }

                return true;
            });
        }

        $perPage = 10;
        $page = $this->getPage();
        $items = $combined->slice(($page - 1) * $perPage, $perPage)->all();

        $paginatedItems = new LengthAwarePaginator($items, $combined->count(), $perPage, $page, [
            'path' => route('accreditation.verification'),
        ]);

        return view('livewire.iku.iku-verification', [
            'outputs' => $paginatedItems,
        ])->layout('components.layouts.app', ['title' => 'Verifikasi IKU']);
    }
}
