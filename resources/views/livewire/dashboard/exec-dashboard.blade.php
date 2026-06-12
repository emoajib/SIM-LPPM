<div>
    {{-- ===================================================================
         PAGE HEADER — Filter Dashboard Eksekutif (Rektor / Dekan)
         Vetted by AI - Manual Review Required by Senior Engineer/Manager
         =================================================================== --}}
    {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        {{-- Tahun Dropdown --}}
        <div class="dropdown">
            <a href="#"
                class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2"
                data-bs-toggle="dropdown" aria-expanded="false">
                <i class="ti ti-calendar-event"></i>
                <span>Tahun: {{ $selectedYear }}</span>
            </a>
            <div class="dropdown-menu">
                @foreach ($availableYears as $year)
                    <a href="#"
                        class="dropdown-item {{ $selectedYear == $year ? 'active' : '' }}"
                        wire:click.preserve-scroll="$set('selectedYear', {{ $year }})">
                        {{ $year }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Semester Dropdown --}}
        <div class="dropdown">
            <a href="#"
                class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2"
                data-bs-toggle="dropdown" aria-expanded="false">
                <i class="ti ti-calendar-stats"></i>
                <span>Semester: {{ $selectedSemester === 'all' ? 'Semua' : ($selectedSemester === 'ganjil' ? 'Ganjil' : 'Genap') }}</span>
            </a>
            <div class="dropdown-menu">
                <a href="#"
                    class="dropdown-item {{ $selectedSemester === 'all' ? 'active' : '' }}"
                    wire:click.preserve-scroll="$set('selectedSemester', 'all')">
                    Semua
                </a>
                <a href="#"
                    class="dropdown-item {{ $selectedSemester === 'ganjil' ? 'active' : '' }}"
                    wire:click.preserve-scroll="$set('selectedSemester', 'ganjil')">
                    Ganjil
                </a>
                <a href="#"
                    class="dropdown-item {{ $selectedSemester === 'genap' ? 'active' : '' }}"
                    wire:click.preserve-scroll="$set('selectedSemester', 'genap')">
                    Genap
                </a>
            </div>
        </div>

        {{-- Status Dropdown --}}
        <div class="dropdown">
            <a href="#"
                class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2"
                data-bs-toggle="dropdown" aria-expanded="false">
                <i class="ti ti-filter"></i>
                <span>Status: {{ $availableStatuses[$selectedStatus] ?? 'Semua Status' }}</span>
            </a>
            <div class="dropdown-menu" style="max-height: 280px; overflow-y: auto;">
                @foreach ($availableStatuses as $value => $label)
                    <a href="#"
                        class="dropdown-item {{ $selectedStatus === $value ? 'active' : '' }}"
                        wire:click.preserve-scroll="$set('selectedStatus', '{{ $value }}')">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Filter Fakultas & Prodi (hanya untuk Rektor) --}}
        @if(! $this->isDekanRestricted() && ! $this->isKaprodiRestricted())
            {{-- Filter Fakultas --}}
            <div class="dropdown">
                <a href="#"
                    class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ti ti-building"></i>
                    <span>Fakultas: {{ $selectedFaculty !== 'all' ? ($availableFaculties[$selectedFaculty] ?? 'Fakultas') : 'Semua Fakultas' }}</span>
                </a>
                <div class="dropdown-menu" style="max-height: 280px; overflow-y: auto;">
                    @foreach ($availableFaculties as $value => $label)
                        <a href="#"
                            class="dropdown-item {{ $selectedFaculty == $value ? 'active' : '' }}"
                            wire:click.preserve-scroll="$set('selectedFaculty', '{{ $value }}')">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Filter Prodi --}}
            <div class="dropdown">
                <a href="#"
                    class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ti ti-book"></i>
                    <span>Prodi: {{ $selectedProdi !== 'all' ? ($availableProdis[$selectedProdi] ?? 'Prodi') : 'Semua Prodi' }}</span>
                </a>
                <div class="dropdown-menu" style="max-height: 280px; overflow-y: auto;">
                    @foreach ($availableProdis as $value => $label)
                        <a href="#"
                            class="dropdown-item {{ $selectedProdi == $value ? 'active' : '' }}"
                            wire:click.preserve-scroll="$set('selectedProdi', '{{ $value }}')">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Filter Skema Penelitian --}}
        <div class="dropdown">
            <a href="#"
                class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2"
                data-bs-toggle="dropdown" aria-expanded="false">
                <i class="ti ti-flask"></i>
                <span>Skema Penelitian: {{ $selectedResearchScheme !== 'all' ? ($availableResearchSchemes[$selectedResearchScheme] ?? 'Skema') : 'Semua Skema' }}</span>
            </a>
            <div class="dropdown-menu" style="max-height: 280px; overflow-y: auto;">
                @foreach ($availableResearchSchemes as $value => $label)
                    <a href="#"
                        class="dropdown-item {{ $selectedResearchScheme == $value ? 'active' : '' }}"
                        wire:click.preserve-scroll="$set('selectedResearchScheme', '{{ $value }}')">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Filter Skema PKM --}}
        <div class="dropdown">
            <a href="#"
                class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2"
                data-bs-toggle="dropdown" aria-expanded="false">
                <i class="ti ti-users-group"></i>
                <span>Skema PKM: {{ $selectedCommunityServiceScheme !== 'all' ? ($availableCommunityServiceSchemes[$selectedCommunityServiceScheme] ?? 'Skema') : 'Semua Skema' }}</span>
            </a>
            <div class="dropdown-menu dropdown-menu-end" style="max-height: 280px; overflow-y: auto;">
                @foreach ($availableCommunityServiceSchemes as $value => $label)
                    <a href="#"
                        class="dropdown-item {{ $selectedCommunityServiceScheme == $value ? 'active' : '' }}"
                        wire:click.preserve-scroll="$set('selectedCommunityServiceScheme', '{{ $value }}')">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Reset Filter Button --}}
        @if ($this->activeFilterCount > 0)
            <button class="btn btn-ghost-danger d-flex align-items-center gap-1 shadow-sm"
                type="button"
                wire:click="resetFilters"
                title="Reset semua filter">
                <i class="ti ti-x"></i>
                <span>Reset</span>
            </button>
        @endif
    </div>

    <!-- Executive KPI Cards -->
    <div class="row row-deck row-cards mb-4">
        <div class="col-sm-6 col-lg-3">
            <x-dashboard.kpi-widget 
                title="Total Penelitian" 
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
            <x-dashboard.kpi-widget 
                title="Total Anggaran" 
                value="Rp {{ number_format($totalBudget, 0, ',', '.') }}" 
                subtitle="Penelitian: Rp {{ number_format($stats['research_budget'] ?? 0, 0, ',', '.') }} • PKM: Rp {{ number_format($stats['pkm_budget'] ?? 0, 0, ',', '.') }}" 
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
    
    {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
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
                             initStarted: false,
                             renderTrendChart(data, isReinit = false) {
                                 this.initStarted = true;
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

    <!-- Process Monitoring (Progress Styles) -->
    <div class="row row-cards mb-4">
        <!-- Review Progress Details -->
        <div class="col-md-4">
            <div class="card glass-card border-0 shadow-sm overflow-hidden" style="border-left: 4px solid #f59f00 !important;">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="subheader text-warning fw-bold">Progress Review</div>
                        <div class="ms-auto">
                            <span class="badge bg-warning-lt">{{ $processStats['review_progress'] ?? 0 }}%</span>
                        </div>
                    </div>
                    <div class="progress progress-sm shadow-none bg-warning-lt">
                        <div class="progress-bar bg-warning" style="width: {{ $processStats['review_progress'] ?? 0 }}%"></div>
                    </div>
                    <div class="mt-2 small text-muted">
                        {{ $processStats['review_completed'] ?? 0 }} dari {{ $processStats['review_total'] ?? 0 }} proposal selesai direview
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
                            <span class="badge bg-info-lt">{{ $processStats['monev_progress'] ?? 0 }}%</span>
                        </div>
                    </div>
                    <div class="progress progress-sm shadow-none bg-info-lt">
                        <div class="progress-bar bg-info" style="width: {{ $processStats['monev_progress'] ?? 0 }}%"></div>
                    </div>
                    <div class="mt-2 small text-muted">
                        {{ $processStats['monev_completed'] ?? 0 }} dari {{ $processStats['monev_total'] ?? 0 }} proposal selesai dimonitoring
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
                            <span class="badge bg-primary-lt">{{ number_format($processStats['output_progress'] ?? 0, 1) }}%</span>
                        </div>
                    </div>
                    <div class="progress progress-sm shadow-none bg-primary-lt">
                        <div class="progress-bar bg-primary" style="width: {{ $processStats['output_progress'] ?? 0 }}%"></div>
                    </div>
                    <div class="mt-2 small text-muted">
                        {{ $processStats['output_achieved'] ?? 0 }} dari {{ $processStats['output_target'] ?? 0 }} target luaran tercapai
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Periodic Summary Split Tables -->
    <div class="row row-cards mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-transparent border-0 py-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar bg-primary-lt text-primary shadow-sm avatar-sm me-3 border-0">
                            <i class="ti ti-flask-2"></i>
                        </div>
                        <h3 class="card-title fw-bold mb-0">Ringkasan Penelitian per Periode</h3>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover table-borderless">
                        <thead class="bg-transparent text-muted">
                            <tr>
                                <th class="ps-4">Periode</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Approved</th>
                                <th class="text-end pe-4">Success Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($periodicSummary as $item)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold">{{ $item['year'] }}/{{ $item['year'] + 1 }}</div>
                                        <div class="text-muted small">
                                            @if($item['semester'] === null)
                                                (tanpa semester)
                                            @else
                                                Semester {{ ucfirst($item['semester']) }}
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center fw-bold">{{ $item['research_total'] }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-green-lt p-1 px-2 fw-normal">{{ $item['research_approved'] }}</span>
                                    </td>
                                    <td class="pe-4">
                                        @php
                                            $rate = $item['research_total'] > 0 ? round(($item['research_approved'] / $item['research_total']) * 100, 1) : 0;
                                            $color = $rate >= 75 ? 'green' : ($rate >= 50 ? 'azure' : 'orange');
                                        @endphp
                                        <div class="d-flex align-items-center justify-content-end gap-2">
                                            <div class="progress progress-xs w-50">
                                                <div class="progress-bar bg-{{ $color }}" style="width: {{ $rate }}%"></div>
                                            </div>
                                            <span class="fw-bold text-{{ $color }}">{{ $rate }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">Tidak ada data penelitian</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-transparent border-0 py-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar bg-azure-lt text-azure shadow-sm avatar-sm me-3 border-0">
                            <i class="ti ti-users-group"></i>
                        </div>
                        <h3 class="card-title fw-bold mb-0">Ringkasan PKM per Periode</h3>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover table-borderless">
                        <thead class="bg-transparent text-muted">
                            <tr>
                                <th class="ps-4">Periode</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Approved</th>
                                <th class="text-end pe-4">Success Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($periodicSummary as $item)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold">{{ $item['year'] }}/{{ $item['year'] + 1 }}</div>
                                        <div class="text-muted small">
                                            @if($item['semester'] === null)
                                                (tanpa semester)
                                            @else
                                                Semester {{ ucfirst($item['semester']) }}
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center fw-bold">{{ $item['pkm_total'] }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-purple-lt p-1 px-2 fw-normal">{{ $item['pkm_approved'] }}</span>
                                    </td>
                                    <td class="pe-4">
                                        @php
                                            $rate = $item['pkm_total'] > 0 ? round(($item['pkm_approved'] / $item['pkm_total']) * 100, 1) : 0;
                                            $color = $rate >= 75 ? 'green' : ($rate >= 50 ? 'azure' : 'orange');
                                        @endphp
                                        <div class="d-flex align-items-center justify-content-end gap-2">
                                            <div class="progress progress-xs w-50">
                                                <div class="progress-bar bg-{{ $color }}" style="width: {{ $rate }}%"></div>
                                            </div>
                                            <span class="fw-bold text-{{ $color }}">{{ $rate }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">Tidak ada data PKM</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Proposals -->
    <div class="row row-cards">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-transparent border-0 py-3 d-flex align-items-center">
                    <div class="avatar bg-primary-lt text-primary shadow-sm avatar-sm me-3 border-0">
                        <i class="ti ti-flask-2"></i>
                    </div>
                    <h3 class="card-title fw-bold mb-0">Penelitian Terbaru</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover table-borderless">
                        <thead class="bg-transparent text-muted">
                            <tr>
                                <th class="ps-4">Judul & Peneliti</th>
                                <th class="text-center">Status</th>
                                <th class="text-end pe-4">Tanggal</th>
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
                                            <div class="avatar avatar-xs me-2 border-0 shadow-sm bg-primary-lt">
                                                {{ $research->submitter?->initials() }}
                                            </div>
                                            {{ $research->submitter?->name }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $research->status->color() }}-lt fw-bold px-2 py-1">
                                            <span class="badge bg-{{ $research->status->color() }} me-1"></span>
                                            {{ $research->status->label() }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-4 text-muted small">
                                        {{ $research->created_at->format('d/m/Y') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">Belum ada penelitian</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-transparent border-0 py-3 d-flex align-items-center">
                    <div class="avatar bg-azure-lt text-azure shadow-sm avatar-sm me-3 border-0">
                        <i class="ti ti-users-group"></i>
                    </div>
                    <h3 class="card-title fw-bold mb-0">PKM Terbaru</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover table-borderless">
                        <thead class="bg-transparent text-muted">
                            <tr>
                                <th class="ps-4">Judul & Pengaju</th>
                                <th class="text-center">Status</th>
                                <th class="text-end pe-4">Tanggal</th>
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
                                            <div class="avatar avatar-xs me-2 border-0 shadow-sm bg-azure-lt">
                                                {{ $communityService->submitter?->initials() }}
                                            </div>
                                            {{ $communityService->submitter?->name }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $communityService->status->color() }}-lt fw-bold px-2 py-1">
                                            <span class="badge bg-{{ $communityService->status->color() }} me-1"></span>
                                            {{ $communityService->status->label() }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-4 text-muted small">
                                        {{ $communityService->created_at->format('d/m/Y') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">Belum ada PKM</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            </div>
        </div>
    </div>
</div>