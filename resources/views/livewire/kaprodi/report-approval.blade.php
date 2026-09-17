{{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
<x-slot:title>Monitoring Laporan Akhir (Kaprodi)</x-slot:title>
<x-slot:pageTitle>Monitoring Laporan Akhir</x-slot:pageTitle>
<x-slot:pageSubtitle>
    Pantau status laporan akhir penelitian dan pengabdian dari program studi Anda.
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
                            <span class="bg-primary text-white avatar"><i class="ti ti-file-text fs-2"></i></span>
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
                            <span class="bg-purple text-white avatar"><i class="ti ti-check fs-2"></i></span>
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
                            <span class="bg-info text-white avatar"><i class="ti ti-clock fs-2"></i></span>
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
                            <span class="bg-success text-white avatar"><i class="ti ti-check-double fs-2"></i></span>
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

    <!-- Alert: Belum Laporan -->
    @if ($this->stats['belum_laporan'] > 0)
        <div class="row row-cards mb-3">
            <div class="col-12">
                <div class="alert alert-warning d-flex align-items-center gap-2 shadow-sm border-0 mb-0" role="alert">
                    <i class="ti ti-alert-triangle fs-3 text-warning"></i>
                    <div>
                        <strong>{{ $this->stats['belum_laporan'] }} proposal</strong>
                        dari prodi Anda sudah disetujui/selesai namun <strong>belum mengajukan laporan akhir</strong>.
                        <button class="btn btn-sm btn-warning ms-2" wire:click="$set('statusFilter', 'belum_laporan')">
                            Lihat Daftar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Filter -->
    <div class="mb-3 row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" placeholder="Cari berdasarkan judul proposal..."
                                wire:model.live.debounce.300ms="search" />
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" wire:model.live="statusFilter">
                                <option value="all">Semua Status Pelaporan</option>
                                <option value="belum_laporan">⚠ Belum Laporan</option>
                                <option value="ready">Siap Disahkan LPPM</option>
                                <option value="waiting_dekan">Menunggu Persetujuan Dekan</option>
                                <option value="approved">Sudah Disahkan LPPM</option>
                                <option value="revision">Perlu Revisi</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" wire:model.live="typeFilter">
                                <option value="all">Semua Jenis</option>
                                <option value="research">Penelitian</option>
                                <option value="community_service">Pengabdian</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-outline-secondary w-100" wire:click="resetFilters">
                                <x-lucide-rotate-ccw class="icon me-1" /> Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    @include('livewire.partials.report-approval-table', ['role' => 'kaprodi'])
</div>
