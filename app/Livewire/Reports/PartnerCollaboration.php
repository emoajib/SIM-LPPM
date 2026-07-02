<?php

namespace App\Livewire\Reports;

use App\Actions\Reports\GetPartnerReportQuery;
use App\Enums\ProposalStatus;
use App\Livewire\Concerns\HasToast;
use App\Livewire\Traits\WithInstitutionalApproval;
use App\Models\Partner;
use App\Models\Proposal;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Laporan Kerjasama Mitra', 'pageTitle' => 'Laporan Kerjasama Mitra'])]
class PartnerCollaboration extends Component
{
    use HasToast, WithInstitutionalApproval, WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $typeFilter = '';

    #[Url]
    public string $periodFilter = '';

    public ?string $selectedPartnerId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function selectPartner(string $id): void
    {
        $this->selectedPartnerId = ($this->selectedPartnerId === $id) ? null : $id;
    }

    #[Computed]
    public function partnerTypes(): array
    {
        return Partner::query()
            ->whereNotNull('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->toArray();
    }

    #[Computed]
    public function periods(): array
    {
        return Proposal::query()
            ->distinct()
            ->whereNotNull('start_year')
            ->orderBy('start_year', 'desc')
            ->pluck('start_year')
            ->map(fn ($y) => (string) $y)
            ->toArray() ?: [(string) date('Y')];
    }

    #[Computed]
    public function summary(): array
    {
        $user = auth()->user();
        $facultyId = $user->activeHasRole('dekan') ? $user->identity?->faculty_id : null;

        $basePartner = Partner::query()
            ->when($facultyId, fn ($q) => $q->whereHas('proposals.submitter.identity', fn ($i) => $i->where('faculty_id', $facultyId)));

        $total = (clone $basePartner)->count();
        $withMou = (clone $basePartner)->whereHas('media', fn ($q) => $q->where('collection_name', 'mou_pks'))->count();
        $withProposal = (clone $basePartner)->whereHas(
            'proposals',
            fn ($q) => $q->when($this->periodFilter, fn ($q2) => $q2->where('start_year', $this->periodFilter))
        )->count();

        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $activeProposals = Proposal::query()
            ->whereHas('partners')
            ->whereIn('status', [ProposalStatus::APPROVED->value, ProposalStatus::COMPLETED->value])
            ->when($facultyId, fn ($q) => $q->whereHas('submitter.identity', fn ($i) => $i->where('faculty_id', $facultyId)))
            ->when($this->periodFilter, fn ($q) => $q->where('start_year', $this->periodFilter))
            ->with(['budgetItems'])
            ->get();

        $activeBudget = $activeProposals->sum(fn (Proposal $p) => ($p->sbk_value && $p->sbk_value > 0)
            ? (float) $p->sbk_value
            : $p->budgetItems->sum('total_price')
        );

        return [
            ['label' => 'Total Mitra Terdaftar', 'value' => $total, 'icon' => 'handshake', 'variant' => 'bg-blue-lt text-blue'],
            ['label' => 'Mitra Ber-MOU/PKS', 'value' => $withMou, 'icon' => 'file-check', 'variant' => 'bg-green-lt text-green'],
            ['label' => 'Mitra Aktif (Ada Proposal)', 'value' => $withProposal, 'icon' => 'users', 'variant' => 'bg-purple-lt text-purple'],
            ['label' => 'Total Dana Kerjasama', 'value' => 'Rp '.number_format($activeBudget, 0, ',', '.'), 'icon' => 'currency-dollar', 'variant' => 'bg-yellow-lt text-yellow'],
        ];
    }

    #[On('export-pdf')]
    public function exportPdf(): void
    {
        $url = route('reports.partner.pdf', [
            'search' => $this->search,
            'typeFilter' => $this->typeFilter,
            'periodFilter' => $this->periodFilter,
        ]);
        $this->dispatch('download-file', url: $url);
    }

    #[On('preview-pdf')]
    public function previewPdf(): void
    {
        $url = route('reports.partner.pdf', [
            'search' => $this->search,
            'typeFilter' => $this->typeFilter,
            'periodFilter' => $this->periodFilter,
            'preview' => true,
        ]);
        $this->dispatch('preview-pdf', url: $url);
    }

    #[On('export-excel')]
    public function exportExcel(): void
    {
        $url = route('reports.partner.excel', [
            'search' => $this->search,
            'typeFilter' => $this->typeFilter,
            'periodFilter' => $this->periodFilter,
        ]);
        $this->dispatch('download-file', url: $url);
    }

    public function render(GetPartnerReportQuery $action): View
    {
        $user = auth()->user();
        $facultyId = $user->activeHasRole('dekan') ? $user->identity?->faculty_id : null;

        $partners = $action->handle($this->search, $this->typeFilter, $this->periodFilter, $facultyId !== null ? (string) $facultyId : null)->paginate(15);

        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        // Hitung total dana secara dinamis dengan fallback ke RAB
        $partners->getCollection()->each(function (Partner $partner) use ($facultyId) {
            $proposals = $partner->proposals()
                ->whereIn('status', [ProposalStatus::APPROVED->value, ProposalStatus::COMPLETED->value])
                ->when($this->periodFilter, fn ($q) => $q->where('start_year', $this->periodFilter))
                ->when($facultyId, fn ($q) => $q->whereHas('submitter.identity', fn ($i) => $i->where('faculty_id', $facultyId)))
                ->with(['budgetItems'])
                ->get();

            $partner->total_budget = $proposals->sum(function ($p) {
                /** @var Proposal $p */
                return ($p->sbk_value && $p->sbk_value > 0)
                    ? (float) $p->sbk_value
                    : $p->budgetItems->sum('total_price');
            });
        });

        $detailProposals = null;
        if ($this->selectedPartnerId) {
            $detailProposals = Proposal::query()
                ->whereHas('partners', fn ($q) => $q->where('partners.id', $this->selectedPartnerId))
                ->when($facultyId, fn ($q) => $q->whereHas('submitter.identity', fn ($i) => $i->where('faculty_id', $facultyId)))
                ->when($this->periodFilter, fn ($q) => $q->where('start_year', $this->periodFilter))
                ->with(['submitter.identity', 'detailable'])
                ->latest()
                ->get();
        }

        return view('livewire.reports.partner-collaboration', [
            'partners' => $partners,
            'detailProposals' => $detailProposals,
            'institutionalReport' => $this->getInstitutionalReport('partner', (int) ($this->periodFilter ?: date('Y'))),
        ]);
    }
}
