<div>
    <x-slot:pageHeader>
        {{-- empty --}}
    </x-slot:pageHeader>

    <x-slot:pageActions>
        <div class="btn-list">
            <a href="{{ route('export.monev.recap', ['academic_year' => $period, 'semester' => $selectedSemester]) }}"
               class="btn btn-outline-success shadow-sm" data-navigate-ignore="true">
                <i class="ti ti-file-spreadsheet me-1"></i> Ekspor Excel
            </a>
            <a href="{{ route('reports.monev.pdf', ['academic_year' => $period, 'semester' => $selectedSemester, 'preview' => 1]) }}"
               class="btn btn-outline-info shadow-sm" target="_blank">
                <i class="ti ti-file-search me-1"></i> Tinjau PDF
            </a>
            <a href="{{ route('reports.monev.pdf', ['academic_year' => $period, 'semester' => $selectedSemester]) }}"
               class="btn btn-info shadow-sm" data-navigate-ignore="true">
                <i class="ti ti-file-type-pdf me-1"></i> Unduh PDF
            </a>
        </div>
    </x-slot:pageActions>

    <div class="mt-3">

        {{-- ── FILTER BAR ── --}}
        <div class="card mb-3 shadow-sm border-0">
            <div class="card-body p-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-search text-primary"></i></span>
                            <input type="text" wire:model.live.debounce.400ms="search"
                                   class="form-control" placeholder="Cari judul, pengusul, atau reviewer...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select wire:model.live="period" class="form-select">
                            @foreach($this->availablePeriods as $p)
                                <option value="{{ $p }}">Periode {{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select wire:model.live="selectedSemester" class="form-select">
                            <option value="all">Semua Semester</option>
                            <option value="ganjil">Ganjil</option>
                            <option value="genap">Genap</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select wire:model.live="selectedType" class="form-select">
                            <option value="all">Semua Jenis</option>
                            <option value="research">Penelitian</option>
                            <option value="community_service">PKM / Pengabdian</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select wire:model.live="selectedStatus" class="form-select">
                            <option value="all">Semua Status</option>
                            <option value="pending">Belum Direview</option>
                            <option value="reviewed">Sudah Direview</option>
                            <option value="verified">Diverifikasi LPPM</option>
                            <option value="approved">Disahkan Kepala</option>
                        </select>
                    </div>
                    <div class="col-auto ms-auto">
                        <button class="btn btn-icon btn-white shadow-sm" wire:click="resetFilters" title="Reset Filter">
                            <i class="ti ti-refresh text-secondary"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── KPI CARDS ── --}}
        <div class="row row-deck mb-4">
            @foreach($this->summaryMetrics as $card)
                <div class="col-sm-6 col-lg-2">
                    <div class="card shadow-sm border-0 card-stacked">
                        <div class="card-body d-flex align-items-center gap-3 py-3">
                            <div class="rounded-2 p-2 {{ $card['color'] }}" style="width:46px;height:46px;display:flex;align-items:center;justify-content:center;">
                                <i class="ti {{ $card['icon'] }} {{ $card['text'] }} fs-2"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-medium">{{ $card['label'] }}</div>
                                <div class="fs-3 fw-bold {{ $card['text'] }}">{{ $card['value'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ── MONITORING TABLE ── --}}
        <div class="card shadow-sm border-0">
            <div class="card-header border-bottom">
                <h3 class="card-title">
                    <i class="ti ti-list-details me-2 text-primary"></i>
                    Daftar Monev — Periode {{ $period }}
                </h3>
                <div class="card-options">
                    <span class="badge bg-blue-lt">{{ $reviews->total() }} entri</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter table-hover card-table">
                    <thead class="table-light">
                        <tr>
                            <th class="w-1">No</th>
                            <th>Proposal / Pengusul</th>
                            <th>Jenis</th>
                            <th>Reviewer</th>
                            <th class="text-center">Skor</th>
                            <th class="text-center">Rekomendasi</th>
                            <th class="text-center">TTD Reviewer</th>
                            <th class="text-center">Verifikasi LPPM</th>
                            <th class="text-center">Sahkan Kepala</th>
                            <th class="w-1">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reviews as $index => $review)
                            <tr>
                                <td class="text-muted">{{ ($reviews->currentPage() - 1) * $reviews->perPage() + $index + 1 }}</td>
                                <td>
                                    <div class="fw-semibold text-wrap" style="max-width:300px;">
                                        {{ $review->proposal?->title ?? 'N/A' }}
                                    </div>
                                    <div class="text-muted small">
                                        {{ $review->proposal?->submitter?->name ?? '-' }}
                                        &bull; {{ $review->academic_year }}/{{ ucfirst($review->semester) }}
                                    </div>
                                </td>
                                <td>
                                    @if($review->proposal?->detailable_type === \App\Models\Research::class)
                                        <span class="badge bg-blue-lt">Penelitian</span>
                                    @else
                                        <span class="badge bg-green-lt">PKM</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-sm fw-medium">{{ $review->reviewer?->name ?? '-' }}</div>
                                </td>
                                <td class="text-center">
                                    @if($review->score > 0)
                                        @php
                                            $sColor = $review->score >= 80 ? 'bg-success' : ($review->score >= 60 ? 'bg-warning' : 'bg-danger');
                                        @endphp
                                        <span class="badge {{ $sColor }} text-white fw-bold">{{ number_format($review->score, 1) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($review->status)
                                        @php
                                            $rColor = match($review->status) {
                                                'sangat_baik' => 'bg-success-lt text-success',
                                                'baik' => 'bg-blue-lt text-blue',
                                                'cukup' => 'bg-warning-lt text-warning',
                                                default => 'bg-secondary-lt text-secondary',
                                            };
                                            $rLabel = match($review->status) {
                                                'sangat_baik' => 'SANGAT BAIK',
                                                'baik' => 'BAIK',
                                                'cukup' => 'CUKUP',
                                                default => strtoupper($review->status),
                                            };
                                        @endphp
                                        <span class="badge {{ $rColor }} fw-bold">{{ $rLabel }}</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($review->reviewed_at)
                                        <div class="text-success fw-bold">
                                            <i class="ti ti-shield-check me-1"></i>
                                            <div class="small text-muted fw-normal">{{ $review->reviewed_at->format('d/m/Y') }}</div>
                                        </div>
                                    @else
                                        <span class="badge bg-warning-lt text-warning">Pending</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($review->finalized_by_lppm_at)
                                        <div class="text-purple fw-bold">
                                            <i class="ti ti-circle-check me-1"></i>
                                            <div class="small text-muted fw-normal">{{ $review->finalized_by_lppm_at->format('d/m/Y') }}</div>
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($review->approved_by_kepala_at)
                                        <div class="text-green fw-bold">
                                            <i class="ti ti-award me-1"></i>
                                            <div class="small text-muted fw-normal">{{ $review->approved_by_kepala_at->format('d/m/Y') }}</div>
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($review->reviewed_at)
                                        <a href="{{ route('export.monev.ba', $review->id) }}"
                                           target="_blank" class="btn btn-sm btn-icon btn-outline-blue" title="Unduh Berita Acara">
                                            <i class="ti ti-file-type-pdf"></i>
                                        </a>
                                    @else
                                        <button class="btn btn-sm btn-icon btn-outline-secondary" disabled title="Belum direview">
                                            <i class="ti ti-file-off"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="ti ti-mood-empty fs-1 d-block mb-2 text-muted"></i>
                                    Belum ada data monev untuk filter yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($reviews->hasPages())
                <div class="card-footer d-flex align-items-center border-top">
                    {{ $reviews->links() }}
                </div>
            @endif
        </div>

    </div>
</div>
