{{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
<x-slot:title>Persetujuan Laporan Akhir</x-slot:title>
<x-slot:pageTitle>Persetujuan Laporan Akhir</x-slot:pageTitle>
<x-slot:pageSubtitle>
    Tinjau dan sahkan laporan akhir penelitian dan pengabdian yang diajukan dosen dan disetujui Dekan.
</x-slot:pageSubtitle>

<div>
    <x-tabler.alert />

    <!-- Stat Cards -->
    <div class="row row-cards mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-primary text-white avatar">
                                <i class="ti ti-file-text fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">{{ $this->stats['total_submitted'] }} Laporan</div>
                            <div class="text-muted small">Total Berkas Masuk</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm" style="border-left: 3px solid #6366f1 !important;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-purple text-white avatar">
                                <i class="ti ti-check fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium text-purple">{{ $this->stats['ready_lppm'] }} Laporan</div>
                            <div class="text-muted small">Siap Disahkan LPPM</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm" style="border-left: 3px solid #00b8d4 !important;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-info text-white avatar">
                                <i class="ti ti-clock fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium text-info">{{ $this->stats['waiting_dekan'] }} Laporan</div>
                            <div class="text-muted small">Menunggu Dekan</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm" style="border-left: 3px solid #2fb344 !important;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-success text-white avatar">
                                <i class="ti ti-check-double fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium text-success">{{ $this->stats['approved_lppm'] }} Laporan</div>
                            <div class="text-muted small">Sudah Disahkan LPPM</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="mb-3 row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <input type="text" class="form-control" placeholder="Cari berdasarkan judul proposal atau peneliti..."
                                wire:model.live.debounce.300ms="search" />
                        </div>

                        <div class="col-md-3">
                            <select class="form-select" wire:model.live="statusFilter">
                                <option value="all">Semua Status Pelaporan</option>
                                <option value="ready">Siap Disahkan LPPM (Disetujui Dekan)</option>
                                <option value="waiting_dekan">Menunggu Persetujuan Dekan</option>
                                <option value="approved">Sudah Disahkan LPPM</option>
                                <option value="revision">Perlu Revisi</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <select class="form-select" wire:model.live="typeFilter">
                                <option value="all">Semua Jenis</option>
                                <option value="research">Penelitian</option>
                                <option value="community_service">Pengabdian</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <button type="button" class="btn btn-outline-secondary w-100" wire:click="resetFilters">
                                <x-lucide-rotate-ccw class="icon me-1" />
                                Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reports Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="card-table table table-vcenter table-hover">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3">Judul Proposal</th>
                        <th>Jenis</th>
                        <th>Pengusul</th>
                        <th class="text-center">Status</th>
                        <th>Tgl Diajukan</th>
                        <th class="w-1 text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->reports as $report)
                        <tr wire:key="report-{{ $report->id }}">
                            <td class="text-wrap ps-3">
                                <div class="text-reset fw-bold">{{ $report->proposal->title }}</div>
                                <div class="mt-1">
                                    <x-tabler.badge variant="outline" class="text-uppercase" style="font-size: 0.65rem;">
                                        Laporan Akhir
                                    </x-tabler.badge>
                                    @if ($report->proposal->researchScheme)
                                        <span class="badge bg-secondary-lt ms-1" style="font-size: 0.65rem;">
                                            {{ $report->proposal->researchScheme->name }}
                                        </span>
                                    @elseif ($report->proposal->communityServiceScheme)
                                        <span class="badge bg-secondary-lt ms-1" style="font-size: 0.65rem;">
                                            {{ $report->proposal->communityServiceScheme->name }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if ($report->proposal->detailable_type === 'App\Models\Research')
                                    <span class="badge bg-blue-lt">Penelitian</span>
                                @else
                                    <span class="badge bg-green-lt">Pengabdian</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $report->proposal->submitter->name }}</div>
                                <div class="small text-secondary">
                                    {{ $report->proposal->submitter->identity?->studyProgram?->name ?? '—' }}
                                    ({{ $report->proposal->submitter->identity?->faculty?->name ?? '—' }})
                                </div>
                            </td>
                            <td class="text-center">
                                @if($report->status === \App\Enums\ReportStatus::APPROVED_BY_DEKAN)
                                    <span class="badge bg-purple text-white fw-bold px-2 py-1 shadow-sm">
                                        <i class="ti ti-check me-1"></i>Siap Disahkan LPPM
                                    </span>
                                @elseif($report->status === \App\Enums\ReportStatus::SUBMITTED)
                                    <span class="badge bg-info text-white fw-bold px-2 py-1 shadow-sm">
                                        <i class="ti ti-clock me-1"></i>Menunggu Dekan
                                    </span>
                                @elseif($report->status === \App\Enums\ReportStatus::APPROVED)
                                    <span class="badge bg-success text-white fw-bold px-2 py-1 shadow-sm">
                                        <i class="ti ti-check-double me-1"></i>Sudah Disahkan
                                    </span>
                                @elseif($report->status === \App\Enums\ReportStatus::REJECTED)
                                    <span class="badge bg-danger text-white fw-bold px-2 py-1 shadow-sm">
                                        <i class="ti ti-alert-circle me-1"></i>Perlu Revisi
                                    </span>
                                @else
                                    <span class="badge bg-secondary text-white fw-bold px-2 py-1">
                                        {{ $report->status->label() }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="text-secondary small">
                                    {{ $report->updated_at?->format('d M Y H:i') ?? '—' }}
                                </div>
                            </td>
                            <td class="text-center pe-3">
                                @php
                                    $showRoute = $report->proposal->detailable_type === 'App\Models\Research'
                                        ? route('research.final-report.show', $report->proposal)
                                        : route('community-service.final-report.show', $report->proposal);
                                @endphp
                                <a href="{{ $showRoute }}" class="btn btn-sm btn-primary" wire:navigate.hover>
                                    <x-lucide-eye class="icon me-1" />
                                    Tinjau
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-5 text-center text-muted">
                                <div class="empty bg-transparent">
                                    <div class="empty-icon text-muted opacity-25">
                                        <i class="ti ti-file-off fs-1"></i>
                                    </div>
                                    <p class="empty-title">Tidak ada laporan akhir yang sesuai filter.</p>
                                    <p class="empty-subtitle text-muted">Silakan ubah kata kunci atau filter status pelaporan.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->reports->hasPages())
            <div class="d-flex align-items-center card-footer border-0">
                {{ $this->reports->links() }}
            </div>
        @endif
    </div>
</div>