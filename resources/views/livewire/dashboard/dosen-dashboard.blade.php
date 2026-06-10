<div>
    {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
    <div class="d-flex justify-content-end mb-4">
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

    <div class="row row-cards">
        <!-- Kolom Kiri: Main Stats, Banner & Chart (75% lebar di Desktop) -->
        <div class="col-lg-9 col-12 d-flex flex-column gap-4">
            <!-- Greeting Card Banner -->
            <div class="bima-welcome-card p-4 position-relative">
                <div class="row align-items-center">
                    <div class="col-md-8 col-12">
                        <div class="text-white-50 small fw-bold mb-2">
                            <i class="ti ti-calendar me-1"></i>
                            {{ \Carbon\Carbon::now()->isoFormat('D MMMM Y') }}
                        </div>
                        <h1 class="text-white fw-bold mb-1">Selamat Datang, {{ auth()->user()->name }}!</h1>
                        <p class="text-white-50 mb-0">Have a nice {{ \Carbon\Carbon::now()->isoFormat('dddd') }}! Kelola usulan, publikasi, dan kolaborasi riset Anda di SIM-LPPM.</p>
                    </div>
                </div>
                <img src="{{ asset(auth()->user()->isFemale() ? 'images/dashboard_avatar_female.png' : 'images/dashboard_avatar_male.png') }}" class="bima-welcome-avatar" alt="Lecturer Avatar">
            </div>

            <!-- Premium Stat Cards (Grid of 4) -->
            <div class="row g-3">
                <!-- Penelitian Card -->
                <div class="col-sm-6 col-md-3">
                    <div class="bima-stat-card bima-card-penelitian p-3">
                        <div class="h2 fw-bold mb-1 text-white">{{ $stats['my_research'] }}</div>
                        <div class="fw-bold small mb-1 text-white">Penelitian</div>
                        <div class="text-white-50 small" style="font-size: 0.72rem;">Didanai oleh Simlitabmas/BIMA</div>
                        <div class="bima-stat-icon-wrapper">
                            <i class="ti ti-search text-white"></i>
                        </div>
                    </div>
                </div>
                <!-- Pengabdian Card -->
                <div class="col-sm-6 col-md-3">
                    <div class="bima-stat-card bima-card-pengabdian p-3">
                        <div class="h2 fw-bold mb-1 text-white">{{ $stats['my_community_service'] }}</div>
                        <div class="fw-bold small mb-1 text-white">Pengabdian</div>
                        <div class="text-white-50 small" style="font-size: 0.72rem;">Didanai oleh Simlitabmas/BIMA</div>
                        <div class="bima-stat-icon-wrapper">
                            <i class="ti ti-file-text text-white"></i>
                        </div>
                    </div>
                </div>
                <!-- Skema Penelitian Card -->
                <div class="col-sm-6 col-md-3">
                    <div class="bima-stat-card bima-card-konsorsium p-3">
                        <div class="h2 fw-bold mb-1 text-white">{{ $stats['research_schemes_count'] }}</div>
                        <div class="fw-bold small mb-1 text-white">Skema Penelitian</div>
                        <div class="text-white-50 small" style="font-size: 0.72rem;">Terdaftar di SIM-LPPM</div>
                        <div class="bima-stat-icon-wrapper">
                            <i class="ti ti-hierarchy text-white"></i>
                        </div>
                    </div>
                </div>
                <!-- Skema PKM Card -->
                <div class="col-sm-6 col-md-3">
                    <div class="bima-stat-card bima-card-prototipe p-3">
                        <div class="h2 fw-bold mb-1 text-white">{{ $stats['community_service_schemes_count'] }}</div>
                        <div class="fw-bold small mb-1 text-white">Skema PKM</div>
                        <div class="text-white-50 small" style="font-size: 0.72rem;">Terdaftar di SIM-LPPM</div>
                        <div class="bima-stat-icon-wrapper">
                            <i class="ti ti-box text-white"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trend Line Chart -->
            <div>
                <x-dashboard.analytics-chart
                    type="line"
                    title="Tren Pengajuan Usulan (5 Tahun Terakhir)"
                    :labels="$chartData['labels']"
                    :datasets="$chartData['datasets']"
                />
            </div>

            <!-- Tables Section (Penelitian & PKM Terbaru) -->
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
                                        <th class="ps-4">Judul</th>
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
                                                <div class="small text-muted mt-1">
                                                    Skema: {{ $research->researchScheme?->name ?? '-' }}
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $research->status->color() }}-lt fw-bold px-2 py-1"><span
                                                        class="badge bg-{{ $research->status->color() }} me-1"></span>{{ $research->status->label() }}</span>
                                            </td>
                                            <td class="text-end pe-4 text-muted small">
                                                {{ $research->created_at->diffForHumans() }}
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
                                        <th class="ps-4">Judul</th>
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
                                                <div class="small text-muted mt-1">
                                                    Skema: {{ $communityService->communityServiceScheme?->name ?? '-' }}
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span
                                                    class="badge bg-{{ $communityService->status->color() }}-lt fw-bold px-2 py-1"><span
                                                        class="badge bg-{{ $communityService->status->color() }} me-1"></span>{{ $communityService->status->label() }}</span>
                                            </td>
                                            <td class="text-end pe-4 text-muted small">
                                                {{ $communityService->created_at->diffForHumans() }}
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

        <!-- Kolom Kanan: Profile & Quick Links (25% lebar di Desktop) -->
        <div class="col-lg-3 col-12 d-flex flex-column gap-4">
            <!-- Widget Profil Saya -->
            <div class="card bima-profile-card">
                <div class="card-header bg-transparent border-0 py-3 d-flex align-items-center justify-content-between">
                    <h3 class="card-title fw-bold text-dark mb-0">Profil Saya</h3>
                    <div class="d-flex align-items-center gap-1">
                        @if(auth()->user()->identity?->sinta_id)
                            <button wire:click.prevent="syncSinta" wire:loading.attr="disabled"
                                class="btn btn-icon btn-ghost-primary btn-sm rounded-circle"
                                title="Sinkronkan Data SINTA">
                                <i wire:loading.remove class="ti ti-refresh"></i>
                                <div wire:loading class="spinner-border spinner-border-sm" role="status"></div>
                            </button>
                        @endif
                        <button wire:click.prevent="openEditMetricsModal" class="btn btn-icon btn-ghost-secondary btn-sm rounded-circle" title="Edit Metrik Publikasi">
                            <i class="ti ti-pencil"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body pt-0 text-center">
                    <div class="avatar avatar-xl mb-3 rounded-circle shadow-sm bg-primary-lt">
                        {{ substr(auth()->user()->name, 0, 2) }}
                    </div>
                    <h4 class="fw-bold mb-1 text-dark">{{ auth()->user()->name }}</h4>
                    <p class="text-muted small mb-2">{{ auth()->user()->identity?->studyProgram?->name ?? 'Teknologi Informasi' }}</p>
                    <div class="d-flex justify-content-center mb-3">
                        <span class="badge bg-green-lt fw-bold px-3 py-1">Aktif Mengajar</span>
                    </div>
                    
                    <hr class="my-3">
                    
                    <div class="row text-center g-2">
                        <div class="col-4">
                            <a href="{{ auth()->user()->identity?->sinta_id ? auth()->user()->identity->getSintaUrl() : 'https://sinta.kemdikbud.go.id/authors' }}" target="_blank" class="text-decoration-none d-block">
                                <div class="h3 fw-bold text-primary mb-0">
                                    {{ number_format(auth()->user()->identity?->sinta_score_v3_overall ?? 0, 0, ',', '.') }}
                                </div>
                                <div class="text-muted" style="font-size: 0.7rem;">Sinta Score</div>
                            </a>
                        </div>
                        <div class="col-4 border-start border-end">
                            <div class="h3 fw-bold text-dark mb-0">
                                {{ auth()->user()->identity?->last_education ?? 'S2' }}
                            </div>
                            <div class="text-muted" style="font-size: 0.7rem;">Pendidikan</div>
                        </div>
                        <div class="col-4">
                            <div class="h3 fw-bold text-dark mb-0 text-truncate px-1" style="font-size: 0.85rem; font-weight: 700;" title="{{ auth()->user()->identity?->functional_position ?? 'Lektor' }}">
                                {{ auth()->user()->identity?->functional_position ?? 'Lektor' }}
                            </div>
                            <div class="text-muted" style="font-size: 0.7rem;">Jabatan</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Widget Riwayat Usulan -->
            <div class="card bima-riwayat-card">
                <div class="card-header bg-transparent border-0 py-3">
                    <h3 class="card-title fw-bold text-dark mb-0">Riwayat Usulan</h3>
                </div>
                <div class="card-body pt-0">
                    <!-- Penelitian -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold text-dark small">Penelitian</span>
                            <a href="{{ route('research.proposal.index') }}" class="small text-primary text-decoration-none">more..</a>
                        </div>
                        <ul class="list-unstyled mb-0">
                            @forelse($recentResearch->take(2) as $research)
                                <li class="text-truncate small mb-1" style="max-width: 100%;" title="{{ $research->title }}">
                                    <i class="ti ti-dot text-primary"></i>
                                    {{ $research->title }}
                                </li>
                            @empty
                                <li class="text-muted small">Tidak ada</li>
                            @endforelse
                        </ul>
                    </div>

                    <!-- Pengabdian -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold text-dark small">Pengabdian kepada Masyarakat</span>
                            <a href="{{ route('community-service.proposal.index') }}" class="small text-primary text-decoration-none">more..</a>
                        </div>
                        <ul class="list-unstyled mb-0">
                            @forelse($recentCommunityService->take(2) as $communityService)
                                <li class="text-truncate small mb-1" style="max-width: 100%;" title="{{ $communityService->title }}">
                                    <i class="ti ti-dot text-cyan"></i>
                                    {{ $communityService->title }}
                                </li>
                            @empty
                                <li class="text-muted small">Tidak ada</li>
                            @endforelse
                        </ul>
                    </div>

                    <!-- Skema Penelitian -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold text-dark small">Skema Penelitian</span>
                            <span class="small text-muted">more..</span>
                        </div>
                        <ul class="list-unstyled mb-0">
                            <li class="text-muted small">Terdaftar di SIM-LPPM</li>
                        </ul>
                    </div>

                    <!-- Skema PKM -->
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold text-dark small">Skema PKM</span>
                            <span class="small text-muted">more..</span>
                        </div>
                        <ul class="list-unstyled mb-0">
                            <li class="text-muted small">Terdaftar di SIM-LPPM</li>
                        </ul>
                    </div>
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