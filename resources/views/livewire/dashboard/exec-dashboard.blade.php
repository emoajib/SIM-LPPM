<div>
    {{-- ===================================================================
         PAGE HEADER — Filter Dashboard Eksekutif (Rektor / Dekan)
         Vetted by AI - Manual Review Required by Senior Engineer/Manager
         =================================================================== --}}
    <div class="page-header d-print-none mb-0">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                {{-- Judul Halaman --}}
                <div class="col">
                    <h2 class="page-title">
                        Dashboard Eksekutif
                    </h2>
                    <div class="text-muted mt-1">
                        Selamat datang, {{ auth()->user()->name }}
                        @if ($this->isDekanRestricted())
                            &mdash; Dekan
                            @if ($stats['faculty_name'] ?? null)
                                <span class="badge bg-primary-lt ms-1">{{ $stats['faculty_name'] }}</span>
                            @endif
                        @else
                            &mdash; Rektor
                        @endif
                    </div>
                </div>

                {{-- Filter Primer: Tahun + Tombol Toggle Filter Lanjutan --}}
                <div class="col-md-auto ms-auto d-print-none">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        {{-- Filter Tahun --}}
                        <div class="dropdown">
                            <a href="#"
                                class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ti ti-calendar-event"></i>
                                <span>Tahun: {{ $selectedYear }}</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                @foreach ($availableYears as $year)
                                    <a href="#"
                                        class="dropdown-item {{ $selectedYear == $year ? 'active' : '' }}"
                                        wire:click.preserve-scroll="$set('selectedYear', {{ $year }})">
                                        {{ $year }}
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        {{-- Tombol Toggle Filter Lanjutan --}}
                        <button class="btn {{ $this->activeFilterCount > 0 ? 'btn-primary' : 'btn-outline-secondary' }} d-flex align-items-center gap-2"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#advancedFilters"
                            aria-expanded="{{ $this->activeFilterCount > 0 ? 'true' : 'false' }}"
                            aria-controls="advancedFilters">
                            <i class="ti ti-adjustments-horizontal"></i>
                            <span>Filter Lanjutan</span>
                            @if ($this->activeFilterCount > 0)
                                <span class="badge bg-white text-primary ms-1">{{ $this->activeFilterCount }}</span>
                            @endif
                        </button>

                        {{-- Tombol Reset Filter (muncul hanya bila ada filter aktif) --}}
                        @if ($this->activeFilterCount > 0)
                            <button class="btn btn-ghost-danger d-flex align-items-center gap-1"
                                type="button"
                                wire:click="resetFilters"
                                title="Reset semua filter">
                                <i class="ti ti-x"></i>
                                <span>Reset</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Panel Filter Lanjutan (Collapsible) --}}
            <div class="collapse {{ $this->activeFilterCount > 0 ? 'show' : '' }} mt-3" id="advancedFilters">
                <div class="card card-sm border-0 bg-light">
                    <div class="card-body py-3">
                        <div class="row g-2 align-items-center">
                            <div class="col-auto">
                                <span class="text-muted small fw-medium">
                                    <i class="ti ti-filter me-1"></i>Filter Lanjutan:
                                </span>
                            </div>

                            {{-- Filter Semester --}}
                            <div class="col-auto">
                                <div class="dropdown">
                                    <a href="#"
                                        class="btn btn-sm {{ $selectedSemester !== 'all' ? 'btn-primary' : 'btn-outline-secondary' }} dropdown-toggle d-flex align-items-center gap-1"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-layers-intersect"></i>
                                        <span>
                                            @if ($selectedSemester === 'all')
                                                Semua Semester
                                            @elseif ($selectedSemester === 'ganjil')
                                                Sem. Ganjil
                                            @else
                                                Sem. Genap
                                            @endif
                                        </span>
                                    </a>
                                    <div class="dropdown-menu">
                                        <a href="#"
                                            class="dropdown-item {{ $selectedSemester === 'all' ? 'active' : '' }}"
                                            wire:click.preserve-scroll="$set('selectedSemester', 'all')">
                                            Semua Semester
                                        </a>
                                        <a href="#"
                                            class="dropdown-item {{ $selectedSemester === 'ganjil' ? 'active' : '' }}"
                                            wire:click.preserve-scroll="$set('selectedSemester', 'ganjil')">
                                            Semester Ganjil (Sep–Feb)
                                        </a>
                                        <a href="#"
                                            class="dropdown-item {{ $selectedSemester === 'genap' ? 'active' : '' }}"
                                            wire:click.preserve-scroll="$set('selectedSemester', 'genap')">
                                            Semester Genap (Mar–Ags)
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Filter Status --}}
                            <div class="col-auto">
                                <div class="dropdown">
                                    <a href="#"
                                        class="btn btn-sm {{ $selectedStatus !== 'all' ? 'btn-primary' : 'btn-outline-secondary' }} dropdown-toggle d-flex align-items-center gap-1"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-filter"></i>
                                        <span>{{ $availableStatuses[$selectedStatus] ?? 'Semua Status' }}</span>
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
                            </div>

                            {{-- Filter Fakultas & Prodi (hanya untuk Rektor) --}}
                            @unless($this->isDekanRestricted())
                                {{-- Pemisah visual --}}
                                <div class="col-auto">
                                    <div class="vr" style="height: 28px;"></div>
                                </div>

                                {{-- Filter Fakultas --}}
                                <div class="col-auto">
                                    <div class="dropdown">
                                        <a href="#"
                                            class="btn btn-sm {{ $selectedFaculty !== 'all' ? 'btn-primary' : 'btn-outline-secondary' }} dropdown-toggle d-flex align-items-center gap-1"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ti ti-building"></i>
                                            <span>{{ $selectedFaculty !== 'all' ? ($availableFaculties[$selectedFaculty] ?? 'Fakultas') : 'Semua Fakultas' }}</span>
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
                                </div>

                                {{-- Filter Prodi (opsional, tampil saat Fakultas dipilih) --}}
                                <div class="col-auto">
                                    <div class="dropdown">
                                        <a href="#"
                                            class="btn btn-sm {{ $selectedProdi !== 'all' ? 'btn-primary' : 'btn-outline-secondary' }} dropdown-toggle d-flex align-items-center gap-1"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ti ti-book"></i>
                                            <span>{{ $selectedProdi !== 'all' ? ($availableProdis[$selectedProdi] ?? 'Prodi') : 'Semua Prodi' }}</span>
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
                                </div>
                            @endunless

                            {{-- Pemisah visual --}}
                            <div class="col-auto">
                                <div class="vr" style="height: 28px;"></div>
                            </div>

                            {{-- Filter Skema Penelitian --}}
                            <div class="col-auto">
                                <div class="dropdown">
                                    <a href="#"
                                        class="btn btn-sm {{ $selectedResearchScheme !== 'all' ? 'btn-primary' : 'btn-outline-secondary' }} dropdown-toggle d-flex align-items-center gap-1"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-flask"></i>
                                        <span>{{ $selectedResearchScheme !== 'all' ? ($availableResearchSchemes[$selectedResearchScheme] ?? 'Skema') : 'Skema Penelitian' }}</span>
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
                            </div>

                            {{-- Filter Skema PKM --}}
                            <div class="col-auto">
                                <div class="dropdown">
                                    <a href="#"
                                        class="btn btn-sm {{ $selectedCommunityServiceScheme !== 'all' ? 'btn-primary' : 'btn-outline-secondary' }} dropdown-toggle d-flex align-items-center gap-1"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-users-group"></i>
                                        <span>{{ $selectedCommunityServiceScheme !== 'all' ? ($availableCommunityServiceSchemes[$selectedCommunityServiceScheme] ?? 'Skema') : 'Skema PKM' }}</span>
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
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body pt-3">
        <div class="container-xl">

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
    </div>
</div>