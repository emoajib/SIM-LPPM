<?php

namespace App\Livewire\Admin\Archive;

use App\Enums\ProposalStatus;
use App\Imports\HistoricalProposalImport;
use App\Models\Proposal;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('components.layouts.app', ['title' => 'Manajemen Arsip Data', 'pageTitle' => 'Manajemen Arsip Data'])]
class ManageArchives extends Component
{
    use WithFileUploads, WithPagination;

    public $search = '';

    public $yearFilter = '';

    public $importFile;

    public $showImportModal = false;

    // Edit State
    public $isEdit = false;

    public $editId;

    public $editTitle;

    public $editYear;

    public $editDana;

    public $editSummary;

    public $showEditModal = false;

    // Reset halaman saat filter berubah (hindari halaman kosong)
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedYearFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $proposals = Proposal::query()
            // QA FIX: eager load identity untuk cegah N+1 di blade ($archive->submitter->identity->nidn)
            ->with(['submitter.identity', 'budgetItems'])
            ->where('status', ProposalStatus::COMPLETED)
            ->when($this->search, function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhereHas('submitter', fn ($u) => $u->where('name', 'like', '%'.$this->search.'%'));
            })
            ->when($this->yearFilter, fn ($q) => $q->where('start_year', $this->yearFilter))
            ->orderByDesc('start_year')
            ->paginate(10);

        return view('livewire.admin.archive.manage-archives', [
            'archives' => $proposals,
        ]);
    }

    public function import()
    {
        $this->validate([
            'importFile' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $importer = new HistoricalProposalImport;
            Excel::import($importer, $this->importFile);

            $msg = "✅ Berhasil mengimport {$importer->imported} baris data arsip.";

            if (! empty($importer->failures)) {
                $details = implode(' | ', array_slice($importer->failures, 0, 5));
                $more = count($importer->failures) > 5 ? ' (dan '.(count($importer->failures) - 5).' lainnya...)' : '';
                session()->flash('warning', $msg.' ⚠ '.count($importer->failures).' baris dilewati: '.$details.$more);
            } else {
                session()->flash('success', $msg);
            }

            $this->showImportModal = false;
            $this->reset('importFile');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal import: '.$e->getMessage());
        }
    }

    public function edit(string $id)
    {
        // Zero Trust: verifikasi proposal ada & statusnya COMPLETED
        $p = Proposal::where('status', ProposalStatus::COMPLETED)->findOrFail($id);
        $this->editId = $p->id;
        $this->editTitle = $p->title;
        $this->editYear = $p->start_year;
        $this->editDana = (int) $p->approved_budget_total;
        $this->editSummary = $p->summary;

        $this->isEdit = true;
        $this->showEditModal = true;
    }

    public function update(): void
    {
        $this->validate([
            'editTitle' => 'required|string|max:255',
            'editYear' => 'required|integer|min:2000|max:'.date('Y'),
            'editDana' => 'required|numeric|min:0',
            'editSummary' => 'nullable|string|max:5000',
        ]);

        // Zero Trust: pastikan ID yang diedit masih COMPLETED (cegah manipulasi ID)
        $p = Proposal::where('status', ProposalStatus::COMPLETED)->findOrFail($this->editId);
        $p->update([
            'title' => $this->editTitle,
            'start_year' => $this->editYear,
            'summary' => $this->editSummary,
            'sbk_value' => $this->editDana,
        ]);

        session()->flash('success', 'Arsip berhasil diperbarui.');
        $this->showEditModal = false;
    }

    public function delete(string $id): void
    {
        // Zero Trust: hanya hapus proposal berstatus COMPLETED (cegah hapus proposal aktif)
        $proposal = Proposal::where('status', ProposalStatus::COMPLETED)->findOrFail($id);
        $proposal->delete();
        session()->flash('success', 'Arsip berhasil dihapus.');
    }

    #[On('open-import-modal')]
    public function openImportModal(): void
    {
        $this->showImportModal = true;
    }

    #[On('request-template-download')]
    public function downloadTemplate(): void
    {
        $this->dispatch('download-file', url: route('admin.archives.template'));
    }

    #[On('request-export-data')]
    public function exportData(): void
    {
        $url = route('admin.archives.export', [
            'search' => $this->search,
            'yearFilter' => $this->yearFilter,
        ]);

        $this->dispatch('download-file', url: $url);
    }
}
