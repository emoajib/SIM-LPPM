<div>
    <div class="d-flex justify-content-end mb-4">
        <div class="d-flex align-items-center gap-2">
            <div class="dropdown">
                <a href="#" class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                    <i class="ti ti-calendar-event fs-2"></i>
                    <span>Tahun: {{ $selectedYear }}</span>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    @foreach ($availableYears as $year)
                        <a href="#" class="dropdown-item {{ $selectedYear == $year ? 'active' : '' }}"
                            wire:click.preserve-scroll="$set('selectedYear', {{ $year }})">
                            {{ $year }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Urgent Action Section -->
    @if($overdueReviews->isNotEmpty() || $dueSoonReviews->isNotEmpty() || $reReviewNeeded->isNotEmpty())
        <div class="row row-cards mb-4">
            <!-- Overdue Alerts -->
            @foreach($overdueReviews as $review)
                <div class="col-md-6 col-lg-4">
                    <div class="card bg-danger-lt border-danger shadow-sm overflow-hidden border-0 border-start border-4" style="border-radius: 12px;">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar bg-danger text-danger-fg shadow-sm">
                                    <i class="ti ti-alert-triangle fs-2"></i>
                                </div>
                                <div class="flex-fill overflow-hidden">
                                    <div class="fw-bold text-danger mb-0">TERLAMBAT ({{ $review->days_overdue }} Hari)</div>
                                    <div class="text-wrap lh-base text-dark fw-bold" title="{{ $review->proposal->title }}">
                                        {{ $review->proposal->title }}
                                    </div>
                                </div>
                                <a href="{{ route($review->proposal->detailable_type === 'App\Models\Research' ? 'research.proposal.show' : 'community-service.proposal.show', $review->proposal) }}" 
                                    class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm" wire:navigate.hover>
                                    Review
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Re-Review Needed -->
            @foreach($reReviewNeeded as $review)
                <div class="col-md-6 col-lg-4">
                    <div class="card bg-warning-lt border-warning shadow-sm overflow-hidden border-0 border-start border-4" style="border-radius: 12px;">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar bg-warning text-warning-fg shadow-sm">
                                    <i class="ti ti-refresh fs-2"></i>
                                </div>
                                <div class="flex-fill overflow-hidden">
                                    <div class="fw-bold text-warning mb-0">REVIEW ULANG (PUTARAN {{ $review->round }})</div>
                                    <div class="text-wrap lh-base text-dark fw-bold" title="{{ $review->proposal->title }}">
                                        {{ $review->proposal->title }}
                                    </div>
                                </div>
                                <a href="{{ route($review->proposal->detailable_type === 'App\Models\Research' ? 'research.proposal.show' : 'community-service.proposal.show', $review->proposal) }}" 
                                    class="btn btn-warning btn-sm rounded-pill px-3 shadow-sm" wire:navigate.hover>
                                    Mulai
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Due Soon -->
            @foreach($dueSoonReviews as $review)
                <div class="col-md-6 col-lg-4">
                    <div class="card bg-info-lt border-info shadow-sm overflow-hidden border-0 border-start border-4" style="border-radius: 12px;">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar bg-info text-info-fg shadow-sm">
                                    <i class="ti ti-clock-pause fs-2"></i>
                                </div>
                                <div class="flex-fill overflow-hidden">
                                    <div class="fw-bold text-info mb-0">DEADLINE DEKAT ({{ $review->days_remaining }} Hari)</div>
                                    <div class="text-wrap lh-base text-dark fw-bold" title="{{ $review->proposal->title }}">
                                        {{ $review->proposal->title }}
                                    </div>
                                </div>
                                <a href="{{ route($review->proposal->detailable_type === 'App\Models\Research' ? 'research.proposal.show' : 'community-service.proposal.show', $review->proposal) }}" 
                                    class="btn btn-info btn-sm rounded-pill px-3 shadow-sm" wire:navigate.hover>
                                    Proses
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if(!empty($chartData['labels']))
        <div class="row row-cards mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                    <div class="card-header bg-transparent border-0 py-3 d-flex align-items-center">
                        <div class="avatar bg-primary-lt text-primary shadow-sm avatar-sm me-3 border-0">
                            <i class="ti ti-chart-line"></i>
                        </div>
                        <h3 class="card-title fw-bold mb-0">Tren Review Selesai</h3>
                    </div>
                    <div class="card-body">
                        <div style="position:relative;height:250px;width:100%;"
                             wire:ignore
                             x-data="{
                                 chart: null,
                                 renderTrendChart(data, isReinit = false) {
                                     if (!data || !data.labels || !data.labels.length || !data.datasets || !data.datasets.length) return;
                                     if (typeof Chart === 'undefined') {
                                         setTimeout(() => this.renderTrendChart(data, isReinit), 100);
                                         return;
                                     }
                                     const ctx = this.$refs.trendCanvas?.getContext('2d');
                                     if (!ctx) return;
                                     if (this.chart) {
                                         this.chart.destroy();
                                         this.chart = null;
                                     }
                                     this.chart = new Chart(ctx, {
                                         type: 'line',
                                         data: {
                                             labels: data.labels,
                                             datasets: data.datasets.map(function(ds) {
                                                 return { 
                                                     label: ds.label, 
                                                     data: ds.data, 
                                                     borderColor: ds.borderColor, 
                                                     backgroundColor: ds.backgroundColor, 
                                                     fill: true, 
                                                     tension: 0.4, 
                                                     pointRadius: 5, 
                                                     pointHoverRadius: 9, 
                                                     pointHoverBorderWidth: 3, 
                                                     pointHoverBorderColor: '#ffffff' 
                                                 };
                                             }),
                                         },
                                         options: {
                                             responsive: true, 
                                             maintainAspectRatio: false,
                                             animation: { duration: isReinit ? 0 : 800 },
                                             hover: { mode: 'index', intersect: false },
                                             plugins: {
                                                 legend: { display: true, position: 'bottom' },
                                                 tooltip: {
                                                     enabled: true,
                                                     backgroundColor: 'rgba(30,41,59,0.95)',
                                                     padding: 12, 
                                                     cornerRadius: 8,
                                                     titleFont: { weight: 'bold', size: 13 },
                                                     bodyFont: { size: 12 },
                                                     callbacks: {
                                                         title: function(items) { return 'Tahun ' + items[0].label; },
                                                         label: function(item) { return item.dataset.label + ': ' + item.formattedValue + ' proposal'; }
                                                     }
                                                 }
                                             },
                                             scales: {
                                                 x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 10 } } },
                                                 y: { grid: { color: 'rgba(229,231,235,0.5)' }, ticks: { color: '#9ca3af', font: { size: 10 }, stepSize: 1 } },
                                             },
                                         },
                                     });
                                 },
                                 destroy() {
                                     if (this.chart) {
                                         this.chart.destroy();
                                         this.chart = null;
                                     }
                                 }
                             }"
                             x-init="$nextTick(() => $data.renderTrendChart(@js($chartData)))"
                             @chart-updated.window="
                                 if ($event.detail.trendChart) {
                                     renderTrendChart($event.detail.trendChart, true);
                                 }
                             ">
                            <canvas x-ref="trendCanvas" aria-label="Grafik Tren Usulan & Pendanaan" role="img"></canvas>
                    </div>
                </div>
            </div>
        </div>
        </div>
    @endif

    <!-- Reviewer KPI Rails -->
    <div class="row row-deck row-cards mb-4">
        <!-- Research Stats -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm overflow-hidden h-100" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4">
                        <div class="subheader text-primary fw-bold">Statistik Penelitian</div>
                        <div class="ms-auto text-primary bg-primary-lt rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="ti ti-microscope fs-3"></i>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-4">
                            <div class="text-muted small mb-1">Assigned</div>
                            <div class="h2 mb-0 fw-bold">{{ $stats['research_assigned'] }}</div>
                        </div>
                        <div class="col-4 border-start ps-3">
                            <div class="text-success small mb-1">Done</div>
                            <div class="h2 mb-0 fw-bold text-success">{{ $stats['research_completed'] }}</div>
                        </div>
                        <div class="col-4 border-start ps-3">
                            <div class="text-warning small mb-1">Pending</div>
                            <div class="h2 mb-0 fw-bold text-warning">{{ $stats['research_pending'] }}</div>
                        </div>
                    </div>
                    <div class="progress progress-sm mt-4 card-progress overflow-visible">
                        @php 
                            $researchPercent = ($stats['research_assigned'] > 0) ? ($stats['research_completed'] / $stats['research_assigned']) * 100 : 0;
                        @endphp
                        <div class="progress-bar bg-primary" @style(['width' => $researchPercent . '%']) role="progressbar"></div>
                        <span class="badge bg-primary position-absolute end-0 top-100 mt-1">{{ round($researchPercent) }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- PKM Stats -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm overflow-hidden h-100" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4">
                        <div class="subheader text-green fw-bold">Statistik Pengabdian (PKM)</div>
                        <div class="ms-auto text-green bg-green-lt rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="ti ti-users fs-3"></i>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-4">
                            <div class="text-muted small mb-1">Assigned</div>
                            <div class="h2 mb-0 fw-bold">{{ $stats['community_service_assigned'] }}</div>
                        </div>
                        <div class="col-4 border-start ps-3">
                            <div class="text-success small mb-1">Done</div>
                            <div class="h2 mb-0 fw-bold text-success">{{ $stats['community_service_completed'] }}</div>
                        </div>
                        <div class="col-4 border-start ps-3">
                            <div class="text-warning small mb-1">Pending</div>
                            <div class="h2 mb-0 fw-bold text-warning">{{ $stats['community_service_pending'] }}</div>
                        </div>
                    </div>
                    <div class="progress progress-sm mt-4 card-progress overflow-visible">
                        @php 
                            $pkmPercent = ($stats['community_service_assigned'] > 0) ? ($stats['community_service_completed'] / $stats['community_service_assigned']) * 100 : 0;
                        @endphp
                        <div class="progress-bar bg-green" @style(['width' => $pkmPercent . '%']) role="progressbar"></div>
                        <span class="badge bg-green position-absolute end-0 top-100 mt-1">{{ round($pkmPercent) }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Review Task Tables -->
    <div class="row row-cards">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-transparent border-0 py-3 d-flex align-items-center">
                    <div class="avatar bg-primary-lt text-primary shadow-sm avatar-sm me-3 border-0">
                        <i class="ti ti-flask-2"></i>
                    </div>
                    <h3 class="card-title fw-bold mb-0">Tugas Penelitian (Butuh Review)</h3>
                    <div class="ms-auto">
                        <a href="{{ route('review.research') }}" class="btn btn-ghost-primary btn-sm rounded-pill px-3" wire:navigate.hover>Lihat Semua</a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover table-borderless">
                        <thead class="bg-transparent text-muted">
                            <tr>
                                <th class="ps-4">Judul & Pengaju</th>
                                <th class="text-center">Status</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentResearch as $research)
                                @php $review = $researchReviewerStats->where('proposal_id', $research->id)->first(); @endphp
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark text-wrap lh-base" title="{{ $research->title }}">
                                            {{ $research->title }}
                                        </div>
                                        <div class="small text-muted d-flex align-items-center mt-1">
                                            <i class="ti ti-user me-1"></i> {{ $research->submitter->name }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @php $badgeColor = $review?->status->color() ?? 'secondary'; $badgeLabel = $review?->status->label() ?? 'Pending'; @endphp
                                        <span class="badge bg-{{ $badgeColor }}-lt fw-bold px-2 py-1"><span class="badge bg-{{ $badgeColor }} me-1"></span>{{ $badgeLabel }}</span>
                                        @if($review && $review->round > 1)
                                            <div class="small text-muted mt-1">Putaran {{ $review->round }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="{{ route('research.proposal.show', $research) }}" class="btn btn-icon btn-outline-primary btn-sm rounded-circle shadow-sm" title="Buka Detail" wire:navigate.hover>
                                            <i class="ti ti-chevron-right fs-2"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="py-5 text-center text-muted">Belum ada tugas penelitian</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-transparent border-0 py-3 d-flex align-items-center">
                    <div class="avatar bg-green-lt text-green shadow-sm avatar-sm me-3 border-0">
                        <i class="ti ti-users-group"></i>
                    </div>
                    <h3 class="card-title fw-bold mb-0">Tugas PKM (Butuh Review)</h3>
                    <div class="ms-auto">
                        <a href="{{ route('review.community-service') }}" class="btn btn-ghost-green btn-sm rounded-pill px-3" wire:navigate.hover>Lihat Semua</a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover table-borderless">
                        <thead class="bg-transparent text-muted">
                            <tr>
                                <th class="ps-4">Judul & Pengaju</th>
                                <th class="text-center">Status</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentCommunityService as $pkm)
                                @php $review = $communityServiceReviewerStats->where('proposal_id', $pkm->id)->first(); @endphp
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark text-wrap lh-base" title="{{ $pkm->title }}">
                                            {{ $pkm->title }}
                                        </div>
                                        <div class="small text-muted d-flex align-items-center mt-1">
                                            <i class="ti ti-user me-1"></i> {{ $pkm->submitter->name }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @php $badgeColor = $review?->status->color() ?? 'secondary'; $badgeLabel = $review?->status->label() ?? 'Pending'; @endphp
                                        <span class="badge bg-{{ $badgeColor }}-lt fw-bold px-2 py-1"><span class="badge bg-{{ $badgeColor }} me-1"></span>{{ $badgeLabel }}</span>
                                        @if($review && $review->round > 1)
                                            <div class="small text-muted mt-1">Putaran {{ $review->round }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="{{ route('community-service.proposal.show', $pkm) }}" class="btn btn-icon btn-outline-green btn-sm rounded-circle shadow-sm" title="Buka Detail" wire:navigate.hover>
                                            <i class="ti ti-chevron-right fs-2"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="py-5 text-center text-muted">Belum ada tugas PKM</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</div>
