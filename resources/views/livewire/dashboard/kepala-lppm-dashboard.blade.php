<div>
    {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        {{-- Export Dropdown --}}
        <div class="dropdown">
            <a href="#" class="btn btn-success dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                <i class="ti ti-file-spreadsheet"></i>
                <span>Export Excel</span>
            </a>
            <div class="dropdown-menu">
                <a href="#" class="dropdown-item" wire:click="exportResearch">
                    <i class="ti ti-flask me-2"></i> Penelitian
                </a>
                <a href="#" class="dropdown-item" wire:click="exportCommunityService">
                    <i class="ti ti-users-group me-2"></i> PKM
                </a>
            </div>
        </div>

        {{-- Tahun Dropdown --}}
        <div class="dropdown">
            <a href="#" class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                <i class="ti ti-calendar-event"></i>
                <span>Tahun: {{ $selectedYear }}</span>
            </a>
            <div class="dropdown-menu">
                @foreach ($availableYears as $year)
                    <a href="#" class="dropdown-item {{ $selectedYear == $year ? 'active' : '' }}"
                        wire:click.preserve-scroll="$set('selectedYear', {{ $year }})">
                        {{ $year }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Status Dropdown --}}
        <div class="dropdown">
            <a href="#" class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                <i class="ti ti-filter"></i>
                <span>Status: {{ $availableStatuses[$selectedStatus] ?? 'Semua Status' }}</span>
            </a>
            <div class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                @foreach ($availableStatuses as $value => $label)
                    <a href="#" class="dropdown-item {{ $selectedStatus === $value ? 'active' : '' }}"
                        wire:click.preserve-scroll="$set('selectedStatus', '{{ $value }}')">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Fakultas Dropdown --}}
        <div class="dropdown">
            <a href="#" class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                <i class="ti ti-building"></i>
                <span>Fakultas: {{ $availableFaculties[$selectedFaculty] ?? 'Semua Fakultas' }}</span>
            </a>
            <div class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                @foreach ($availableFaculties as $value => $label)
                    <a href="#" class="dropdown-item {{ $selectedFaculty == $value ? 'active' : '' }}"
                        wire:click.preserve-scroll="$set('selectedFaculty', '{{ $value }}')">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Prodi Dropdown --}}
        <div class="dropdown">
            <a href="#" class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                <i class="ti ti-book"></i>
                <span>Prodi: {{ $availableProdis[$selectedProdi] ?? 'Semua Prodi' }}</span>
            </a>
            <div class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                @foreach ($availableProdis as $value => $label)
                    <a href="#" class="dropdown-item {{ $selectedProdi == $value ? 'active' : '' }}"
                        wire:click.preserve-scroll="$set('selectedProdi', '{{ $value }}')">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Semester Dropdown --}}
        <div class="dropdown">
            <a href="#" class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                <i class="ti ti-calendar-stats"></i>
                <span>Semester: {{ $selectedSemester === 'all' ? 'Semua' : ($selectedSemester === 'ganjil' ? 'Ganjil' : 'Genap') }}</span>
            </a>
            <div class="dropdown-menu">
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

        {{-- Skema Penelitian Dropdown --}}
        <div class="dropdown">
            <a href="#" class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                <i class="ti ti-flask"></i>
                <span>Skema Penelitian: {{ $availableResearchSchemes[$selectedResearchScheme] ?? 'Semua Skema' }}</span>
            </a>
            <div class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                @foreach ($availableResearchSchemes as $value => $label)
                    <a href="#" class="dropdown-item {{ $selectedResearchScheme == $value ? 'active' : '' }}"
                        wire:click.preserve-scroll="$set('selectedResearchScheme', '{{ $value }}')">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Skema PKM Dropdown --}}
        <div class="dropdown">
            <a href="#" class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                <i class="ti ti-users-group"></i>
                <span>Skema PKM: {{ $availableCommunityServiceSchemes[$selectedCommunityServiceScheme] ?? 'Semua Skema' }}</span>
            </a>
            <div class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                @foreach ($availableCommunityServiceSchemes as $value => $label)
                    <a href="#" class="dropdown-item {{ $selectedCommunityServiceScheme == $value ? 'active' : '' }}"
                        wire:click.preserve-scroll="$set('selectedCommunityServiceScheme', '{{ $value }}')">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Reset Filter Button --}}
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
            <!-- Approval Summary KPI Cards -->
            <div class="row row-deck row-cards mb-4">
                <div class="col-sm-6 col-lg-3">
                    @php
                        $researchCountSubtitle = '<div class="mt-2"><strong>Total Proposal: ' . ($stats['total_research'] ?? 0) . '</strong><ul class="list-unstyled mb-0 ms-2 small">';
                        foreach ($stats['research_count_by_scheme'] ?? [] as $scheme => $count) {
                            $researchCountSubtitle .= '<li>- ' . htmlspecialchars($scheme) . ': ' . $count . '</li>';
                        }
                        $researchCountSubtitle .= '</ul></div>';
                    @endphp
                    <x-dashboard.kpi-widget 
                        title="Penelitian (Total)" 
                        value="{{ $stats['total_research'] ?? 0 }}" 
                        subtitle="{!! $researchCountSubtitle !!}" 
                        icon="flask" 
                        color="primary" />
                </div>

                <div class="col-sm-6 col-lg-3">
                    @php
                        $pkmCountSubtitle = '<div class="mt-2"><strong>Total Proposal: ' . ($stats['total_community_service'] ?? 0) . '</strong><ul class="list-unstyled mb-0 ms-2 small">';
                        foreach ($stats['pkm_count_by_scheme'] ?? [] as $scheme => $count) {
                            $pkmCountSubtitle .= '<li>- ' . htmlspecialchars($scheme) . ': ' . $count . '</li>';
                        }
                        $pkmCountSubtitle .= '</ul></div>';
                    @endphp
                    <x-dashboard.kpi-widget 
                        title="PKM (Total)" 
                        value="{{ $stats['total_community_service'] ?? 0 }}" 
                        subtitle="{!! $pkmCountSubtitle !!}" 
                        icon="users-group" 
                        color="azure" />
                </div>

                <!-- KPI Section: Approval Rate -->
                <div class="col-sm-6 col-lg-3">
                    @php
                        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
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
            @php
                $budgetSubtitle = '<div class="mt-2">
                    <strong>Penelitian: Rp ' . number_format($stats['research_budget'] ?? 0, 0, ',', '.') . '</strong>
                    <ul class="list-unstyled mb-2 ms-2 small">';
                foreach ($stats['research_budget_by_scheme'] ?? [] as $scheme => $budget) {
                    $budgetSubtitle .= '<li>- ' . htmlspecialchars($scheme) . ': Rp ' . number_format($budget, 0, ',', '.') . '</li>';
                }
                $budgetSubtitle .= '</ul>
                    <strong>PKM: Rp ' . number_format($stats['pkm_budget'] ?? 0, 0, ',', '.') . '</strong>
                    <ul class="list-unstyled mb-0 ms-2 small">';
                foreach ($stats['pkm_budget_by_scheme'] ?? [] as $scheme => $budget) {
                    $budgetSubtitle .= '<li>- ' . htmlspecialchars($scheme) . ': Rp ' . number_format($budget, 0, ',', '.') . '</li>';
                }
                $budgetSubtitle .= '</ul></div>';
            @endphp
                    <x-dashboard.kpi-widget 
                        title="Total Anggaran (Diusulkan)" 
                        value="Rp {{ number_format($totalBudget, 0, ',', '.') }}" 
                        subtitle="{!! $budgetSubtitle !!}" 
                        icon="cash" 
                        color="purple" />
                </div>
            </div>

            <!-- Analytics Charts -->
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
                                     renderTrendChart(data, isReinit = false) {
                                         if (!data || !data.labels || !data.labels.length || !data.datasets || !data.datasets.length) return;
                                         if (typeof Chart === 'undefined') {
                                             setTimeout(() => this.renderTrendChart(data, isReinit), 100);
                                             return;
                                         }
                                         const canvasEl = this.$refs.trendCanvas;
                                         if (!canvasEl) return;
                                         const ctx = canvasEl.getContext('2d');
                                         if (!ctx) return;
                                         if (canvasEl.chartInstance) {
                                             canvasEl.chartInstance.data.labels = data.labels;
                                             canvasEl.chartInstance.data.datasets = data.datasets.map(function(ds) {
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
                                             });
                                             canvasEl.chartInstance.update(isReinit ? 'none' : 'default');
                                             return;
                                         }
                                         canvasEl.chartInstance = new Chart(ctx, {
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

            <!-- Alur Tahapan Proses Penelitian & Pengabdian (Interactive Process Cards) -->
            <div class="row row-cards mb-3">
                <div class="col-12">
                    <div class="d-flex align-items-center mb-1">
                        <span class="badge bg-primary-lt text-primary fw-bold me-2 px-2 py-1">
                            <i class="ti ti-git-fork me-1"></i>Siklus Tridharma
                        </span>
                        <h4 class="text-dark fw-bold mb-0">Tahapan Proses Penelitian & Pengabdian</h4>
                        <span class="text-muted small ms-auto d-none d-md-inline">
                            <i class="ti ti-hand-click me-1"></i>Klik kartu untuk melihat rincian data usulan di dasbor
                        </span>
                    </div>
                </div>

                <!-- 1. Usulan (Proposal Baru Masuk) -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card glass-card border-0 shadow-sm overflow-hidden h-100 cursor-pointer" 
                         style="border-left: 4px solid #206bc4 !important; cursor: pointer; transition: all 0.2s ease;"
                         wire:click="openProcessModal('usulan')"
                         title="Klik untuk melihat data usulan">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="subheader text-primary fw-bold d-flex align-items-center">
                                    <i class="ti ti-file-text me-1"></i>Usulan
                                </div>
                                <div class="ms-auto">
                                    <span class="badge bg-primary-lt">{{ $processStats['usulan_total'] ?? 0 }} Total</span>
                                </div>
                            </div>
                            <div class="h2 mb-1 fw-bold text-dark">
                                {{ $processStats['usulan_total'] ?? 0 }} <span class="fs-4 fw-normal text-muted">Usulan</span>
                            </div>
                            <div class="mt-2 small text-muted d-flex justify-content-between align-items-center">
                                <span>{{ $processStats['usulan_submitted'] ?? 0 }} Diajukan • {{ $processStats['usulan_draft'] ?? 0 }} Draf</span>
                                <span class="text-primary fw-semibold fs-4"><i class="ti ti-arrow-right"></i></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Perbaikan Usulan (Revisi) -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card glass-card border-0 shadow-sm overflow-hidden h-100 cursor-pointer" 
                         style="border-left: 4px solid #f59f00 !important; cursor: pointer; transition: all 0.2s ease;"
                         wire:click="openProcessModal('perbaikan_usulan')"
                         title="Klik untuk melihat data perbaikan usulan">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="subheader text-warning fw-bold d-flex align-items-center">
                                    <i class="ti ti-refresh me-1"></i>Perbaikan Usulan
                                </div>
                                <div class="ms-auto">
                                    <span class="badge bg-warning-lt">{{ $processStats['revision_total'] ?? 0 }} Revisi</span>
                                </div>
                            </div>
                            <div class="h2 mb-1 fw-bold text-dark">
                                {{ $processStats['revision_total'] ?? 0 }} <span class="fs-4 fw-normal text-muted">Proposal</span>
                            </div>
                            <div class="mt-2 small text-muted d-flex justify-content-between align-items-center">
                                <span>{{ $processStats['revision_needed'] ?? 0 }} Dosen • {{ $processStats['revision_submitted'] ?? 0 }} Diajukan</span>
                                <span class="text-warning fw-semibold fs-4"><i class="ti ti-arrow-right"></i></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Catatan Harian / Laporan Keuangan (LPJ) -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card glass-card border-0 shadow-sm overflow-hidden h-100 cursor-pointer" 
                         style="border-left: 4px solid #6366f1 !important; cursor: pointer; transition: all 0.2s ease;"
                         wire:click="openProcessModal('catatan_harian_keuangan')"
                         title="Klik untuk melihat data catatan harian & LPJ">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="subheader text-indigo fw-bold d-flex align-items-center">
                                    <i class="ti ti-receipt-2 me-1"></i>Catatan & LPJ
                                </div>
                                <div class="ms-auto">
                                    <span class="badge bg-indigo-lt">{{ $processStats['financial_completed'] ?? 0 }}/{{ $processStats['financial_total'] ?? 0 }} Disahkan</span>
                                </div>
                            </div>
                            <div class="h2 mb-1 fw-bold text-dark">
                                {{ $processStats['financial_with_notes'] ?? 0 }} <span class="fs-4 fw-normal text-muted">Ada Catatan</span>
                            </div>
                            <div class="mt-2 small text-muted d-flex justify-content-between align-items-center">
                                <span>{{ $processStats['financial_total'] ?? 0 }} Didanai</span>
                                <span class="text-indigo fw-semibold fs-4"><i class="ti ti-arrow-right"></i></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Laporan Akhir -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card glass-card border-0 shadow-sm overflow-hidden h-100 cursor-pointer" 
                         style="border-left: 4px solid #2fb344 !important; cursor: pointer; transition: all 0.2s ease;"
                         wire:click="openProcessModal('laporan_akhir')"
                         title="Klik untuk melihat data laporan akhir">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="subheader text-success fw-bold d-flex align-items-center">
                                    <i class="ti ti-file-certificate me-1"></i>Laporan Akhir
                                </div>
                                <div class="ms-auto">
                                    <span class="badge bg-success-lt">{{ $processStats['report_active_progress'] ?? 0 }}% Aktif</span>
                                </div>
                            </div>
                            <div class="h2 mb-1 fw-bold text-dark">
                                {{ $processStats['report_active_total'] ?? 0 }} <span class="fs-4 fw-normal text-muted">dari {{ $processStats['report_total'] ?? 0 }}</span>
                            </div>
                            <div class="mt-2 small text-muted d-flex justify-content-between align-items-center">
                                <span>{{ $processStats['report_submitted'] ?? 0 }} Masuk • {{ $processStats['report_draft'] ?? 0 }} Draf</span>
                                <span class="text-success fw-semibold fs-4"><i class="ti ti-arrow-right"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monitoring Evaluasi & Capaian (Review, Monev, IKU) -->
            <div class="row row-cards mb-4">
                <!-- Review Progress Details -->
                <div class="col-md-4">
                    <div class="card glass-card border-0 shadow-sm overflow-hidden h-100 cursor-pointer" 
                         style="border-left: 4px solid #f59f00 !important; cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease;"
                         role="button"
                         wire:click="openProcessModal('review')"
                         title="Klik untuk melihat rincian data review usulan"
                         onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 6px 16px rgba(0,0,0,0.1)'"
                         onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="subheader text-warning fw-bold d-flex align-items-center">
                                    <i class="ti ti-clipboard-check me-1"></i>Progress Review
                                </div>
                                <div class="ms-auto">
                                    <span class="badge bg-warning-lt">{{ $processStats['review_progress'] ?? 0 }}%</span>
                                </div>
                            </div>
                            <div class="progress progress-sm shadow-none bg-warning-lt">
                                <div class="progress-bar bg-warning" style="width: {{ $processStats['review_progress'] ?? 0 }}%"></div>
                            </div>
                            <div class="mt-2 small text-muted d-flex justify-content-between align-items-center">
                                <span>{{ $processStats['review_completed'] ?? 0 }} dari {{ $processStats['review_total'] ?? 0 }} proposal selesai</span>
                                <span class="text-warning fw-semibold"><i class="ti ti-arrow-right"></i></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monev Progress Details -->
                <div class="col-md-4">
                    <div class="card glass-card border-0 shadow-sm overflow-hidden h-100 cursor-pointer" 
                         style="border-left: 4px solid #00b8d4 !important; cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease;"
                         role="button"
                         wire:click="openProcessModal('monev')"
                         title="Klik untuk melihat rincian data monev proposal"
                         onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 6px 16px rgba(0,0,0,0.1)'"
                         onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="subheader text-info fw-bold d-flex align-items-center">
                                    <i class="ti ti-chart-dots me-1"></i>Progress Monev
                                </div>
                                <div class="ms-auto">
                                    <span class="badge bg-info-lt">{{ $processStats['monev_progress'] ?? 0 }}%</span>
                                </div>
                            </div>
                            <div class="progress progress-sm shadow-none bg-info-lt">
                                <div class="progress-bar bg-info" style="width: {{ $processStats['monev_progress'] ?? 0 }}%"></div>
                            </div>
                            <div class="mt-2 small text-muted d-flex justify-content-between align-items-center">
                                <span>{{ $processStats['monev_completed'] ?? 0 }} dari {{ $processStats['monev_total'] ?? 0 }} proposal selesai</span>
                                <span class="text-info fw-semibold"><i class="ti ti-arrow-right"></i></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- IKU Progress Details -->
                <div class="col-md-4">
                    <div class="card glass-card border-0 shadow-sm overflow-hidden h-100 cursor-pointer" 
                         style="border-left: 4px solid #206bc4 !important; cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease;"
                         role="button"
                         wire:click="openProcessModal('iku')"
                         title="Klik untuk melihat rincian capaian luaran (IKU)"
                         onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 6px 16px rgba(0,0,0,0.1)'"
                         onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="subheader text-primary fw-bold d-flex align-items-center">
                                    <i class="ti ti-award me-1"></i>Progress IKU (Luaran)
                                </div>
                                <div class="ms-auto">
                                    <span class="badge bg-primary-lt">{{ number_format($processStats['output_progress'] ?? 0, 1) }}%</span>
                                </div>
                            </div>
                            <div class="progress progress-sm shadow-none bg-primary-lt">
                                <div class="progress-bar bg-primary" style="width: {{ $processStats['output_progress'] ?? 0 }}%"></div>
                            </div>
                            <div class="mt-2 small text-muted d-flex justify-content-between align-items-center">
                                <span>{{ $processStats['output_achieved'] ?? 0 }} dari {{ $processStats['output_target'] ?? 0 }} target tercapai</span>
                                <span class="text-primary fw-semibold"><i class="ti ti-arrow-right"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3 row row-cards">
                <!-- Penelitian Terbaru -->
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                        <div class="card-header bg-transparent border-0 py-3 d-flex align-items-center">
                            <div class="avatar bg-primary-lt text-primary shadow-sm avatar-sm me-3 border-0">
                                <i class="ti ti-flask-2"></i>
                            </div>
                            <h3 class="card-title fw-bold mb-0">Penelitian Terbaru</h3>
                            <span class="badge bg-primary-lt ms-auto">{{ count($recentResearch) }} Data</span>
                        </div>
                        <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                            {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
                            <table class="table table-vcenter card-table table-hover table-borderless mb-0">
                                <thead class="bg-surface text-muted" style="position: sticky; top: 0; z-index: 10; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                    <tr>
                                        <th class="ps-4">Judul & Peneliti</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-end pe-4">Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentResearch as $research)
                                        <tr wire:key="res-{{ $research->id }}">
                                            <td class="ps-4">
                                                <a href="{{ route('research.proposal.show', $research->id) }}" class="text-reset fw-bold text-wrap lh-base text-decoration-none" title="{{ $research->title }}" wire:navigate>
                                                    {{ $research->title }}
                                                </a>
                                                <div class="small text-muted d-flex align-items-center mt-1">
                                                    <div class="avatar avatar-xs me-2 border-0 shadow-sm bg-primary-lt">
                                                        {{ $research->submitter?->initials() }}
                                                    </div>
                                                    {{ $research->submitter?->name }}
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @if(in_array($research->status->value, ['completed', 'approved']))
                                                    @if($research->latestFinalReport)
                                                        @php
                                                            $repStatus = $research->latestFinalReport->status;
                                                        @endphp
                                                        @if($repStatus === \App\Enums\ReportStatus::APPROVED)
                                                            <span class="badge bg-success text-white fw-bold px-2 py-1">
                                                                <i class="ti ti-check me-1"></i>Laporan Disetujui
                                                            </span>
                                                        @elseif($repStatus === \App\Enums\ReportStatus::APPROVED_BY_DEKAN)
                                                            <span class="badge bg-purple text-white fw-bold px-2 py-1">
                                                                <i class="ti ti-clock-check me-1"></i>Disetujui Dekan
                                                            </span>
                                                        @elseif($repStatus === \App\Enums\ReportStatus::SUBMITTED)
                                                            <span class="badge bg-primary text-white fw-bold px-2 py-1">
                                                                <i class="ti ti-send me-1"></i>Laporan Diajukan
                                                            </span>
                                                        @elseif($repStatus === \App\Enums\ReportStatus::REJECTED)
                                                            <span class="badge bg-danger text-white fw-bold px-2 py-1">
                                                                <i class="ti ti-alert-circle me-1"></i>Revisi Laporan
                                                            </span>
                                                        @elseif($repStatus === \App\Enums\ReportStatus::DRAFT)
                                                            <span class="badge bg-warning text-white fw-bold px-2 py-1">
                                                                <i class="ti ti-edit me-1"></i>Draf Laporan
                                                            </span>
                                                        @endif
                                                    @else
                                                        <span class="badge bg-azure-lt fw-bold px-2 py-1">
                                                            <span class="badge bg-azure me-1"></span>Pelaksanaan
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="badge bg-{{ $research->status->color() }}-lt fw-bold px-2 py-1">
                                                        <span class="badge bg-{{ $research->status->color() }} me-1"></span>
                                                        {{ $research->status->label() }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-end pe-4 text-muted small">
                                                {{ $research->updated_at->format('d/m/Y H:i') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="py-5 text-muted text-center">Belum ada penelitian</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- PKM Terbaru -->
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                        <div class="card-header bg-transparent border-0 py-3 d-flex align-items-center">
                            <div class="avatar bg-azure-lt text-azure shadow-sm avatar-sm me-3 border-0">
                                <i class="ti ti-users-group"></i>
                            </div>
                            <h3 class="card-title fw-bold mb-0">PKM Terbaru</h3>
                            <span class="badge bg-azure-lt ms-auto">{{ count($recentCommunityService) }} Data</span>
                        </div>
                        <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                            {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
                            <table class="table table-vcenter card-table table-hover table-borderless mb-0">
                                <thead class="bg-surface text-muted" style="position: sticky; top: 0; z-index: 10; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                    <tr>
                                        <th class="ps-4">Judul & Pengaju</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-end pe-4">Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentCommunityService as $communityService)
                                        <tr wire:key="pkm-{{ $communityService->id }}">
                                            <td class="ps-4">
                                                <a href="{{ route('community-service.proposal.show', $communityService->id) }}" class="text-reset fw-bold text-wrap lh-base text-decoration-none" title="{{ $communityService->title }}" wire:navigate>
                                                    {{ $communityService->title }}
                                                </a>
                                                <div class="small text-muted d-flex align-items-center mt-1">
                                                    <div class="avatar avatar-xs me-2 border-0 shadow-sm bg-azure-lt">
                                                        {{ $communityService->submitter?->initials() }}
                                                    </div>
                                                    {{ $communityService->submitter?->name }}
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @if(in_array($communityService->status->value, ['completed', 'approved']))
                                                    @if($communityService->latestFinalReport)
                                                        @php
                                                            $repStatus = $communityService->latestFinalReport->status;
                                                        @endphp
                                                        @if($repStatus === \App\Enums\ReportStatus::APPROVED)
                                                            <span class="badge bg-success text-white fw-bold px-2 py-1">
                                                                <i class="ti ti-check me-1"></i>Laporan Disetujui
                                                            </span>
                                                        @elseif($repStatus === \App\Enums\ReportStatus::APPROVED_BY_DEKAN)
                                                            <span class="badge bg-purple text-white fw-bold px-2 py-1">
                                                                <i class="ti ti-clock-check me-1"></i>Disetujui Dekan
                                                            </span>
                                                        @elseif($repStatus === \App\Enums\ReportStatus::SUBMITTED)
                                                            <span class="badge bg-primary text-white fw-bold px-2 py-1">
                                                                <i class="ti ti-send me-1"></i>Laporan Diajukan
                                                            </span>
                                                        @elseif($repStatus === \App\Enums\ReportStatus::REJECTED)
                                                            <span class="badge bg-danger text-white fw-bold px-2 py-1">
                                                                <i class="ti ti-alert-circle me-1"></i>Revisi Laporan
                                                            </span>
                                                        @elseif($repStatus === \App\Enums\ReportStatus::DRAFT)
                                                            <span class="badge bg-warning text-white fw-bold px-2 py-1">
                                                                <i class="ti ti-edit me-1"></i>Draf Laporan
                                                            </span>
                                                        @endif
                                                    @else
                                                        <span class="badge bg-azure-lt fw-bold px-2 py-1">
                                                            <span class="badge bg-azure me-1"></span>Pelaksanaan
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="badge bg-{{ $communityService->status->color() }}-lt fw-bold px-2 py-1">
                                                        <span class="badge bg-{{ $communityService->status->color() }} me-1"></span>
                                                        {{ $communityService->status->label() }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-end pe-4 text-muted small">
                                                {{ $communityService->updated_at->format('d/m/Y H:i') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="py-5 text-muted text-center">Belum ada PKM</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

    @include('livewire.dashboard.partials.process-details-modal')
</div>