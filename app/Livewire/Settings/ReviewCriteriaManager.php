<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\HasToast;
use App\Models\ReviewCriteria;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ReviewCriteriaManager extends Component
{
    use HasToast;

    public array $editing = [];

    public array $creating = [];

    public function mount(): void
    {
        if (! Auth::user()->activeHasRole('admin lppm')) {
            abort(403);
        }

        $this->cleanupDuplicates();
    }

    private function cleanupDuplicates(): void
    {
        $duplicates = ReviewCriteria::select('type', 'criteria')
            ->groupBy('type', 'criteria')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isEmpty()) {
            return;
        }

        foreach ($duplicates as $duplicate) {
            $ids = ReviewCriteria::where('type', $duplicate->type)
                ->where('criteria', $duplicate->criteria)
                ->orderBy('id')
                ->pluck('id');

            if ($ids->count() > 1) {
                $idsToRemove = $ids->slice(1);
                ReviewCriteria::whereIn('id', $idsToRemove)->delete();
            }
        }
    }

    #[Computed]
    public function researchCriterias()
    {
        return ReviewCriteria::where('type', 'research')
            ->orderBy('order')
            ->get();
    }

    #[Computed]
    public function pkmCriterias()
    {
        return ReviewCriteria::where('type', 'community_service')
            ->orderBy('order')
            ->get();
    }

    #[Computed]
    public function monevResearchCriterias()
    {
        return ReviewCriteria::where('type', 'monev_research')
            ->orderBy('order')
            ->get();
    }

    #[Computed]
    public function monevPkmCriterias()
    {
        return ReviewCriteria::where('type', 'monev_community_service')
            ->orderBy('order')
            ->get();
    }

    public function toggleActive(int $id): void
    {
        $criteria = ReviewCriteria::findOrFail($id);
        $criteria->update(['is_active' => ! $criteria->is_active]);
        $this->toastSuccess('Status kriteria berhasil diperbarui.');
    }

    public function edit(int $id): void
    {
        $criteria = ReviewCriteria::findOrFail($id);
        $this->editing = [
            'id' => $id,
            'criteria' => $criteria->criteria,
            'description' => $criteria->description,
            'weight' => $criteria->weight,
        ];
    }

    public function cancelEdit(): void
    {
        $this->editing = [];
    }

    public function delete(int $id): void
    {
        $user = Auth::user();
        if (! $user || ! $user->hasAnyRole(['superadmin', 'admin lppm'])) {
            abort(403, 'Unauthorized action.');
        }

        $criteria = ReviewCriteria::findOrFail($id);
        $criteria->delete();
        $this->toastSuccess('Kriteria berhasil dihapus.');
    }

    public function save(): void
    {
        $this->validate([
            'editing.criteria' => 'required|string|max:255',
            'editing.description' => 'required|string',
            'editing.weight' => 'required|numeric|min:0|max:100',
        ], [], [
            'editing.criteria' => 'Nama Kriteria',
            'editing.description' => 'Deskripsi/Acuan',
            'editing.weight' => 'Bobot',
        ]);

        $criteria = ReviewCriteria::findOrFail($this->editing['id']);
        $criteria->update([
            'criteria' => $this->editing['criteria'],
            'description' => $this->editing['description'],
            'weight' => $this->editing['weight'],
        ]);

        $this->editing = [];
        $this->toastSuccess('Kriteria berhasil diperbarui.');
    }

    public function openCreate(string $type = 'research'): void
    {
        $this->creating = [
            'type' => $type,
            'criteria' => '',
            'description' => '',
            'weight' => 0,
        ];
    }

    public function cancelCreate(): void
    {
        $this->creating = [];
    }

    public function createCriteria(): void
    {
        $this->validate([
            'creating.criteria' => 'required|string|max:255',
            'creating.description' => 'required|string',
            'creating.weight' => 'required|numeric|min:0|max:100',
            'creating.type' => 'required|in:research,community_service,monev_research,monev_community_service',
        ], [], [
            'creating.criteria' => 'Nama Kriteria',
            'creating.description' => 'Deskripsi/Acuan',
            'creating.weight' => 'Bobot',
            'creating.type' => 'Jenis Kriteria',
        ]);

        $order = ReviewCriteria::where('type', $this->creating['type'])->max('order') ?? 0;

        ReviewCriteria::create([
            'type' => $this->creating['type'],
            'criteria' => $this->creating['criteria'],
            'description' => $this->creating['description'],
            'weight' => $this->creating['weight'],
            'order' => $order + 1,
            'is_active' => true,
        ]);

        $this->creating = [];
        $this->toastSuccess('Kriteria baru berhasil ditambahkan.');
    }

    public function render()
    {
        return view('livewire.settings.review-criteria-manager');
    }
}
