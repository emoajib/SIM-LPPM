<div>
    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <div class="dropdown">
            <a href="#" class="btn btn-success dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                <i class="ti ti-file-spreadsheet fs-2"></i>
                <span>Export Excel</span>
            </a>
            <div class="dropdown-menu dropdown-menu-end">
                <a href="#" class="dropdown-item" wire:click="exportResearch">
                    <i class="ti ti-flask me-2"></i> Penelitian
                </a>
                <a href="#" class="dropdown-item" wire:click="exportCommunityService">
                    <i class="ti ti-users-group me-2"></i> PKM
                </a>
            </div>
        </div>
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
        <div class="dropdown">
            <a href="#" class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                <i class="ti ti-filter fs-2"></i>
                <span>Status: {{ $availableStatuses[$selectedStatus] ?? 'Semua Status' }}</span>
            </a>
            <div class="dropdown-menu dropdown-menu-end" style="max-height: 300px; overflow-y: auto;">
                @foreach ($availableStatuses as $value => $label)
                    <a href="#" class="dropdown-item {{ $selectedStatus === $value ? 'active' : '' }}"
                        wire:click.preserve-scroll="$set('selectedStatus', '{{ $value }}')">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>
        <div class="dropdown">
            <a href="#" class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                <i class="ti ti-building fs-2"></i>
                <span>Fakultas: {{ $availableFaculties[$selectedFaculty] ?? 'Semua Fakultas' }}</span>
            </a>
            <div class="dropdown-menu dropdown-menu-end" style="max-height: 300px; overflow-y: auto;">
                @foreach ($availableFaculties as $value => $label)
                    <a href="#" class="dropdown-item {{ $selectedFaculty == $value ? 'active' : '' }}"
                        wire:click.preserve-scroll="$set('selectedFaculty', '{{ $value }}')">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>
        <div class="dropdown">
            <a href="#" class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                <i class="ti ti-book fs-2"></i>
                <span>Prodi: {{ $availableProdis[$selectedProdi] ?? 'Semua Prodi' }}</span>
            </a>
            <div class="dropdown-menu dropdown-menu-end" style="max-height: 300px; overflow-y: auto;">
                @foreach ($availableProdis as $value => $label)
                    <a href="#" class="dropdown-item {{ $selectedProdi == $value ? 'active' : '' }}"
                        wire:click.preserve-scroll="$set('selectedProdi', '{{ $value }}')">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>
        <div class="dropdown">
            <a href="#" class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                <i class="ti ti-calendar-stats fs-2"></i>
                <span>Semester: {{ $selectedSemester === 'all' ? 'Semua' : ($selectedSemester === 'ganjil' ? 'Ganjil' : 'Genap') }}</span>
            </a>
            <div class="dropdown-menu dropdown-menu-end">
                <a href="#" class="dropdown-item {{ $selectedSemester === 'all' ? 'active' : '' }}"
                    wire:click.preserve-scroll="$set('selectedSemester', 'all')">
                    Semua
                </a>
                <a href="#" class="dropdown-item {{ $selectedSemester === 'ganjil' ? 'active' : '' }}"
                    wire:click.preserve-scroll="$set('selectedSemester', 'ganjil')">
                    Ganjil
                </a>
                <a href="#" class="dropdown-item {{ $selectedSemester === 'genap' ? 'active' : '' }}"
                    wire:click.preserve-scroll="$set('selectedSemester', 'genap')">
                    Genap
                </a>
            </div>
        </div>
        <div class="dropdown">
            <a href="#" class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                <i class="ti ti-flask fs-2"></i>
                <span>Skema Penelitian: {{ $availableResearchSchemes[$selectedResearchScheme] ?? 'Semua Skema' }}</span>
            </a>
            <div class="dropdown-menu dropdown-menu-end" style="max-height: 300px; overflow-y: auto;">
                @foreach ($availableResearchSchemes as $value => $label)
                    <a href="#" class="dropdown-item {{ $selectedResearchScheme == $value ? 'active' : '' }}"
                        wire:click.preserve-scroll="$set('selectedResearchScheme', '{{ $value }}')">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>
        <div class="dropdown">
            <a href="#" class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                <i class="ti ti-users-group fs-2"></i>
                <span>Skema PKM: {{ $availableCommunityServiceSchemes[$selectedCommunityServiceScheme] ?? 'Semua Skema' }}</span>
            </a>
            <div class="dropdown-menu dropdown-menu-end" style="max-height: 300px; overflow-y: auto;">
                @foreach ($availableCommunityServiceSchemes as $value => $label)
                    <a href="#" class="dropdown-item {{ $selectedCommunityServiceScheme == $value ? 'active' : '' }}"
                        wire:click.preserve-scroll="$set('selectedCommunityServiceScheme', '{{ $value }}')">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>
        
        {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
        @if ($selectedSemester !== 'all' || $selectedStatus !== 'all' || $selectedFaculty !== 'all' || $selectedProdi !== 'all' || $selectedResearchScheme !== 'all' || $selectedCommunityServiceScheme !== 'all')
            <button class="btn btn-ghost-danger d-flex align-items-center gap-1 shadow-sm"
                type="button"
                wire:click="resetFilters"
                title="Reset semua filter">
                <i class="ti ti-x"></i>
                <span>Reset</span>
            </button>
        @endif
    </div>

    <div class="row row-deck row-cards mb-4">
        <!-- KPI Section: Research -->
        <div class="col-sm-6 col-lg-3">
            <x-dashboard.kpi-widget 
                title="Penelitian (Total)" 
                value="{{ $stats['total_research'] ?? 0 }}" 
                subtitle="Proposal terdaftar" 
                icon="flask" 
                color="primary" />
        </div>

        <div class="col-sm-6 col-lg-3">
            <x-dashboard.kpi-widget 
                title="PKM (Total)" 
                value="{{ $stats['total_community_service'] ?? 0 }}" 
                subtitle="Proposal pengabdian" 
                icon="users-group" 
                color="azure" />
        </div>

        <!-- KPI Section: Approval Rate -->
        <div class="col-sm-6 col-lg-3">
            @php
                $totalProp = $stats['total_research'] + $stats['total_community_service'];
                $totalAppr = $stats['research_approved'] + $stats['community_service_approved'];
                $approvalRate = ($totalProp > 0) ? round(($totalAppr / $totalProp) * 100, 1) : 0;
                $totalBudget = ($stats['research_budget'] ?? 0) + ($stats['pkm_budget'] ?? 0);
            @endphp
            <x-dashboard.kpi-widget 
                title="Approval Rate" 
                value="{{ $approvalRate }}%" 
                subtitle="{{ $totalAppr }} dari {{ $totalProp }} usulan disetujui" 
                icon="chart-bar" 
                color="green" />
        </div>

        <!-- KPI Section: Total Budget -->
        <div class="col-sm-6 col-lg-3">
            <x-dashboard.kpi-widget 
                title="Total Anggaran" 
                value="Rp {{ number_format($totalBudget, 0, ',', '.') }}" 
                subtitle="Penelitian: Rp {{ number_format($stats['research_budget'] ?? 0, 0, ',', '.') }} • PKM: Rp {{ number_format($stats['pkm_budget'] ?? 0, 0, ',', '.') }}" 
                icon="cash" 
                color="purple" />
        </div>
    </div>    <!-- Analytics Charts -->
    <div class="row row-cards mb-4">
        <div class="col-lg-6">
            <x-dashboard.analytics-chart 
                type="bar" 
                title="Distribusi Bidang Fokus" 
                :labels="$focusAreasChartData['labels']" 
                :datasets="$focusAreasChartData['datasets']" />
        </div>
        <div class="col-lg-6">
            <x-dashboard.analytics-chart 
                type="bar" 
                title="Performa Usulan per Fakultas" 
                :labels="$facultyPerformanceChartData['labels']" 
                :datasets="$facultyPerformanceChartData['datasets']" />
        </div>
    </div>
 
    <div class="row row-cards mb-4">
        <div class="col-lg-6">
            <x-dashboard.analytics-chart 
                type="bar" 
                title="Distribusi Rumpun Ilmu dan PKM" 
                :labels="$scienceClustersChartData['labels']" 
                :datasets="$scienceClustersChartData['datasets']" />
        </div>
        <div class="col-lg-6">
            <x-dashboard.analytics-chart 
                type="bar" 
                title="Distribusi Tingkat Kesiapterapan Teknologi (TKT)" 
                :labels="$tktChartData['labels']" 
                :datasets="$tktChartData['datasets']" />
        </div>
    </div>
 
    {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
    <div class="row row-cards mb-4">
        <div class="col-lg-6">
            <x-dashboard.analytics-chart 
                type="bar" 
                title="Distribusi Tema Penelitian dan PKM" 
                :labels="$themesChartData['labels']" 
                :datasets="$themesChartData['datasets']" />
        </div>
        <div class="col-lg-6">
            <x-dashboard.analytics-chart 
                type="bar" 
                title="Distribusi Topik Penelitian dan PKM" 
                :labels="$topicsChartData['labels']" 
                :datasets="$topicsChartData['datasets']" />
        </div>
    </div>

    @if(!empty($chartData['labels']))
        <div class="row row-cards mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm" style="border-radius: 12px; transition: transform 0.2s ease, box-shadow 0.2s ease; cursor: default;"
                     onmouseover="this.style.transform='scale(1.015)';this.style.boxShadow='0 8px 25px rgba(0,0,0,0.12)'"
                     onmouseout="this.style.transform='scale(1)';this.style.boxShadow='none'">
                    <div class="card-header bg-transparent border-0 py-3 d-flex align-items-center">
                        <div class="avatar bg-primary-lt text-primary shadow-sm avatar-sm me-3 border-0">
                            <i class="ti ti-chart-line"></i>
                        </div>
                        <h3 class="card-title fw-bold mb-0">Tren Usulan & Pendanaan</h3>
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
                                     if (typeof ChartDataLabels !== 'undefined' && !Chart.registry.plugins.get('datalabels')) {
                                         Chart.register(ChartDataLabels);
                                     }
                                     this.chart = new Chart(ctx, {
                                         type: 'line',
                                         data: {
                                             labels: data.labels,
                                             datasets: data.datasets.map(function(ds) {
                                                 return { label: ds.label, data: ds.data, borderColor: ds.borderColor, backgroundColor: ds.backgroundColor, fill: true, tension: 0.4, pointRadius: 5, pointHoverRadius: 9, pointHoverBorderWidth: 3, pointHoverBorderColor: '#ffffff' };
                                             }),
                                         },
                                         options: {
                                             responsive: true, maintainAspectRatio: false,
                                             animation: { duration: isReinit ? 0 : 800 },
                                             hover: { mode: 'index', intersect: false },
                                             plugins: {
                                                 legend: { display: true, position: 'bottom' },
                                                 tooltip: {
                                                     enabled: true,
                                                     backgroundColor: 'rgba(30,41,59,0.95)',
                                                     padding: 12, cornerRadius: 8,
                                                     titleFont: { weight: 'bold', size: 13 },
                                                     bodyFont: { size: 12 },
                                                     callbacks: {
                                                         title: function(items) { return 'Tahun ' + items[0].label; },
                                                         label: function(item) { return item.dataset.label + ': ' + item.formattedValue + ' proposal'; }
                                                     }
                                                 },
                                                 datalabels: {
                                                     display: function(context) {
                                                         return context.dataset.data[context.dataIndex] > 0;
                                                     },
                                                     color: function(context) {
                                                         return context.dataset.borderColor;
                                                     },
                                                     font: { weight: 'bold', size: 11 },
                                                     anchor: 'end',
                                                     align: 'end',
                                                     offset: 2
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
    @endif

    <!-- Process Monitoring (Progress Styles) -->
    <div class="row row-cards mb-4">
        <!-- Review Progress Details -->
        <div class="col-md-4">
            <div class="card glass-card border-0 shadow-sm overflow-hidden" style="border-left: 4px solid #f59f00 !important;">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="subheader text-warning fw-bold">Progress Review</div>
                        <div class="ms-auto">
                            <span class="badge bg-warning-lt">{{ $processStats['review_progress'] }}%</span>
                        </div>
                    </div>
                    <div class="progress progress-sm shadow-none bg-warning-lt">
                        <div class="progress-bar bg-warning" style="width: {{ $processStats['review_progress'] }}%"></div>
                    </div>
                    <div class="mt-2 small text-muted">
                        {{ $processStats['review_completed'] }} dari {{ $processStats['review_total'] }} proposal selesai direview
                    </div>
                </div>
            </div>
        </div>

        <!-- Monev Progress Details -->
        <div class="col-md-4">
            <div class="card glass-card border-0 shadow-sm overflow-hidden" style="border-left: 4px solid #00b8d4 !important;">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="subheader text-info fw-bold">Progress Monev</div>
                        <div class="ms-auto">
                            <span class="badge bg-info-lt">{{ $processStats['monev_progress'] }}%</span>
                        </div>
                    </div>
                    <div class="progress progress-sm shadow-none bg-info-lt">
                        <div class="progress-bar bg-info" style="width: {{ $processStats['monev_progress'] }}%"></div>
                    </div>
                    <div class="mt-2 small text-muted">
                        {{ $processStats['monev_completed'] }} dari {{ $processStats['monev_total'] }} proposal selesai dimonitoring
                    </div>
                </div>
            </div>
        </div>

        <!-- IKU Progress Details -->
        <div class="col-md-4">
            <div class="card glass-card border-0 shadow-sm overflow-hidden" style="border-left: 4px solid #206bc4 !important;">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="subheader text-primary fw-bold">Progress IKU (Luaran)</div>
                        <div class="ms-auto">
                            <span class="badge bg-primary-lt">{{ number_format($processStats['output_progress'], 1) }}%</span>
                        </div>
                    </div>
                    <div class="progress progress-sm shadow-none bg-primary-lt">
                        <div class="progress-bar bg-primary" style="width: {{ $processStats['output_progress'] }}%"></div>
                    </div>
                    <div class="mt-2 small text-muted">
                        {{ $processStats['output_achieved'] }} dari {{ $processStats['output_target'] }} target luaran tercapai
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cards mt-4">
        <!-- Recent Research Table -->
        <div class="col-lg-6">
            <div class="card glass-card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 py-3">
                    <div class="d-flex align-items-center justify-content-between w-100">
                        <div class="d-flex align-items-center">
                            <div class="avatar bg-primary-lt text-primary shadow-sm avatar-sm me-3 border-0">
                                <i class="ti ti-flask-2"></i>
                            </div>
                            <h3 class="card-title fw-bold mb-0">Penelitian Terbaru</h3>
                        </div>
                        <a href="{{ route('reports.research') }}" class="btn btn-sm btn-ghost-primary" wire:navigate>
                            Lihat Semua <i class="ti ti-chevron-right ms-1"></i>
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover table-borderless">
                        <thead class="bg-light-lt">
                            <tr>
                                <th class="ps-4">Judul & Pengajuan</th>
                                <th class="text-center">Status</th>
                                <th class="text-end pe-4">Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentResearch as $research)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-wrap lh-base" title="{{ $research->title }}">
                                            {{ $research->title }}
                                        </div>
                                        <div class="small text-muted d-flex align-items-center mt-1">
                                            <div class="avatar avatar-xs me-2 border-0 shadow-sm" style="background-image: url({{ $research->submitter->profile_picture }})"></div>
                                            {{ $research->submitter->name }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <x-tabler.badge :color="$research->status->color()" class="fw-normal">{{ $research->status->label() }}</x-tabler.badge>
                                    </td>
                                    <td class="text-end pe-4 text-muted small">
                                        {{ $research->updated_at->diffForHumans() }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-5">
                                        <div class="empty bg-transparent">
                                            <div class="empty-icon text-muted opacity-25">
                                                <i class="ti ti-ghost fs-1"></i>
                                            </div>
                                            <p class="empty-title">Data Kosong</p>
                                            <p class="empty-subtitle text-muted">Belum ada usulan penelitian teraktivasi.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent PKM Table -->
        <div class="col-lg-6">
            <div class="card glass-card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 py-3">
                    <div class="d-flex align-items-center justify-content-between w-100">
                        <div class="d-flex align-items-center">
                            <div class="avatar bg-azure-lt text-azure shadow-sm avatar-sm me-3 border-0">
                                <i class="ti ti-users-group"></i>
                            </div>
                            <h3 class="card-title fw-bold mb-0">PKM Terbaru</h3>
                        </div>
                        <a href="{{ route('reports.pkm') }}" class="btn btn-sm btn-ghost-primary" wire:navigate>
                            Lihat Semua <i class="ti ti-chevron-right ms-1"></i>
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover table-borderless">
                        <thead class="bg-light-lt">
                            <tr>
                                <th class="ps-4">Judul & Pengajuan</th>
                                <th class="text-center">Status</th>
                                <th class="text-end pe-4">Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentCommunityService as $communityService)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-wrap lh-base" title="{{ $communityService->title }}">
                                            {{ $communityService->title }}
                                        </div>
                                        <div class="small text-muted d-flex align-items-center mt-1">
                                            <div class="avatar avatar-xs me-2 border-0 shadow-sm" style="background-image: url({{ $communityService->submitter->profile_picture }})"></div>
                                            {{ $communityService->submitter->name }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <x-tabler.badge :color="$communityService->status->color()" class="fw-normal">{{ $communityService->status->label() }}</x-tabler.badge>
                                    </td>
                                    <td class="text-end pe-4 text-muted small">
                                        {{ $communityService->updated_at->diffForHumans() }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-5">
                                        <div class="empty bg-transparent">
                                            <div class="empty-icon text-muted opacity-25">
                                                <i class="ti ti-ghost fs-1"></i>
                                            </div>
                                            <p class="empty-title">Data Kosong</p>
                                            <p class="empty-subtitle text-muted">Belum ada usulan PKM teraktivasi.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <div class="row row-cards mt-4">
        <!-- System Maintenance / Backup Section -->
        <div class="col-12">
            <div class="card bg-dark text-white border-0 shadow-sm overflow-hidden">
                <div class="card-body py-4">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="avatar bg-white-lt text-white shadow-sm avatar-md border-0">
                                <i class="ti ti-settings fs-1"></i>
                            </span>
                        </div>
                        <div class="col">
                            <h3 class="card-title fw-bold mb-1">Pemeliharaan Sistem & Backup</h3>
                            <p class="text-muted-dark mb-0">
                                @if(config('app.env') === 'local')
                                    Gunakan tombol ini untuk menarik data terbaru dari website (Produksi) ke laptop Anda.
                                @else
                                    Gunakan tombol ini untuk mengunduh cadangan (backup) database terbaru dari server ini.
                                @endif
                            </p>
                        </div>
                        <div class="col-auto">
                            @if(config('app.env') === 'local')
                                <button type="button" class="btn btn-primary d-flex align-items-center gap-2" 
                                    wire:click="syncFromProduction" 
                                    wire:loading.attr="disabled">
                                    <span wire:loading.remove><i class="ti ti-cloud-download fs-2"></i> Sinkronisasi dari Website</span>
                                    <span wire:loading><span class="spinner-border spinner-border-sm me-2"></span> Memproses...</span>
                                </button>
                            @else
                                <a href="{{ route('settings.download-backup-db') }}" class="btn btn-outline-white d-flex align-items-center gap-2">
                                    <i class="ti ti-database-export fs-2"></i> Download Backup DB
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
