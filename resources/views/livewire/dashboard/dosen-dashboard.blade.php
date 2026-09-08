<div>
    {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
    <div class="d-flex justify-content-end mb-4">
        <div class="d-flex align-items-center gap-2">
            <div class="dropdown">
                <a href="#" class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2"
                    data-bs-toggle="dropdown">
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

    <!-- Metrics Section -->
    <div class="row row-deck row-cards mb-4">
        <!-- SINTA Score Card -->
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm overflow-hidden h-100" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="subheader text-primary fw-bold">SINTA Score Overall</div>
                        <div class="ms-auto d-flex gap-1" style="position: relative; z-index: 2;">
                            @if(auth()->user()->identity?->sinta_id)
                                <button wire:click.prevent="syncSinta" wire:loading.attr="disabled"
                                    class="btn btn-icon btn-ghost-primary btn-sm rounded-circle"
                                    title="Sinkronkan Data SINTA">
                                    <i wire:loading.remove class="ti ti-refresh"></i>
                                    <div wire:loading class="spinner-border spinner-border-sm" role="status"></div>
                                </button>
                            @endif
                        </div>
                    </div>
                    <a href="{{ auth()->user()->identity?->sinta_id ? auth()->user()->identity->getSintaUrl() : 'https://sinta.kemdikbud.go.id/authors' }}"
                        target="_blank" class="text-decoration-none d-block">
                        <div class="h1 mb-1 fw-bold text-primary">
                            {{ number_format(auth()->user()->identity?->sinta_score_v3_overall ?? 0, 0, ',', '.') }}
                        </div>
                        <div class="text-muted small">Algoritma SINTA v3 Overall Score</div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Scopus H-Index -->
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm overflow-hidden h-100" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="subheader text-green fw-bold">Scopus H-Index</div>
                        <div class="ms-auto">
                            @php
                                $scopusUrl = auth()->user()->identity?->getScopusUrl() ?? "https://www.scopus.com/results/authorNamesList.uri?st1=" . urlencode(explode(' ', auth()->user()->name)[0] ?? '') . "&st2=" . urlencode(explode(' ', auth()->user()->name)[1] ?? '');
                            @endphp
                            <div class="text-green bg-green-lt rounded-circle p-2 d-flex align-items-center justify-content-center"
                                style="width: 32px; height: 32px;">
                                <i class="ti ti-brand-chrome fs-3"></i>
                            </div>
                        </div>
                    </div>
                    <a href="{{ $scopusUrl }}" target="_blank" class="text-decoration-none d-block">
                        <div class="h1 mb-1 fw-bold text-green">{{ auth()->user()->identity?->scopus_h_index ?? '-' }}
                        </div>
                        <div class="text-muted small">Citations per Publication Ratio</div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Google Scholar H-Index -->
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm overflow-hidden h-100" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="subheader text-yellow fw-bold">Google Scholar H-Index</div>
                        <div class="ms-auto">
                            @php
                                $gsUrl = auth()->user()->identity?->getGoogleScholarUrl() ?? "https://scholar.google.com/scholar?q=" . urlencode(auth()->user()->name);
                            @endphp
                            <div class="text-yellow bg-yellow-lt rounded-circle p-2 d-flex align-items-center justify-content-center"
                                style="width: 32px; height: 32px;">
                                <i class="ti ti-brand-google fs-3"></i>
                            </div>
                        </div>
                    </div>
                    <a href="{{ $gsUrl }}" target="_blank" class="text-decoration-none d-block">
                        <div class="h1 mb-1 fw-bold text-yellow">{{ auth()->user()->identity?->gs_h_index ?? '-' }}</div>
                        <div class="text-muted small">Visualized by Scholar Library</div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Web of Science (WoS) H-Index -->
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm overflow-hidden h-100" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="subheader text-purple fw-bold">Web of Science H-Index</div>
                        <div class="ms-auto">
                            @php
                                $wosUrl = auth()->user()->identity?->wos_id ? "https://www.webofscience.com/wos/author/record/" . auth()->user()->identity->wos_id : "https://www.webofscience.com/wos/author/search?search_mode=AuthorResearcherId&researcher_id=" . urlencode(auth()->user()->name);
                            @endphp
                            <div class="text-purple bg-purple-lt rounded-circle p-2 d-flex align-items-center justify-content-center"
                                style="width: 32px; height: 32px;">
                                <i class="ti ti-flask fs-3"></i>
                            </div>
                        </div>
                    </div>
                    <a href="{{ $wosUrl }}" target="_blank" class="text-decoration-none d-block">
                        <div class="h1 mb-1 fw-bold text-purple">{{ auth()->user()->identity?->wos_h_index ?? '-' }}</div>
                        <div class="text-muted small">Clarivate Analytics WoS</div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Productivity Details -->
    <div class="row row-deck row-cards mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-primary-lt text-primary avatar border-0 shadow-sm"><i
                                    class="ti ti-flask"></i></span>
                        </div>
                        <div class="col">
                            <div class="fw-bold">Penelitian</div>
                            <div class="text-muted small">{{ $stats['my_research'] }} Total</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-azure-lt text-azure avatar border-0 shadow-sm"><i
                                    class="ti ti-users"></i></span>
                        </div>
                        <div class="col">
                            <div class="fw-bold">Pengabdian</div>
                            <div class="text-muted small">{{ $stats['my_community_service'] }} Total</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-green-lt text-green avatar border-0 shadow-sm"><i
                                    class="ti ti-user-plus"></i></span>
                        </div>
                        <div class="col">
                            <div class="fw-bold">Penelitian Pending</div>
                            <div class="text-muted small">{{ $stats['research_pending'] }} Menunggu Persetujuan</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-azure-lt text-azure avatar border-0 shadow-sm"><i
                                    class="ti ti-check"></i></span>
                        </div>
                        <div class="col">
                            <div class="fw-bold">Penelitian Disetujui</div>
                            <div class="text-muted small">{{ $stats['research_approved'] }} Disetujui</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-yellow-lt text-yellow avatar border-0 shadow-sm"><i
                                    class="ti ti-user-plus"></i></span>
                        </div>
                        <div class="col">
                            <div class="fw-bold">PKM Pending</div>
                            <div class="text-muted small">{{ $stats['community_service_pending'] }} Menunggu Persetujuan</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-teal-lt text-teal avatar border-0 shadow-sm"><i
                                    class="ti ti-check"></i></span>
                        </div>
                        <div class="col">
                            <div class="fw-bold">PKM Disetujui</div>
                            <div class="text-muted small">{{ $stats['community_service_approved'] }} Disetujui</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-purple-lt text-purple avatar border-0 shadow-sm"><i
                                    class="ti ti-chart-dots"></i></span>
                        </div>
                        <div class="col">
                            <div class="fw-bold">Member</div>
                            <div class="text-muted small">
                                {{ $stats['research_as_member'] + $stats['community_service_as_member'] }} Kolaborasi
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Section -->
    @if(!empty($chartData['labels']))
        <div class="row row-cards mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                    <div class="card-header bg-transparent border-0 py-3 d-flex align-items-center">
                        <div class="avatar bg-primary-lt text-primary shadow-sm avatar-sm me-3 border-0">
                            <i class="ti ti-chart-line"></i>
                        </div>
                        <h3 class="card-title fw-bold mb-0">Tren Penelitian & Pengabdian</h3>
                        <div class="ms-auto d-flex gap-3 flex-wrap">
                            <span class="d-flex align-items-center gap-1 small text-muted">
                                <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#206bc4;"></span>
                                Penelitian (Ketua)
                            </span>
                            <span class="d-flex align-items-center gap-1 small text-muted">
                                <span class="d-inline-block border border-2 rounded-circle" style="width:8px;height:8px;background:transparent;border-color:#7eb8e0 !important;"></span>
                                Penelitian (Anggota)
                            </span>
                            <span class="d-flex align-items-center gap-1 small text-muted">
                                <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#f59f00;"></span>
                                Pengabdian (Ketua)
                            </span>
                            <span class="d-flex align-items-center gap-1 small text-muted">
                                <span class="d-inline-block border border-2 rounded-circle" style="width:8px;height:8px;background:transparent;border-color:#fbd38d !important;"></span>
                                Pengabdian (Anggota)
                            </span>
                        </div>
                    </div>
                    {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
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
                                                 legend: { display: false },
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
                                                 x: {
                                                     grid: { display: false },
                                                     ticks: { color: '#9ca3af', font: { size: 10 } }
                                                 },
                                                 y: {
                                                     grid: { color: 'rgba(229,231,235,0.5)' },
                                                     ticks: { color: '#9ca3af', font: { size: 10 }, stepSize: 1 }
                                                 },
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

    {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
    <!-- Alur Proses Tahapan Penelitian & Pengabdian (Data Riil) -->
    <div class="row row-cards mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h3 class="card-title fw-bold text-dark d-flex align-items-center gap-2 mb-0">
                    <i class="ti ti-git-fork text-primary"></i>
                    Alur Proses Penelitian & Pengabdian Saya
                </h3>
                <span class="badge bg-blue-lt">Klik kartu untuk rincian data usulan</span>
            </div>
        </div>

        <!-- 1. Tahap Usulan -->
        <div class="col-sm-6 col-lg-3">
            <div class="card glass-card border-0 shadow-sm overflow-hidden h-100 cursor-pointer" 
                 style="border-top: 4px solid #206bc4 !important; transition: transform 0.15s ease, box-shadow 0.15s ease;"
                 role="button"
                 wire:click="openProcessModal('usulan')"
                 onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 6px 16px rgba(0,0,0,0.1)'"
                 onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center mb-2">
                        <span class="avatar avatar-sm bg-primary-lt text-primary rounded shadow-none me-2">
                            <i class="ti ti-file-text fs-2"></i>
                        </span>
                        <div>
                            <div class="subheader text-primary fw-bold mb-0">Usulan</div>
                            <div class="text-muted small">Tahap Pengajuan</div>
                        </div>
                        <div class="ms-auto text-end">
                            <span class="badge bg-primary-lt fw-bold">{{ $processStats['usulan_approved_progress'] ?? 0 }}% Disetujui</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mb-2">
                        <span class="h1 mb-0 fw-bold text-dark">{{ $processStats['usulan_total'] ?? 0 }}</span>
                        <span class="text-muted small">Total Usulan</span>
                    </div>
                    <div class="progress progress-sm shadow-none bg-primary-lt mb-2">
                        <div class="progress-bar bg-success" style="width: {{ $processStats['usulan_approved_progress'] ?? 0 }}%" title="Disetujui: {{ $processStats['usulan_approved'] ?? 0 }}"></div>
                        <div class="progress-bar bg-warning" style="width: {{ $processStats['usulan_submitted_progress'] ?? 0 }}%" title="Diajukan: {{ $processStats['usulan_submitted'] ?? 0 }}"></div>
                        <div class="progress-bar bg-danger" style="width: {{ $processStats['usulan_rejected_progress'] ?? 0 }}%" title="Ditolak: {{ $processStats['usulan_rejected'] ?? 0 }}"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span>{{ $processStats['usulan_approved'] ?? 0 }} Disetujui</span>
                        <span>{{ $processStats['usulan_submitted'] ?? 0 }} Diajukan</span>
                        <span>{{ $processStats['usulan_draft'] ?? 0 }} Draf</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Tahap Perbaikan Usulan -->
        <div class="col-sm-6 col-lg-3">
            <div class="card glass-card border-0 shadow-sm overflow-hidden h-100 cursor-pointer" 
                 style="border-top: 4px solid #f59f00 !important; transition: transform 0.15s ease, box-shadow 0.15s ease;"
                 role="button"
                 wire:click="openProcessModal('revision')"
                 onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 6px 16px rgba(0,0,0,0.1)'"
                 onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center mb-2">
                        <span class="avatar avatar-sm bg-warning-lt text-warning rounded shadow-none me-2">
                            <i class="ti ti-edit fs-2"></i>
                        </span>
                        <div>
                            <div class="subheader text-warning fw-bold mb-0">Perbaikan Usulan</div>
                            <div class="text-muted small">Revisi & Resubmisi</div>
                        </div>
                        <div class="ms-auto text-end">
                            <span class="badge bg-warning-lt fw-bold">{{ $processStats['revision_progress'] ?? 0 }}% Ditindak</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mb-2">
                        <span class="h1 mb-0 fw-bold text-dark">{{ $processStats['revision_total'] ?? 0 }}</span>
                        <span class="text-muted small">Usulan Revisi</span>
                    </div>
                    <div class="progress progress-sm shadow-none bg-warning-lt mb-2">
                        <div class="progress-bar bg-success" style="width: {{ $processStats['revision_progress'] ?? 0 }}%" title="Selesai Diperbaiki: {{ $processStats['revision_resubmitted'] ?? 0 }}"></div>
                        <div class="progress-bar bg-warning" style="width: {{ 100 - ($processStats['revision_progress'] ?? 0) }}%" title="Menunggu Revisi: {{ $processStats['revision_waiting'] ?? 0 }}"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span class="text-danger fw-bold">{{ $processStats['revision_waiting'] ?? 0 }} Menunggu</span>
                        <span>{{ $processStats['revision_draft'] ?? 0 }} Draf</span>
                        <span class="text-success fw-bold">{{ $processStats['revision_resubmitted'] ?? 0 }} Selesai</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Catatan Harian / Laporan Keuangan -->
        <div class="col-sm-6 col-lg-3">
            <div class="card glass-card border-0 shadow-sm overflow-hidden h-100 cursor-pointer" 
                 style="border-top: 4px solid #6366f1 !important; transition: transform 0.15s ease, box-shadow 0.15s ease;"
                 role="button"
                 wire:click="openProcessModal('financial')"
                 onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 6px 16px rgba(0,0,0,0.1)'"
                 onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center mb-2">
                        <span class="avatar avatar-sm bg-indigo-lt text-indigo rounded shadow-none me-2">
                            <i class="ti ti-receipt-2 fs-2"></i>
                        </span>
                        <div>
                            <div class="subheader text-indigo fw-bold mb-0">Catatan & Keuangan</div>
                            <div class="text-muted small">Logbook & LPJ</div>
                        </div>
                        <div class="ms-auto text-end">
                            <span class="badge bg-indigo-lt fw-bold">{{ $processStats['financial_progress'] ?? 0 }}% Sah</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mb-2">
                        <span class="h1 mb-0 fw-bold text-dark">{{ $processStats['financial_total'] ?? 0 }}</span>
                        <span class="text-muted small">Proposal Berjalan</span>
                    </div>
                    <div class="progress progress-sm shadow-none bg-indigo-lt mb-2">
                        <div class="progress-bar bg-success" style="width: {{ $processStats['financial_progress'] ?? 0 }}%" title="Disahkan: {{ $processStats['financial_completed'] ?? 0 }}"></div>
                        <div class="progress-bar bg-primary" style="width: {{ $processStats['financial_pending_progress'] ?? 0 }}%" title="Menunggu Sah: {{ $processStats['financial_pending'] ?? 0 }}"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span>{{ $processStats['financial_with_notes'] ?? 0 }} Ada Catatan</span>
                        <span class="text-primary fw-bold">{{ $processStats['financial_pending'] ?? 0 }} Menunggu</span>
                        <span class="text-success fw-bold">{{ $processStats['financial_completed'] ?? 0 }} Sah</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Laporan Akhir -->
        <div class="col-sm-6 col-lg-3">
            <div class="card glass-card border-0 shadow-sm overflow-hidden h-100 cursor-pointer" 
                 style="border-top: 4px solid #2fb344 !important; transition: transform 0.15s ease, box-shadow 0.15s ease;"
                 role="button"
                 wire:click="openProcessModal('final_report')"
                 onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 6px 16px rgba(0,0,0,0.1)'"
                 onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center mb-2">
                        <span class="avatar avatar-sm bg-success-lt text-success rounded shadow-none me-2">
                            <i class="ti ti-certificate fs-2"></i>
                        </span>
                        <div>
                            <div class="subheader text-success fw-bold mb-0">Laporan Akhir</div>
                            <div class="text-muted small">Pelaporan & Validasi</div>
                        </div>
                        <div class="ms-auto text-end">
                            <span class="badge bg-success-lt fw-bold">{{ $processStats['report_active_progress'] ?? $processStats['report_progress'] ?? 0 }}% Berproses</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mb-2">
                        <span class="h1 mb-0 fw-bold text-dark">{{ $processStats['report_active_total'] ?? 0 }}<span class="text-muted fs-4">/{{ $processStats['report_total'] ?? 0 }}</span></span>
                        <span class="text-muted small">Aktif Lapor</span>
                    </div>
                    <div class="progress progress-sm shadow-none bg-success-lt mb-2">
                        <div class="progress-bar bg-success" style="width: {{ $processStats['report_progress'] ?? 0 }}%" title="Diajukan/Disetujui: {{ ($processStats['report_submitted'] ?? 0) + ($processStats['report_approved'] ?? 0) + ($processStats['report_approved_dekan'] ?? 0) }}"></div>
                        <div class="progress-bar bg-warning" style="width: {{ $processStats['report_draft_progress'] ?? 0 }}%" title="Draf: {{ $processStats['report_draft'] ?? 0 }}"></div>
                        <div class="progress-bar bg-danger" style="width: {{ $processStats['report_revision_progress'] ?? 0 }}%" title="Revisi: {{ $processStats['report_revision'] ?? 0 }}"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span class="text-primary fw-bold">{{ $processStats['report_submitted'] ?? 0 }} Diajukan</span>
                        <span>{{ $processStats['report_draft'] ?? 0 }} Draf</span>
                        <span class="text-success fw-bold">{{ $processStats['report_approved'] ?? 0 }} Disetujui</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tables Section -->
    <div class="row row-cards mt-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-transparent border-0 py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="avatar bg-primary-lt text-primary shadow-sm avatar-sm me-3 border-0">
                            <i class="ti ti-flask-2"></i>
                        </div>
                        <h3 class="card-title fw-bold mb-0">Penelitian Terbaru</h3>
                        <span class="badge bg-primary-lt ms-2">{{ count($recentResearch) }} Data</span>
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                    <table class="table table-vcenter card-table table-hover table-borderless">
                        <thead class="bg-surface text-muted" style="position: sticky; top: 0; z-index: 10; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
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
                <div class="card-header bg-transparent border-0 py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="avatar bg-azure-lt text-azure shadow-sm avatar-sm me-3 border-0">
                            <i class="ti ti-users-group"></i>
                        </div>
                        <h3 class="card-title fw-bold mb-0">PKM Terbaru</h3>
                        <span class="badge bg-azure-lt ms-2">{{ count($recentCommunityService) }} Data</span>
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                    <table class="table table-vcenter card-table table-hover table-borderless">
                        <thead class="bg-surface text-muted" style="position: sticky; top: 0; z-index: 10; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
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
                                    <td colspan="3" class="text-center py-5 text-muted">Belum ada PKM</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Modal Form Update Metrik -->
    <div class="modal modal-blur fade @if($showEditMetricsModal) show @endif" id="modal-edit-metrics" tabindex="-1"
        role="dialog" aria-hidden="true" style="@if($showEditMetricsModal) display: block; @endif">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content glass-card">
                <form wire:submit="saveMetrics">
                    <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold">
                            <i class="ti ti-pencil me-2 text-primary"></i>
                            Sesuaikan Metrik Publikasi
                        </h5>
                        <button type="button" class="btn-close" wire:click="$set('showEditMetricsModal', false)"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info bg-info-lt mb-4 border-0 shadow-sm">
                            <div class="d-flex">
                                <div><i class="ti ti-info-circle me-3 fs-2 text-info"></i></div>
                                <div>
                                    <h4 class="alert-title mb-1">Informasi Sinkronisasi</h4>
                                    <div class="text-secondary">Anda dapat memperbarui skor metrik secara manual untuk
                                        penyesuaian/kalibrasi dengan laporan SINTA yang diunggah oleh LPPM.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-bold">SINTA Score Overall</label>
                                <div class="input-group input-group-flat">
                                    <span class="input-group-text bg-transparent text-primary"><i
                                            class="ti ti-star"></i></span>
                                    <input type="number" step="0.01" class="form-control"
                                        wire:model="sinta_score_v3_overall">
                                </div>
                                @error('sinta_score_v3_overall') <div class="text-danger small mt-1">{{ $message }}
                                </div> @enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-bold">Scopus H-Index</label>
                                <div class="input-group input-group-flat">
                                    <span class="input-group-text bg-transparent text-green"><i
                                            class="ti ti-chart-bar"></i></span>
                                    <input type="number" class="form-control" wire:model="scopus_h_index">
                                </div>
                                @error('scopus_h_index') <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-bold">Google Scholar H-Index</label>
                                <div class="input-group input-group-flat">
                                    <span class="input-group-text bg-transparent text-yellow"><i
                                            class="ti ti-book"></i></span>
                                    <input type="number" class="form-control" wire:model="gs_h_index">
                                </div>
                                @error('gs_h_index') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-bold">Web Of Science (WoS)</label>
                                <div class="input-group input-group-flat">
                                    <span class="input-group-text bg-transparent text-purple"><i
                                            class="ti ti-flask"></i></span>
                                    <input type="number" class="form-control" wire:model="wos_h_index"
                                        placeholder="H-Index">
                                </div>
                                @error('wos_h_index') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary"
                            wire:click="$set('showEditMetricsModal', false)">
                            <i class="ti ti-x me-2"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-primary shadow-sm" wire:loading.attr="disabled"
                            wire:target="saveMetrics">
                            <span wire:loading.remove wire:target="saveMetrics">
                                <i class="ti ti-device-floppy me-2"></i> Simpan Metrik
                            </span>
                            <span wire:loading wire:target="saveMetrics">
                                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @if($showEditMetricsModal)
        <div class="modal-backdrop fade show"></div>
    @endif

    {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
    @include('livewire.dashboard.partials.process-details-modal')
</div>