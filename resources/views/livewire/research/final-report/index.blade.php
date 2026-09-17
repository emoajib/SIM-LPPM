<x-slot:title>Laporan Akhir Penelitian</x-slot:title>
<x-slot:pageTitle>Laporan Akhir Penelitian</x-slot:pageTitle>
<x-slot:pageSubtitle>
    Pantau progres, filter status, dan kelola laporan akhir penelitian yang telah didanai.
</x-slot:pageSubtitle>

{{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
<div>
    <x-tabler.alert />

    <!-- Role-based Tabs (khusus peran dosen) -->
    @if (auth()->user()->activeHasAnyRole(['dosen']))
        <div class="mb-3">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if ($roleFilter === 'ketua') active @endif"
                        wire:click="$set('roleFilter', 'ketua')" role="tab"
                        aria-selected="@if ($roleFilter === 'ketua') true @else false @endif">
                        <x-lucide-crown class="icon me-2" />
                        Sebagai Ketua
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if ($roleFilter === 'anggota') active @endif"
                        wire:click="$set('roleFilter', 'anggota')" role="tab"
                        aria-selected="@if ($roleFilter === 'anggota') true @else false @endif">
                        <x-lucide-users class="icon me-2" />
                        Sebagai Anggota
                    </button>
                </li>
            </ul>
        </div>
    @endif

    <!-- Metric Counter Cards (Informatif & Klikable) -->
    <div class="row row-cards mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm cursor-pointer {{ $statusFilter === 'all' ? 'border-primary shadow' : '' }}"
                wire:click="filterByStatus('all')" title="Tampilkan Semua Status">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-primary text-white avatar">
                                <x-lucide-file-text class="icon" />
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Total Wajib Lapor</div>
                            <div class="text-secondary small">{{ $this->statistics['total'] }} Penelitian</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm cursor-pointer {{ $statusFilter === 'approved' ? 'border-success shadow' : '' }}"
                wire:click="filterByStatus('approved')" title="Filter Laporan yang Sudah Disahkan LPPM">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-success text-white avatar">
                                <x-lucide-check-circle-2 class="icon" />
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium text-success">Laporan Selesai</div>
                            <div class="text-secondary small">{{ $this->statistics['approved'] }} Disahkan LPPM</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm cursor-pointer {{ in_array($statusFilter, ['submitted', 'approved_by_dekan']) ? 'border-info shadow' : '' }}"
                wire:click="filterByStatus('submitted')" title="Filter Laporan dalam Proses Review/Approval">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-info text-white avatar">
                                <x-lucide-clock class="icon" />
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium text-info">Dalam Proses</div>
                            <div class="text-secondary small">{{ $this->statistics['in_review'] }} Menunggu Approval</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm cursor-pointer {{ $statusFilter === 'belum_laporan' ? 'border-warning shadow' : '' }}"
                wire:click="filterByStatus('belum_laporan')" title="Filter Penelitian yang Belum Mengunggah Laporan">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-warning text-white avatar">
                                <x-lucide-alert-triangle class="icon" />
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium text-warning">Belum Laporan</div>
                            <div class="text-secondary small">{{ $this->statistics['belum_laporan'] }} Perlu Diunggah</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Section -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-2 align-items-center">
                        <!-- Search Input -->
                        <div class="col-md-4">
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <x-lucide-search class="icon text-muted" />
                                </span>
                                <input type="text" class="form-control"
                                    placeholder="Cari judul, ketua, atau NIDN..."
                                    wire:model.live.debounce.300ms="search" />
                            </div>
                        </div>

                        <!-- Year Filter -->
                        <div class="col-md-2">
                            <select class="form-select" wire:model.live="selectedYear">
                                <option value="">Semua Tahun</option>
                                @foreach ($this->availableYears as $year)
                                    <option value="{{ $year }}">Tahun {{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Scheme Filter -->
                        <div class="col-md-3">
                            <select class="form-select" wire:model.live="schemeFilter">
                                <option value="all">Semua Skema Penelitian</option>
                                @foreach ($this->availableSchemes as $scheme)
                                    <option value="{{ $scheme->id }}">{{ $scheme->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div class="col-md-2">
                            <select class="form-select" wire:model.live="statusFilter">
                                <option value="all">Semua Status Laporan</option>
                                <option value="belum_laporan">⚠ Belum Laporan</option>
                                <option value="draft">📝 Draft Laporan</option>
                                <option value="submitted">⏳ Diajukan (Menunggu Dekan)</option>
                                <option value="approved_by_dekan">🔷 Disetujui Dekan</option>
                                <option value="approved">✅ Disetujui LPPM (Selesai)</option>
                                <option value="rejected">❌ Ditolak / Perbaikan</option>
                            </select>
                        </div>

                        <!-- Reset Button -->
                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-icon btn-outline-secondary w-100" wire:click="resetFilters" title="Reset Semua Filter">
                                <x-lucide-rotate-ccw class="icon" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Proposals Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Daftar Laporan Akhir Penelitian</h3>
            <span class="badge bg-blue-lt">
                Ditemukan: {{ $this->proposals->count() }} Data
            </span>
        </div>
        <div class="table-responsive">
            <table class="card-table table-vcenter table table-striped">
                <thead>
                    <tr>
                        <th class="w-1">No</th>
                        <th>Judul & Skema Penelitian</th>
                        <th>Ketua & Fakultas/Prodi</th>
                        <th>Status Laporan Akhir</th>
                        <th class="w-1 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->proposals as $index => $proposal)
                        <tr wire:key="proposal-{{ $proposal->id }}">
                            <td class="text-center text-muted">{{ $index + 1 }}</td>
                            <td class="text-wrap" style="max-width: 380px;">
                                <div class="text-reset fw-bold text-truncate" title="{{ $proposal->title }}">
                                    {{ $proposal->title }}
                                </div>
                                <div class="mt-1 d-flex align-items-center gap-1">
                                    <x-tabler.badge variant="outline" class="text-muted small">
                                        {{ $proposal->researchScheme?->name ?? 'Tanpa Skema' }}
                                    </x-tabler.badge>
                                    @if($proposal->start_year)
                                        <span class="badge bg-secondary-lt small">Tahun {{ $proposal->start_year }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="fw-medium">{{ $proposal->submitter?->name ?? '-' }}</div>
                                <div class="text-secondary small">
                                    NIDN: {{ $proposal->submitter?->identity?->nidn ?? $proposal->submitter?->identity?->identity_id ?? '-' }}
                                </div>
                                <div class="text-muted small">
                                    {{ $proposal->submitter?->identity?->studyProgram?->name ?? $proposal->submitter?->identity?->faculty?->name ?? '-' }}
                                </div>
                            </td>
                            <td>
                                @php
                                    $finalReport = $proposal->latestFinalReport ?? $proposal->progressReports->where('reporting_period', 'final')->first();
                                @endphp
                                @if ($finalReport)
                                    <div>
                                        <x-tabler.badge :color="$finalReport->status->color()">
                                            {{ $finalReport->status->label() }}
                                        </x-tabler.badge>
                                    </div>
                                    @if ($finalReport->submitted_at)
                                        <small class="text-secondary d-block mt-1">
                                            Diajukan: {{ $finalReport->submitted_at->format('d/m/Y') }}
                                        </small>
                                    @endif
                                @else
                                    <span class="badge bg-warning-lt">
                                        <x-lucide-alert-triangle class="icon icon-inline me-1" />
                                        Belum Laporan
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @php
                                    $hasFinal = (bool) ($proposal->latestFinalReport ?? $proposal->progressReports->where('reporting_period', 'final')->first());
                                @endphp
                                <a href="{{ route('research.final-report.show', $proposal) }}"
                                    class="btn {{ $hasFinal ? 'btn-primary' : 'btn-outline-warning' }} btn-sm" wire:navigate.hover>
                                    <x-lucide-file-edit class="icon me-1" />
                                    {{ $hasFinal ? 'Lihat Laporan' : 'Buat Laporan' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-5 text-center">
                                <div class="mb-3">
                                    <x-lucide-inbox class="text-secondary icon icon-lg" />
                                </div>
                                <p class="text-secondary mb-1">
                                    Tidak ada data laporan akhir penelitian yang sesuai filter.
                                </p>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" wire:click="resetFilters">
                                    <x-lucide-rotate-ccw class="icon me-1" /> Reset Filter
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>