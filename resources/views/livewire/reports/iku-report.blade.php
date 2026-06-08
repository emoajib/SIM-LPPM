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
        @if(active_role() === 'kepala lppm' || active_role() === 'rektor')
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

                        @if(active_role() === 'kepala lppm' && (!$institutionalReport || in_array($institutionalReport->status, [\App\Enums\InstitutionalReportStatus::DRAFT, \App\Enums\InstitutionalReportStatus::REJECTED])))
                            <button class="btn btn-primary shadow-sm"
                                wire:click="submitInstitutionalReport('iku', {{ $period }}, {{ json_encode($currentFilters) }})"
                                wire:loading.attr="disabled">
                                <i class="ti ti-send me-2"></i>
                                Ajukan ke Rektor
                            </button>
                        @endif

                        @if(active_role() === 'rektor' && ($institutionalReport?->status === \App\Enums\InstitutionalReportStatus::SUBMITTED))
                            <button class="btn btn-outline-danger shadow-sm" data-bs-toggle="modal"
                                data-bs-target="#modal-reject-institutional">
                                <i class="ti ti-x me-2"></i>
                                Minta Perbaikan
                            </button>
                            <button class="btn btn-success shadow-sm" wire:click="approveInstitutionalReport('iku', {{ $period }})"
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
            <div class="card shadow-sm border-0 mb-4 animate__animated animate__fadeIn">
                <div class="card-header bg-light-lt d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title fw-bold text-primary">Detail Capaian {{ $selectedIku }}</h3>
                        <p class="text-muted small mb-0">{{ $ikuMetrics[strtolower($selectedIku)]['description'] }}</p>
                    </div>
                    <button class="btn-close" wire:click="toggleDetails(null)"></button>
                </div>
                <div class="p-0 card-body">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-hover">
                            @if($selectedIku == 'IKU4')
                                <thead>
                                    <tr>
                                        <th>Nama Dosen</th>
                                        <th>NIDN/NIP</th>
                                        <th>Scopus ID</th>
                                        <th>SINTA ID</th>
                                        <th>WOS ID</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($ikuDetails as $detail)
                                        <tr>
                                            <td class="fw-bold">{{ $detail['name'] }}</td>
                                            <td class="text-muted">{{ $detail['id_number'] }}</td>
                                            <td><span class="badge bg-blue-lt">{{ $detail['scopus'] ?: '-' }}</span></td>
                                            <td><span class="badge bg-green-lt">{{ $detail['sinta'] ?: '-' }}</span></td>
                                            <td><span class="badge bg-purple-lt">{{ $detail['wos'] ?: '-' }}</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">Data tidak ditemukan</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            @elseif($selectedIku == 'IKU5')
                                <thead>
                                    <tr>
                                        <th>Judul Kegiatan</th>
                                        <th>Ketua</th>
                                        <th>Mitra</th>
                                        <th class="text-center">Bobot</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($ikuDetails as $detail)
                                        <tr>
                                            <td class="text-wrap" style="max-width: 300px;">{{ $detail['title'] }}</td>
                                            <td class="small">{{ $detail['submitter'] }}</td>
                                            <td class="small">{{ $detail['partners'] }}</td>
                                            <td class="text-center"><span
                                                    class="badge bg-primary-lt">{{ number_format($detail['weight'], 1) }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">Data tidak ditemukan</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            @elseif($selectedIku == 'IKU6')
                                <thead>
                                    <tr>
                                        <th>Judul Artikel</th>
                                        <th>Jurnal / Penerbit</th>
                                        <th>Ranking</th>
                                        <th>Indeks</th>
                                        <th class="text-center">Skor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($ikuDetails as $detail)
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
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">Data tidak ditemukan</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            @elseif($selectedIku == 'IKU7')
                                <thead>
                                    <tr>
                                        <th>Judul Penelitian/PKM</th>
                                        <th>Ketua Pengusul</th>
                                        <th>SDGs yang Didukung</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($ikuDetails as $detail)
                                        <tr>
                                            <td class="text-wrap" style="max-width: 400px;">{{ $detail['title'] }}</td>
                                            <td>{{ $detail['submitter'] }}</td>
                                            <td><span class="text-wrap badge bg-teal-lt">{{ $detail['sdgs'] }}</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted">Data tidak ditemukan</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            @elseif($selectedIku == 'IKU8')
                                <thead>
                                    <tr>
                                        <th>Nama Dosen</th>
                                        <th>Keterlibatan Kebijakan / Rekognisi Pakar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($ikuDetails as $detail)
                                        <tr>
                                            <td class="fw-bold">{{ $detail['name'] }}</td>
                                            <td class="text-wrap">{{ $detail['policies'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center py-4 text-muted">Data tidak ditemukan</td>
                                        </tr>
                                    @endforelse
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

    @if(active_role() === 'rektor')
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
                            wire:click="rejectInstitutionalReport('iku', '{{ $period }}')">
                            Simpan & Tolak
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>