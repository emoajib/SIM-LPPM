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
                                Usulan (Ketua)
                            </span>
                            <span class="d-flex align-items-center gap-1 small text-muted">
                                <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#2fb344;"></span>
                                Didanai (Ketua)
                            </span>
                            <span class="d-flex align-items-center gap-1 small text-muted">
                                <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#f59f00;"></span>
                                Usulan (Anggota)
                            </span>
                            <span class="d-flex align-items-center gap-1 small text-muted">
                                <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:#d6336c;"></span>
                                Didanai (Anggota)
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

    <!-- Tables Section -->
    <div class="row row-cards mt-4">
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
</div>