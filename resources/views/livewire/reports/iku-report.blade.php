<div>
    <x-slot:pageHeader>
        {{-- Header empty as requested by general style --}}
    </x-slot:pageHeader>

    <x-slot:pageActions>
        <div class="btn-list">
            <a href="{{ route('admin.iku.export-pdf', ['period' => $period, 'search' => $search, 'preview' => 1]) }}"
                class="btn btn-outline-info shadow-sm" target="_blank" title="Tinjau PDF">
                <i class="ti ti-eye me-2"></i>
                <span>{{ __('Tinjau PDF') }}</span>
            </a>
            <a href="{{ route('admin.iku.export-excel', ['period' => $period, 'search' => $search]) }}"
                class="btn btn-outline-success shadow-sm" data-navigate-ignore="true" title="Unduh Excel">
                <i class="ti ti-table me-2"></i>
                <span>{{ __('Unduh Excel') }}</span>
            </a>
            <a href="{{ route('admin.iku.export-pdf', ['period' => $period, 'search' => $search]) }}"
                class="btn btn-outline-danger shadow-sm" data-navigate-ignore="true" title="Unduh PDF">
                <i class="ti ti-file-type-pdf me-2"></i>
                <span>{{ __('Unduh PDF') }}</span>
            </a>
        </div>
    </x-slot:pageActions>

    <div class="mt-3">
        <!-- Filter Bar (Support System) -->
        <div class="card mb-3 shadow-sm border-0 glass-card">
            <div class="card-body p-3">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-primary"></i>
                            </span>
                            <input type="text" wire:model.live.debounce.500ms="search" class="form-control"
                                placeholder="Cari data detail IKU (Nama, Judul, dll)...">
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="dropdown">
                            <button class="btn btn-white dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                                <i class="ti ti-calendar-event me-2 text-primary"></i>
                                Periode: <span class="fw-bold ms-1">{{ $period }}</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                                @foreach ($periods as $p)
                                    <button type="button" class="dropdown-item @if($p == $period) active @endif"
                                        wire:click="setPeriod('{{ $p }}')">
                                        {{ $p }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-icon btn-white shadow-sm" wire:click="resetFilters" title="Reset Filter">
                            <i class="ti ti-refresh text-secondary"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @if(active_role_is('kepala lppm') || active_role_is('rektor'))
            <div class="card mb-3 border-primary shadow-sm glass-card">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="d-flex align-items-center mb-1">
                            <h3 class="card-title h3 mb-0 me-2 text-primary">Validasi Dokumen Institusi (IKU)</h3>
                            @if($institutionalReport)
                                <span class="badge bg-{{ $institutionalReport->status->color() }}-lt">
                                    {{ $institutionalReport->status->label() }}
                                </span>
                            @else
                                <span class="badge bg-secondary-lt">Belum Diajukan</span>
                            @endif
                        </div>
                        <p class="text-secondary mb-0 small">
                            @if(!$institutionalReport || $institutionalReport->status === \App\Enums\InstitutionalReportStatus::DRAFT)
                                Rekapitulasi IKU periode {{ $period }} belum diajukan ke Rektor.
                            @elseif($institutionalReport->status === \App\Enums\InstitutionalReportStatus::SUBMITTED)
                                Menunggu persetujuan dan tanda tangan digital Rektor.
                            @elseif($institutionalReport->status === \App\Enums\InstitutionalReportStatus::APPROVED)
                                Telah disahkan Rektor pada {{ $institutionalReport->approved_at->format('d M Y H:i') }}.
                            @elseif($institutionalReport->status === \App\Enums\InstitutionalReportStatus::REJECTED)
                                Perbaikan: <strong>{{ $institutionalReport->notes }}</strong>
                            @endif
                        </p>
                    </div>
                    <div class="btn-list">
                        @php
                            $currentFilters = ['search' => $search, 'period' => $period];
                        @endphp

                        <!-- Draft Preview Icon (Support System) -->
                        <a href="{{ route('admin.iku.export-pdf', array_merge($currentFilters, ['preview' => 1])) }}"
                            target="_blank" class="btn btn-outline-primary shadow-sm" title="Tinjau Draft PDF">
                            <i class="ti ti-eye me-2"></i> Tinjau PDF
                        </a>

                        @if(active_role_is('kepala lppm') && (!$institutionalReport || in_array($institutionalReport->status, [\App\Enums\InstitutionalReportStatus::DRAFT, \App\Enums\InstitutionalReportStatus::REJECTED])))
                            <button class="btn btn-primary shadow-sm"
                                wire:click="submitInstitutionalReport('iku', {{ $this->institutionalYear() }}, {{ json_encode($currentFilters) }})"
                                wire:loading.attr="disabled">
                                <i class="ti ti-send me-2"></i>
                                Ajukan ke Rektor
                            </button>
                        @endif

                        @if(active_role_is('rektor') && ($institutionalReport?->status === \App\Enums\InstitutionalReportStatus::SUBMITTED))
                            <button class="btn btn-outline-danger shadow-sm" data-bs-toggle="modal"
                                data-bs-target="#modal-reject-institutional">
                                <i class="ti ti-x me-2"></i>
                                Minta Perbaikan
                            </button>
                            <button class="btn btn-success shadow-sm" wire:click="approveInstitutionalReport('iku', {{ $this->institutionalYear() }})"
                                wire:loading.attr="disabled">
                                <i class="ti ti-circle-check me-2"></i>
                                Setujui & Tanda Tangani
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- IKU Metrics KPI Cards -->
        <div class="mb-4 row row-deck g-3">
            @foreach ($ikuMetrics as $key => $metric)
                <div class="col-sm-6 col-md-4 col-xl-2.4" style="width: 20%">
                    <div class="card @if($selectedIku == strtoupper($key)) border-primary shadow-md @else shadow-sm @endif border-0 transition-all cursor-pointer"
                        wire:click="toggleDetails('{{ strtoupper($key) }}')">
                        <div class="p-3 card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="badge bg-primary-lt">{{ $metric['code'] }}</div>
                                @if($metric['achievement'] >= $metric['target'])
                                    <i class="ti ti-circle-check text-success"></i>
                                @else
                                    <i class="ti ti-circle-x text-warning"></i>
                                @endif
                            </div>
                            <div class="text-secondary small fw-medium mb-1 text-truncate" title="{{ $metric['name'] }}">
                                {{ $metric['name'] }}
                            </div>
                            <div class="d-flex align-items-baseline gap-1">
                                <h3 class="mb-0 fw-bold">{{ number_format($metric['achievement'], 1) }}%</h3>
                                <span class="text-muted small">/ {{ number_format($metric['target'], 0) }}%</span>
                            </div>
                            <div class="progress progress-xs mt-2">
                                <div class="progress-bar {{ $metric['achievement'] >= $metric['target'] ? 'bg-success' : 'bg-warning' }}"
                                    style="width: {{ min(100, $metric['achievement']) }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($selectedIku)
            @php
                $pendingDetails = collect($ikuDetails)->where('is_achieved', false);
                $achievedDetails = collect($ikuDetails)->where('is_achieved', true);
                $visibleDetails = $onlyPending ? $pendingDetails : collect($ikuDetails);
            @endphp
            <div class="card shadow-sm border-0 mb-4 animate__animated animate__fadeIn">
                <div class="card-header bg-light-lt d-flex justify-content-between align-items-center">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h3 class="card-title fw-bold text-primary mb-0">Detail Capaian {{ $selectedIku }}</h3>
                            @if($pendingDetails->isNotEmpty())
                                <span class="badge bg-warning-lt">{{ $pendingDetails->count() }} belum tercapai</span>
                            @endif
                        </div>
                        <p class="text-muted small mb-0 mt-1">{{ $ikuMetrics[strtolower($selectedIku)]['description'] ?? '' }}</p>
                        <p class="text-secondary small mb-0">
                            {{ $achievedDetails->count() }} tercapai dari {{ count($ikuDetails) }} data.
                            @if($pendingDetails->isNotEmpty())
                                Baris berstatus <span class="badge bg-warning-lt">Belum</span> adalah data yang perlu dilengkapi/diverifikasi.
                            @endif
                        </p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="iku-only-pending"
                                wire:model.live="onlyPending">
                            <label class="form-check-label small" for="iku-only-pending">Hanya yang belum tercapai</label>
                        </div>
                        <button class="btn-close" wire:click="toggleDetails(null)"></button>
                    </div>
                </div>
                <div class="p-0 card-body">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-hover">
                            @if($selectedIku === 'IKU04')
                                <thead>
                                    <tr>
                                        <th>Nama Dosen</th>
                                        <th>NIDN/NIP</th>
                                        <th>Scopus ID</th>
                                        <th>SINTA ID</th>
                                        <th>WOS ID</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($visibleDetails as $detail)
                                        <tr>
                                            <td class="fw-bold">{{ $detail['name'] }}</td>
                                            <td class="text-muted">{{ $detail['id_number'] }}</td>
                                            <td><span class="badge bg-blue-lt">{{ $detail['scopus'] ?: '-' }}</span></td>
                                            <td><span class="badge bg-green-lt">{{ $detail['sinta'] ?: '-' }}</span></td>
                                            <td><span class="badge bg-purple-lt">{{ $detail['wos'] ?: '-' }}</span></td>
                                            <td>
                                                <span
                                                    class="badge bg-{{ $detail['is_achieved'] ? 'success' : 'warning' }}-lt">{{ $detail['status'] }}</span>
                                                @if(! $detail['is_achieved'])
                                                    <div class="text-muted xsmall">{{ $detail['note'] }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">{{ $onlyPending ? 'Semua data sudah tercapai.' : 'Data tidak ditemukan' }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            @elseif($selectedIku === 'IKU05')
                                <thead>
                                    <tr>
                                        <th>Judul Kegiatan</th>
                                        <th>Ketua</th>
                                        <th>Mitra</th>
                                        <th class="text-center">Bobot</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($visibleDetails as $detail)
                                        <tr>
                                            <td class="text-wrap" style="max-width: 300px;">{{ $detail['title'] }}</td>
                                            <td class="small">{{ $detail['submitter'] }}</td>
                                            <td class="small">{{ $detail['partners'] }}</td>
                                            <td class="text-center"><span
                                                    class="badge bg-primary-lt">{{ number_format($detail['weight'], 1) }}</span>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-{{ $detail['is_achieved'] ? 'success' : 'warning' }}-lt">{{ $detail['status'] }}</span>
                                                @if(! $detail['is_achieved'])
                                                    <div class="text-muted xsmall">{{ $detail['note'] }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">{{ $onlyPending ? 'Semua data sudah tercapai.' : 'Data tidak ditemukan' }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            @elseif($selectedIku === 'IKU06')
                                <thead>
                                    <tr>
                                        <th>Judul Artikel</th>
                                        <th>Jurnal / Penerbit</th>
                                        <th>Ranking</th>
                                        <th>Indeks</th>
                                        <th class="text-center">Skor</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($visibleDetails as $detail)
                                        <tr>
                                            <td class="text-wrap" style="max-width: 300px;">
                                                <div class="fw-bold">{{ $detail['title'] }}</div>
                                                <div class="text-muted xsmall">{{ $detail['proposal'] }}</div>
                                            </td>
                                            <td class="small">{{ $detail['journal'] }}</td>
                                            <td class="text-center"><span class="badge bg-azure-lt">{{ $detail['rank'] }}</span>
                                            </td>
                                            <td><span class="badge bg-indigo-lt">{{ $detail['indexing'] }}</span></td>
                                            <td class="text-center fw-bold">{{ number_format($detail['weight'], 1) }}</td>
                                            <td>
                                                <span
                                                    class="badge bg-{{ $detail['is_achieved'] ? 'success' : 'warning' }}-lt">{{ $detail['status'] }}</span>
                                                @if(! $detail['is_achieved'])
                                                    <div class="text-muted xsmall">{{ $detail['note'] }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">{{ $onlyPending ? 'Semua data sudah tercapai.' : 'Data tidak ditemukan' }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            @elseif($selectedIku === 'IKU07')
                                <thead>
                                    <tr>
                                        <th>Judul Penelitian/PKM</th>
                                        <th>Ketua Pengusul</th>
                                        <th>SDGs yang Didukung</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($visibleDetails as $detail)
                                        <tr>
                                            <td class="text-wrap" style="max-width: 400px;">{{ $detail['title'] }}</td>
                                            <td>{{ $detail['submitter'] }}</td>
                                            <td><span class="text-wrap badge bg-teal-lt">{{ $detail['sdgs'] }}</span></td>
                                            <td>
                                                <span
                                                    class="badge bg-{{ $detail['is_achieved'] ? 'success' : 'warning' }}-lt">{{ $detail['status'] }}</span>
                                                @if(! $detail['is_achieved'])
                                                    <div class="text-muted xsmall">{{ $detail['note'] }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">{{ $onlyPending ? 'Semua data sudah tercapai.' : 'Data tidak ditemukan' }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            @elseif($selectedIku === 'IKU08')
                                <thead>
                                    <tr>
                                        <th>Nama Dosen</th>
                                        <th>Keterlibatan Kebijakan / Rekognisi Pakar</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($visibleDetails as $detail)
                                        <tr>
                                            <td class="fw-bold">{{ $detail['name'] }}</td>
                                            <td class="text-wrap">{{ $detail['policies'] }}</td>
                                            <td>
                                                <span
                                                    class="badge bg-{{ $detail['is_achieved'] ? 'success' : 'warning' }}-lt">{{ $detail['status'] }}</span>
                                                @if(! $detail['is_achieved'])
                                                    <div class="text-muted xsmall">{{ $detail['note'] }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted">{{ $onlyPending ? 'Semua data sudah tercapai.' : 'Data tidak ditemukan' }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            @else
                                <tbody>
                                    <tr>
                                        <td class="text-center py-4 text-muted">Rincian data belum tersedia untuk
                                            {{ $selectedIku }}.</td>
                                    </tr>
                                </tbody>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <div class="card shadow-sm border-0">
            <div class="card-header border-0 bg-white">
                <h3 class="card-title fw-bold">Ringkasan Tabel Capaian IKU — {{ $period }}</h3>
            </div>
            <div class="p-0 card-body">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover">
                        <thead>
                            <tr>
                                <th class="w-1">Kode</th>
                                <th>Indikator Kinerja Utama</th>
                                <th class="text-center">Target Dasar</th>
                                <th class="text-center">Realisasi</th>
                                <th class="text-center">Status</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ikuMetrics as $key => $metric)
                                <tr class="cursor-pointer" wire:click="toggleDetails('{{ strtoupper($key) }}')">
                                    <td class="text-muted fw-bold">{{ $metric['code'] }}</td>
                                    <td>
                                        <div class="font-weight-medium">{{ $metric['name'] }}</div>
                                        <div class="text-muted small text-truncate" style="max-width: 400px;">
                                            {{ $metric['description'] }}
                                        </div>
                                    </td>
                                    <td class="text-center fw-bold text-azure">{{ number_format($metric['target'], 1) }}%
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-2">
                                            <div class="fw-bold font-lg">{{ number_format($metric['achievement'], 1) }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($metric['achievement'] >= $metric['target'])
                                            <span class="badge bg-success-lt p-2">
                                                <i class="ti ti-check me-1"></i> Tercapai
                                            </span>
                                        @else
                                            <span class="badge bg-warning-lt p-2">
                                                <i class="ti ti-alert-triangle me-1"></i> Belum
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-icon btn-sm btn-ghost-primary">
                                            <i class="ti ti-chevron-right"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if(active_role_is('rektor'))
        <div class="modal modal-blur fade" id="modal-reject-institutional" tabindex="-1" role="dialog" aria-hidden="true"
            wire:ignore.self>
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Alasan Penolakan / Permintaan Perbaikan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div>
                            <label class="form-label">Catatan untuk Kepala LPPM</label>
                            <textarea class="form-control" wire:model="approvalNotes" rows="3"
                                placeholder="Masukkan alasan atau instruksi perbaikan..."></textarea>
                            @error('approvalNotes') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary me-auto"
                            data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-danger shadow-sm"
                            wire:click="rejectInstitutionalReport('iku', '{{ $this->institutionalYear() }}')">
                            Simpan & Tolak
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>