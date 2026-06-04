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
    </div>

    <div class="row row-deck row-cards">
        <!-- KPI Section: Research -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-stacked glass-card border-0 shadow-sm overflow-hidden" style="border-left: 4px solid #206bc4 !important;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="subheader text-primary fw-bold">Penelitian (Total)</div>
                        <div class="ms-auto text-primary opacity-50">
                            <i class="ti ti-flask fs-2"></i>
                        </div>
                    </div>
                    <div class="h1 mb-3 fw-bold">{{ $stats['total_research'] ?? 0 }}</div>
                    <div class="d-flex align-items-baseline flex-wrap gap-2">
                        @php $resApprOnly = $stats['research_approved'] - ($stats['research_completed'] ?? 0); @endphp
                        @if($resApprOnly > 0)
                            <span class="badge bg-success-lt text-success fw-bold">
                                {{ $resApprOnly }} Disetujui
                            </span>
                        @endif
                        @if(($stats['research_completed'] ?? 0) > 0)
                            <span class="badge bg-teal-lt text-teal fw-bold">
                                {{ $stats['research_completed'] }} Selesai
                            </span>
                        @endif
                        @if(($stats['research_pending'] ?? 0) > 0)
                            <span class="badge bg-warning-lt text-warning fw-bold">
                                {{ $stats['research_pending'] }} Pending
                            </span>
                        @endif
                    </div>
                </div>
                <div class="progress progress-sm card-progress">
                    @php 
                        $resPrc = ($stats['total_research'] > 0) ? ($stats['research_approved'] / $stats['total_research'] * 100) : 0;
                    @endphp
                    <div class="progress-bar bg-primary" style="width: {{ $resPrc }}%" role="progressbar"></div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-stacked glass-card border-0 shadow-sm overflow-hidden" style="border-left: 4px solid #4591ed !important;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="subheader text-azure fw-bold">PKM (Total)</div>
                        <div class="ms-auto text-azure opacity-50">
                            <i class="ti ti-users-group fs-2"></i>
                        </div>
                    </div>
                    <div class="h1 mb-3 fw-bold">{{ $stats['total_community_service'] ?? 0 }}</div>
                    <div class="d-flex align-items-baseline flex-wrap gap-2">
                        @php $pkmApprOnly = $stats['community_service_approved'] - ($stats['community_service_completed'] ?? 0); @endphp
                        @if($pkmApprOnly > 0)
                            <span class="badge bg-success-lt text-success fw-bold">
                                {{ $pkmApprOnly }} Disetujui
                            </span>
                        @endif
                        @if(($stats['community_service_completed'] ?? 0) > 0)
                            <span class="badge bg-teal-lt text-teal fw-bold">
                                {{ $stats['community_service_completed'] }} Selesai
                            </span>
                        @endif
                        @if(($stats['community_service_pending'] ?? 0) > 0)
                            <span class="badge bg-warning-lt text-warning fw-bold">
                                {{ $stats['community_service_pending'] }} Pending
                            </span>
                        @endif
                    </div>
                </div>
                <div class="progress progress-sm card-progress">
                    @php 
                        $pkmPrc = ($stats['total_community_service'] > 0) ? ($stats['community_service_approved'] / $stats['total_community_service'] * 100) : 0;
                    @endphp
                    <div class="progress-bar bg-azure" style="width: {{ $pkmPrc }}%" role="progressbar"></div>
                </div>
            </div>
        </div>

        <!-- KPI Section: Approval Rate -->
        <div class="col-sm-6 col-lg-3">
            @php
                $totalProp = $stats['total_research'] + $stats['total_community_service'];
                $totalAppr = $stats['research_approved'] + $stats['community_service_approved'];
                $approvalRate = ($totalProp > 0) ? round(($totalAppr / $totalProp) * 100, 1) : 0;
                $totalBudget = ($stats['research_budget'] ?? 0) + ($stats['pkm_budget'] ?? 0);
            @endphp
            <div class="card card-stacked glass-card border-0 shadow-sm overflow-hidden" style="border-left: 4px solid #0ca678 !important;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="subheader text-green fw-bold">Approval Rate</div>
                        <div class="ms-auto text-green opacity-50">
                            <i class="ti ti-chart-bar fs-2"></i>
                        </div>
                    </div>
                    <div class="h1 mb-3 fw-bold">{{ $approvalRate }}%</div>
                    <div class="text-muted small mb-2">{{ $totalAppr }} dari {{ $totalProp }} usulan disetujui</div>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-green" style="width: {{ $approvalRate }}%" role="progressbar"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI Section: Total Budget -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-stacked glass-card border-0 shadow-sm overflow-hidden" style="border-left: 4px solid #ae3ec9 !important;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="subheader text-purple fw-bold">Total Anggaran</div>
                        <div class="ms-auto text-purple opacity-50">
                            <i class="ti ti-cash fs-2"></i>
                        </div>
                    </div>
                    <div class="h2 mb-3 fw-bold">Rp {{ number_format($totalBudget, 0, ',', '.') }}</div>
                    <div class="d-flex flex-column gap-1">
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted"><i class="ti ti-flask me-1"></i>Penelitian</span>
                            <span class="fw-bold">Rp {{ number_format($stats['research_budget'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted"><i class="ti ti-users-group me-1"></i>PKM</span>
                            <span class="fw-bold">Rp {{ number_format($stats['pkm_budget'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    @php
                        $resBudget = $stats['research_budget'] ?? 0;
                        $pkmBudget = $stats['pkm_budget'] ?? 0;
                        $totBudget = $resBudget + $pkmBudget;
                        $resPrc = $totBudget > 0 ? ($resBudget / $totBudget * 100) : 0;
                        $pkmPrc = $totBudget > 0 ? ($pkmBudget / $totBudget * 100) : 0;
                    @endphp
                    @if($totBudget > 0)
                        <div class="progress progress-sm mt-3 shadow-sm rounded-pill overflow-hidden">
                            <div class="progress-bar bg-primary" style="width: {{ $resPrc }}%" role="progressbar" aria-label="Penelitian {{ round($resPrc) }}%" title="Penelitian {{ round($resPrc) }}%"></div>
                            <div class="progress-bar bg-azure" style="width: {{ $pkmPrc }}%" role="progressbar" aria-label="PKM {{ round($pkmPrc) }}%" title="PKM {{ round($pkmPrc) }}%"></div>
                        </div>
                    @endif
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
                    <div class="d-flex align-items-center">
                        <div class="avatar bg-primary-lt text-primary shadow-sm avatar-sm me-3 border-0">
                            <i class="ti ti-flask-2"></i>
                        </div>
                        <h3 class="card-title fw-bold mb-0">Penelitian Terbaru</h3>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover table-borderless">
                        <thead class="bg-light-lt">
                            <tr>
                                <th class="ps-4">Judul & Pengaju</th>
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
                                        {{ $research->created_at->diffForHumans() }}
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
                    <div class="d-flex align-items-center">
                        <div class="avatar bg-azure-lt text-azure shadow-sm avatar-sm me-3 border-0">
                            <i class="ti ti-users-group"></i>
                        </div>
                        <h3 class="card-title fw-bold mb-0">PKM Terbaru</h3>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover table-borderless">
                        <thead class="bg-light-lt">
                            <tr>
                                <th class="ps-4">Judul & Pengaju</th>
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
                                        {{ $communityService->created_at->diffForHumans() }}
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
