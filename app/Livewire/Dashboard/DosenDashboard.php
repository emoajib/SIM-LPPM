<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\HasToast;
use App\Models\Proposal;
use App\Services\SintaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Dashboard Dosen', 'pageTitle' => 'Ruang Peneliti', 'pageSubtitle' => 'Kelola usulan, publikasi, dan kolaborasi riset Anda'])]
class DosenDashboard extends Component
{
    use HasToast;

    public $user;

    public $roleName;

    public $stats = [];

    // Edit Metrics State
    public $showEditMetricsModal = false;

    public $sinta_score_v3_overall;

    public $scopus_h_index;

    public $gs_h_index;

    public $wos_h_index;

    // Zero Trust: Pastikan proses save mencakup validasi tipe data numerik

    public $recentResearch = [];

    public $recentCommunityService = [];

    public $selectedYear;

    public $availableYears = [];

    public function mount(): void
    {
        $this->user = Auth::user()->load('identity');
        $this->roleName = active_role();
        $this->selectedYear = date('Y');
        $this->availableYears = $this->getAvailableYears();

        $this->loadAnalytics();
    }

    public function updatedSelectedYear(): void
    {
        $this->loadAnalytics();
    }

    private function getAvailableYears(): array
    {
        $years = Proposal::select(DB::raw(sql_year().' as year'))
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        if (empty($years)) {
            $years = [date('Y')];
        }

        return $years;
    }

    public function loadAnalytics(): void
    {
        $yearFilter = $this->selectedYear;

        // OPTIMIZED: Single aggregated query for own proposals stats
        $this->loadStats($yearFilter);

        // Load as member counts (2 separate queries needed due to relationship)
        $this->loadMemberStats($yearFilter);

        // Load recent proposals
        $this->loadRecentProposals($yearFilter);

        // Pending invitations moved to NotificationCenter
    }

    /**
     * Load all stats in a single aggregated query.
     * Replaces 6 separate count queries with 1 grouped query.
     */
    private function loadStats(string $yearFilter): void
    {
        $statsRaw = Proposal::query()
            ->where('submitter_id', $this->user->id)
            ->whereYear('created_at', $yearFilter)
            ->select([
                'detailable_type',
                'status',
                DB::raw('COUNT(*) as count'),
            ])
            ->groupBy('detailable_type', 'status')
            ->get();

        $research = $statsRaw->filter(fn ($r) => str_contains($r->detailable_type ?? '', 'Research'));
        $communityService = $statsRaw->filter(fn ($r) => str_contains($r->detailable_type ?? '', 'CommunityService'));

        $this->stats = [
            'my_research' => $research->sum('count'),
            'my_community_service' => $communityService->sum('count'),
            'research_pending' => $research->filter(fn ($r) => ($r->status->value ?? '') === 'submitted')->sum('count'),
            'community_service_pending' => $communityService->filter(fn ($r) => ($r->status->value ?? '') === 'submitted')->sum('count'),
            'research_approved' => $research->filter(fn ($r) => ($r->status->value ?? '') === 'approved')->sum('count'),
            'community_service_approved' => $communityService->filter(fn ($r) => ($r->status->value ?? '') === 'approved')->sum('count'),
        ];
    }

    /**
     * Load member stats in optimized queries.
     * Uses raw query to avoid pivot column conflicts with GROUP BY.
     */
    private function loadMemberStats(string $yearFilter): void
    {
        // Use raw query builder to avoid pivot columns being auto-included
        $memberStats = DB::table('proposals')
            ->join('proposal_user', 'proposals.id', '=', 'proposal_user.proposal_id')
            ->where('proposal_user.user_id', $this->user->id)
            ->where('proposal_user.role', '!=', 'ketua') // Hanya hitung jika sebagai Anggota
            ->where('proposal_user.status', 'accepted') // Hanya hitung yang sudah dikonfirmasi
            ->whereYear('proposals.created_at', $yearFilter)
            ->select([
                'proposals.detailable_type',
                DB::raw('COUNT(*) as count'),
            ])
            ->groupBy('proposals.detailable_type')
            ->get();

        $this->stats['research_as_member'] = $memberStats
            ->filter(fn ($r) => str_contains($r->detailable_type ?? '', 'Research'))
            ->sum('count');

        $this->stats['community_service_as_member'] = $memberStats
            ->filter(fn ($r) => str_contains($r->detailable_type ?? '', 'CommunityService'))
            ->sum('count');
    }

    /**
     * Load recent proposals in a single query.
     */
    private function loadRecentProposals(string $yearFilter): void
    {
        $recentProposals = Proposal::query()
            ->with(['researchScheme', 'communityServiceScheme'])
            ->where('submitter_id', $this->user->id)
            ->whereYear('created_at', $yearFilter)
            ->latest()
            ->limit(20)
            ->get();

        $this->recentResearch = $recentProposals
            ->filter(fn ($p) => str_contains($p->detailable_type, 'Research'))
            ->take(10)
            ->values();

        $this->recentCommunityService = $recentProposals
            ->filter(fn ($p) => str_contains($p->detailable_type, 'CommunityService'))
            ->take(10)
            ->values();
    }

    public function syncSinta(SintaService $sintaService): void
    {
        $result = $sintaService->syncAuthorProfile($this->user);

        if ($result['success']) {
            $this->toastSuccess($result['message']);
            $this->user->refresh();
        } else {
            $this->toastError($result['message']);
        }
    }

    public function openEditMetricsModal(): void
    {
        $identity = $this->user->identity;
        $this->sinta_score_v3_overall = $identity->sinta_score_v3_overall ?? 0;
        $this->scopus_h_index = $identity->scopus_h_index;
        $this->gs_h_index = $identity->gs_h_index;
        $this->wos_h_index = $identity->wos_h_index;

        $this->showEditMetricsModal = true;
    }

    public function saveMetrics(): void
    {
        $this->validate([
            'sinta_score_v3_overall' => 'required|numeric|min:0',
            'scopus_h_index' => 'nullable|numeric|min:0',
            'gs_h_index' => 'nullable|numeric|min:0',
            'wos_h_index' => 'nullable|numeric|min:0',
        ]);

        $identity = $this->user->identity;
        if ($identity) {
            $identity->update([
                'sinta_score_v3_overall' => $this->sinta_score_v3_overall,
                'scopus_h_index' => $this->scopus_h_index,
                'gs_h_index' => $this->gs_h_index,
                'wos_h_index' => $this->wos_h_index,
            ]);
            $this->toastSuccess('Metrik publikasi berhasil diperbarui secara manual.');
            $this->showEditMetricsModal = false;
            $this->user->refresh();
        } else {
            $this->toastError('Harap lengkapi Profil Identitas Dasar Anda terlebih dahulu.');
        }
    }

    public function render()
    {
        return view('livewire.dashboard.dosen-dashboard');
    }
}
