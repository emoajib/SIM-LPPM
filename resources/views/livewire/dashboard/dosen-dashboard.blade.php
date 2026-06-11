{{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
<div>
    <!-- Year Filter Bar -->
    <div class="d-flex justify-content-end mb-3">
        <div class="dropdown">
            <a href="#" class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2"
                data-bs-toggle="dropdown">
                <i class="ti ti-calendar-event fs-2"></i>
                <span>Tahun Pelaksanaan: {{ $selectedYear }}</span>
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

    <!-- Main Grid Content -->
    <div class="dash-grid pt-0">
        <!-- Left Side: Welcome Banner, Stat Cards, Chart, and Recent Tables -->
        <div>
            <!-- Welcome Banner (No avatar) -->
            <div class="welcome">
                <span class="welcome-date">📅 {{ \Carbon\Carbon::now()->isoFormat('D MMMM Y') }}</span>
                <h2>Selamat Datang, {{ auth()->user()->name }} !</h2>
                <p>Have a nice {{ \Carbon\Carbon::now()->isoFormat('dddd') }}!</p>
            </div>

            <!-- Stat Cards Grid (4 Cards) -->
            <div class="stat-grid">
                <!-- Penelitian -->
                <div class="stat-card pink">
                    <div class="stat-left">
                        <div class="stat-num">{{ $stats['my_research'] }}</div>
                        <div class="stat-lbl">Penelitian</div>
                        <div class="stat-sub">Didanai oleh Simlitabmas/SIM LPPM</div>
                    </div>
                    <div class="stat-ico">🔍</div>
                </div>

                <!-- Pengabdian -->
                <div class="stat-card teal">
                    <div class="stat-left">
                        <div class="stat-num">{{ $stats['my_community_service'] }}</div>
                        <div class="stat-lbl">Pengabdian</div>
                        <div class="stat-sub">Didanai oleh Simlitabmas/SIM LPPM</div>
                    </div>
                    <div class="stat-ico">📋</div>
                </div>

                <!-- Skema Penelitian (Purple) -->
                <div class="stat-card purple">
                    <div class="stat-left">
                        <div class="stat-num">{{ $stats['research_schemes_count'] }}</div>
                        <div class="stat-lbl">Skema Penelitian</div>
                        <div class="stat-sub">Terdaftar di SIM-LPPM</div>
                    </div>
                    <div class="stat-ico">🔬</div>
                </div>

                <!-- Skema PKM (Green) -->
                <div class="stat-card green">
                    <div class="stat-left">
                        <div class="stat-num">{{ $stats['community_service_schemes_count'] }}</div>
                        <div class="stat-lbl">Skema PKM</div>
                        <div class="stat-sub">Terdaftar di SIM-LPPM</div>
                    </div>
                    <div class="stat-ico">📦</div>
                </div>
            </div>

            <!-- Chart Section -->
            <div class="chart-box mb-4">
                <div class="chart-title">Penelitian & Pengabdian</div>
                <div class="legend mb-3">
                    <span><span class="ldot" style="background:#206bc4"></span>Usulan</span>
                    <span><span class="ldot" style="background:#2fb344"></span>Didanai</span>
                </div>
                <div style="position: relative; height: 220px; width: 100%;" wire:ignore>
                    <canvas id="dosenAnalyticsChart"></canvas>
                </div>
            </div>

            <!-- Recent Proposal Tables (Left Column Bottom) -->
            <div class="row g-3">
                <div class="col-md-6 col-12">
                    <div class="card border-0 shadow-sm" style="border-radius: 10px; border: 1px solid #e5e7eb;">
                        <div class="card-header bg-transparent border-0 py-3 d-flex align-items-center">
                            <div class="avatar bg-primary-lt text-primary shadow-sm avatar-sm me-3 border-0">
                                <i class="ti ti-flask-2"></i>
                            </div>
                            <h3 class="card-title fw-bold mb-0" style="font-size: 13px;">Penelitian Terbaru</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-hover table-borderless">
                                <thead class="bg-transparent text-muted">
                                    <tr>
                                        <th class="ps-4" style="font-size: 11px;">Judul</th>
                                        <th class="text-center" style="font-size: 11px;">Status</th>
                                        <th class="text-end pe-4" style="font-size: 11px;">Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentResearch as $research)
                                        <tr>
                                            <td class="ps-4" style="font-size: 12px;">
                                                <div class="fw-bold text-wrap lh-base" title="{{ $research->title }}">
                                                    {{ $research->title }}
                                                </div>
                                                <div class="small text-muted mt-1" style="font-size: 10.5px;">
                                                    Skema: {{ $research->researchScheme?->name ?? '-' }}
                                                </div>
                                            </td>
                                            <td class="text-center" style="font-size: 12px;">
                                                <span class="badge bg-{{ $research->status->color() }}-lt fw-bold px-2 py-1"><span
                                                        class="badge bg-{{ $research->status->color() }} me-1"></span>{{ $research->status->label() }}</span>
                                            </td>
                                            <td class="text-end pe-4 text-muted small" style="font-size: 10.5px;">
                                                {{ $research->created_at->diffForHumans() }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-5 text-muted" style="font-size: 12px;">Belum ada penelitian</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-12">
                    <div class="card border-0 shadow-sm" style="border-radius: 10px; border: 1px solid #e5e7eb;">
                        <div class="card-header bg-transparent border-0 py-3 d-flex align-items-center">
                            <div class="avatar bg-azure-lt text-azure shadow-sm avatar-sm me-3 border-0">
                                <i class="ti ti-users-group"></i>
                            </div>
                            <h3 class="card-title fw-bold mb-0" style="font-size: 13px;">PKM Terbaru</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-hover table-borderless">
                                <thead class="bg-transparent text-muted">
                                    <tr>
                                        <th class="ps-4" style="font-size: 11px;">Judul</th>
                                        <th class="text-center" style="font-size: 11px;">Status</th>
                                        <th class="text-end pe-4" style="font-size: 11px;">Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentCommunityService as $communityService)
                                        <tr>
                                            <td class="ps-4" style="font-size: 12px;">
                                                <div class="fw-bold text-wrap lh-base" title="{{ $communityService->title }}">
                                                    {{ $communityService->title }}
                                                </div>
                                                <div class="small text-muted mt-1" style="font-size: 10.5px;">
                                                    Skema: {{ $communityService->communityServiceScheme?->name ?? '-' }}
                                                </div>
                                            </td>
                                            <td class="text-center" style="font-size: 12px;">
                                                <span
                                                    class="badge bg-{{ $communityService->status->color() }}-lt fw-bold px-2 py-1"><span
                                                        class="badge bg-{{ $communityService->status->color() }} me-1"></span>{{ $communityService->status->label() }}</span>
                                            </td>
                                            <td class="text-end pe-4 text-muted small" style="font-size: 10.5px;">
                                                {{ $communityService->created_at->diffForHumans() }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-5 text-muted" style="font-size: 12px;">Belum ada PKM</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Sidebar Panel -->
        <div class="sidebar">
            <!-- Profil Saya Card -->
            <div class="s-card">
                <div class="s-head">
                    <span>Profil Saya</span>
                    <div class="d-flex align-items-center gap-1">
                        @if(auth()->user()->identity?->sinta_id)
                            <button wire:click.prevent="syncSinta" wire:loading.attr="disabled"
                                class="btn btn-icon btn-sm text-white bg-transparent border-0 p-0 me-1"
                                title="Sinkronkan Data SINTA" style="font-size: 12px;">
                                <i wire:loading.remove class="ti ti-refresh"></i>
                                <div wire:loading class="spinner-border spinner-border-sm" role="status"></div>
                            </button>
                        @endif
                        <span style="cursor:pointer; font-size: 12px;" wire:click.prevent="openEditMetricsModal">✏️</span>
                    </div>
                </div>
                
                <div class="s-body">
                    <div class="av-row">
                        <div class="av">
                            @if (auth()->user()->profile_picture)
                                <img src="{{ auth()->user()->profile_picture }}" style="width:100%; height:100%; object-fit:cover;">
                            @else
                                👤
                            @endif
                        </div>
                        <div style="min-width:0">
                            <div class="av-name">{{ auth()->user()->name }}</div>
                            <div class="av-dept">
                                {{ auth()->user()->identity?->studyProgram?->name ?? 'Teknologi Informasi' }}<br>
                                ITSNU Pekalongan
                            </div>
                            <span class="badge-aktif">Aktif Mengajar</span>
                        </div>
                    </div>

                    <div class="stats-row">
                        <div class="st-item">
                            <div class="st-val">{{ number_format(auth()->user()->identity?->sinta_score_v3_overall ?? 0, 0, ',', '.') }}</div>
                            <div class="st-lbl">Sinta Score<br>overall</div>
                        </div>
                        <div class="st-item">
                            <div class="st-val">{{ auth()->user()->identity?->last_education ?? 'S2' }}</div>
                            <div class="st-lbl">Jenjang<br>Pendidikan</div>
                        </div>
                        <div class="st-item">
                            <div class="st-val" style="color:#d97706">{{ auth()->user()->identity?->functional_position ?? 'Lektor' }}</div>
                            <div class="st-lbl">Jabatan<br>Akademik</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Riwayat Usulan Card -->
            <div class="s-card">
                <div class="s-head">Riwayat Usulan</div>
                <div class="rw-body">
                    <div class="rw-group">
                        <div class="rw-cat">
                            <span class="rw-cat-lbl">Penelitian</span>
                            <a href="{{ route('research.proposal.index') }}" class="rw-more text-decoration-none">more...</a>
                        </div>
                        @forelse($recentResearch->take(2) as $research)
                            <div class="rw-item text-truncate mb-1" title="{{ $research->title }}">
                                {{ $research->title }}
                            </div>
                        @empty
                            <div class="rw-none">Tidak ada</div>
                        @endforelse
                    </div>

                    <div class="rw-group">
                        <div class="rw-cat">
                            <span class="rw-cat-lbl">Pengabdian Masyarakat</span>
                            <a href="{{ route('community-service.proposal.index') }}" class="rw-more text-decoration-none">more...</a>
                        </div>
                        @forelse($recentCommunityService->take(2) as $communityService)
                            <div class="rw-item text-truncate mb-1" title="{{ $communityService->title }}">
                                {{ $communityService->title }}
                            </div>
                        @empty
                            <div class="rw-none">Tidak ada</div>
                        @endforelse
                    </div>

                    <div class="rw-group">
                        <div class="rw-cat">
                            <span class="rw-cat-lbl">Skema Penelitian</span>
                            <span class="rw-more text-muted" style="cursor:default">more...</span>
                        </div>
                        <div class="rw-item">
                            Terdaftar di SIM-LPPM
                        </div>
                    </div>

                    <div class="rw-group">
                        <div class="rw-cat">
                            <span class="rw-cat-lbl">Skema PKM</span>
                            <span class="rw-more text-muted" style="cursor:default">more...</span>
                        </div>
                        <div class="rw-item">
                            Terdaftar di SIM-LPPM
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form Update Metrik -->
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
                            <div class="col-sm-12">
                                <label class="form-label fw-bold">Jenis Kelamin (Untuk Menentukan Ilustrasi Avatar)</label>
                                <select class="form-select" wire:model="gender">
                                    <option value="">Pilih Jenis Kelamin (Default/Deteksi Otomatis)</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                                @error('gender') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
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

<!-- Chart Script -->
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('livewire:navigated', function () {
        initDosenChart();
    });

    document.addEventListener('DOMContentLoaded', function () {
        initDosenChart();
    });

    function initDosenChart() {
        const canvas = document.getElementById('dosenAnalyticsChart');
        if (!canvas) return;
        
        const chartData = @js($chartData);
        if (!chartData || !chartData.labels) return;

        const ctx = canvas.getContext('2d');
        
        // Destroy existing chart instance if exists
        const existingChart = Chart.getChart(canvas);
        if (existingChart) {
            existingChart.destroy();
        }

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: chartData.datasets.map(ds => ({
                    ...ds,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }))
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false // We show a custom HTML legend
                    },
                    tooltip: {
                        backgroundColor: 'rgba(30, 41, 59, 0.9)',
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#9ca3af',
                            font: {
                                size: 10
                            }
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(229, 231, 235, 0.5)'
                        },
                        ticks: {
                            color: '#9ca3af',
                            font: {
                                size: 10
                            },
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
</script>
@endpush