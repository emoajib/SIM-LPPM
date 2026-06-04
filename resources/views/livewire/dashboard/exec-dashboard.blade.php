<div>
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
            <div class="dropdown">
                <a href="#" class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2"
                    data-bs-toggle="dropdown">
                    <i class="ti ti-layers-intersect fs-2"></i>
                    <span>Semester:
                        {{ $selectedSemester === 'all' ? 'Semua' : 'Semester ' . ucfirst($selectedSemester) }}</span>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <a href="#" class="dropdown-item {{ $selectedSemester === 'all' ? 'active' : '' }}"
                        wire:click.preserve-scroll="$set('selectedSemester', 'all')">
                        Semua
                    </a>
                    <a href="#" class="dropdown-item {{ $selectedSemester === 'ganjil' ? 'active' : '' }}"
                        wire:click.preserve-scroll="$set('selectedSemester', 'ganjil')">
                        Semester Ganjil (Sep-Feb)
                    </a>
                    <a href="#" class="dropdown-item {{ $selectedSemester === 'genap' ? 'active' : '' }}"
                        wire:click.preserve-scroll="$set('selectedSemester', 'genap')">
                        Semester Genap (Mar-Ags)
                    </a>
                </div>
            </div>
            <div class="dropdown">
                <a href="#" class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2"
                    data-bs-toggle="dropdown">
                    <i class="ti ti-filter fs-2"></i>
                    <span>Status:
                        {{ $availableStatuses[$selectedStatus] ?? 'Semua Status' }}</span>
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
            @unless($this->isDekanRestricted())
            <div class="dropdown">
                <a href="#" class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2"
                    data-bs-toggle="dropdown">
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
                <a href="#" class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2"
                    data-bs-toggle="dropdown">
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
            @endunless
        </div>
    </div>



    <!-- Executive KPI Cards -->
    <div class="row row-deck row-cards mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm overflow-hidden h-100" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="subheader text-primary fw-bold">Total Penelitian</div>
                        <div class="ms-auto text-primary bg-primary-lt rounded-circle p-2 d-flex align-items-center justify-content-center"
                            style="width: 32px; height: 32px;">
                            <i class="ti ti-microscope fs-3"></i>
                        </div>
                    </div>
                    <div class="h1 mb-1 fw-bold text-primary">{{ $stats['total_research'] ?? 0 }}</div>
                    <div class="text-muted small">Proposal terdaftar</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm overflow-hidden h-100" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="subheader text-azure fw-bold">Total PKM</div>
                        <div class="ms-auto text-azure bg-azure-lt rounded-circle p-2 d-flex align-items-center justify-content-center"
                            style="width: 32px; height: 32px;">
                            <i class="ti ti-users-group fs-3"></i>
                        </div>
                    </div>
                    <div class="h1 mb-1 fw-bold text-azure">{{ $stats['total_community_service'] ?? 0 }}</div>
                    <div class="text-muted small">Proposal pengabdian</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm overflow-hidden h-100" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="subheader text-orange fw-bold">Persetujuan Lap. Akhir</div>
                        <div class="ms-auto text-orange bg-orange-lt rounded-circle p-2 d-flex align-items-center justify-content-center"
                            style="width: 32px; height: 32px;">
                            <i class="ti ti-clipboard-check fs-3"></i>
                        </div>
                    </div>
                    <div class="h1 mb-1 fw-bold text-orange">{{ $stats['final_report_pending'] ?? 0 }}</div>
                    <div class="text-muted small">Laporan menunggu tinjauan</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm overflow-hidden h-100" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="subheader text-purple fw-bold">Total Luaran</div>
                        <div class="ms-auto text-purple bg-purple-lt rounded-circle p-2 d-flex align-items-center justify-content-center"
                            style="width: 32px; height: 32px;">
                            <i class="ti ti-award fs-3"></i>
                        </div>
                    </div>
                    <div class="h1 mb-1 fw-bold text-purple">{{ $stats['total_outputs'] ?? 0 }}</div>
                    <div class="text-muted small">Capaian luaran terarsip</div>
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