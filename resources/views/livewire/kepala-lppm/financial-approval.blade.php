<x-slot:title>Persetujuan Laporan Keuangan (LPJ)</x-slot:title>
<x-slot:pageTitle>Persetujuan Laporan Keuangan (LPJ)</x-slot:pageTitle>
<x-slot:pageSubtitle>
    Tinjau rekapitulasi realisasi anggaran, berkas scan tanda tangan basah, dan sahkan laporan pertanggungjawaban (LPJ) penelitian dan pengabdian.
</x-slot:pageSubtitle>

<div>
    <x-tabler.alert />

    <!-- Stat Cards Summary -->
    <div class="row row-cards mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm" style="border-left: 4px solid #206bc4 !important;">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="subheader text-primary fw-bold">Total Usulan Didanai</div>
                        <div class="ms-auto text-primary">
                            <x-lucide-award class="icon" />
                        </div>
                    </div>
                    <div class="h1 mb-1 mt-2">{{ $this->stats['total'] }}</div>
                    <div class="text-secondary small">Proposal aktif berjalan</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm" style="border-left: 4px solid #2fb344 !important;">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="subheader text-success fw-bold">Disahkan LPPM</div>
                        <div class="ms-auto text-success">
                            <x-lucide-check-circle class="icon" />
                        </div>
                    </div>
                    <div class="h1 mb-1 mt-2 text-success">{{ $this->stats['approved'] }}</div>
                    <div class="text-secondary small">{{ $this->stats['progress'] }}% dari total usulan</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm" style="border-left: 4px solid #f59f00 !important;">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="subheader text-warning fw-bold">Menunggu Pengesahan</div>
                        <div class="ms-auto text-warning">
                            <x-lucide-clock class="icon" />
                        </div>
                    </div>
                    <div class="h1 mb-1 mt-2 text-warning">{{ $this->stats['pending'] }}</div>
                    <div class="text-secondary small">Berkas scan / catatan siap disahkan</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm" style="border-left: 4px solid #6c757d !important;">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="subheader text-secondary fw-bold">Belum Ada Catatan</div>
                        <div class="ms-auto text-secondary">
                            <x-lucide-alert-circle class="icon" />
                        </div>
                    </div>
                    <div class="h1 mb-1 mt-2 text-muted">{{ $this->stats['empty'] }}</div>
                    <div class="text-secondary small">Belum menginput nota / logbook</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-md-5">
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <x-lucide-search class="icon" />
                        </span>
                        <input type="text" class="form-control" placeholder="Cari judul proposal atau nama pengusul..."
                            wire:model.live.debounce.300ms="search" />
                    </div>
                </div>

                <div class="col-md-3">
                    <select class="form-select" wire:model.live="typeFilter">
                        <option value="all">Semua Jenis (Penelitian & PKM)</option>
                        <option value="research">Penelitian</option>
                        <option value="community_service">Pengabdian (PKM)</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <select class="form-select" wire:model.live="statusFilter">
                        <option value="all">Semua Status LPJ</option>
                        <option value="pending">Menunggu Pengesahan LPPM</option>
                        <option value="approved">Sudah Disahkan LPPM</option>
                        <option value="empty">Belum Ada Catatan</option>
                    </select>
                </div>

                <div class="col-md-1">
                    <button type="button" class="btn btn-outline-secondary w-100" wire:click="resetFilters" title="Reset Filter">
                        <x-lucide-rotate-ccw class="icon" />
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Table of Proposals -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        <th style="min-width: 260px;">Judul Proposal & Pengusul</th>
                        <th class="w-1 text-center">Jenis</th>
                        <th style="min-width: 170px;">Realisasi Belanja</th>
                        <th style="min-width: 150px;">Berkas Scan Basah</th>
                        <th style="min-width: 140px;">Status LPJ</th>
                        <th class="w-1 text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->proposals as $proposal)
                        @php
                            $totalBudget = (float) $proposal->budgetItems->sum('total_price');
                            $usedBudget = (float) $proposal->dailyNotes->sum('amount');
                            $budgetPct = $totalBudget > 0 ? round(($usedBudget / $totalBudget) * 100, 1) : 0;
                            $isApproved = $proposal->logbook_approved_at !== null;
                            $scanMedia = $proposal->getFirstMedia('logbook_approval_file');

                            $dailyNoteRoute = $proposal->detailable_type === 'App\Models\Research'
                                ? route('research.daily-note.show', $proposal)
                                : route('community-service.daily-note.show', $proposal);

                            $isPkm = $proposal->detailable_type === 'App\Models\CommunityService';
                        @endphp
                        <tr wire:key="prop-{{ $proposal->id }}">
                            <td>
                                <a href="{{ $dailyNoteRoute }}" class="text-reset fw-bold d-block text-truncate" style="max-width: 380px;" title="{{ $proposal->title }}">
                                    {{ $proposal->title }}
                                </a>
                                <div class="small text-secondary mt-1">
                                    <span class="fw-medium text-dark">{{ $proposal->submitter->name }}</span>
                                    &bull;
                                    {{ $proposal->submitter->identity?->studyProgram?->name ?? '—' }}
                                </div>
                                <div class="small text-muted mt-1">
                                    <span class="badge bg-light text-secondary border">
                                        {{ $proposal->researchScheme?->name ?? $proposal->communityServiceScheme?->name ?? 'Skema Mandiri' }}
                                    </span>
                                </div>
                            </td>
                            <td class="text-center">
                                @if ($isPkm)
                                    <span class="badge bg-green-lt">Pengabdian</span>
                                @else
                                    <span class="badge bg-blue-lt">Penelitian</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="small fw-bold">Rp {{ number_format($usedBudget, 0, ',', '.') }}</span>
                                    <span class="small text-muted">{{ $budgetPct }}%</span>
                                </div>
                                <div class="progress progress-xs">
                                    <div class="progress-bar {{ $budgetPct >= 100 ? 'bg-success' : ($budgetPct >= 70 ? 'bg-primary' : 'bg-warning') }}"
                                         style="width: {{ min($budgetPct, 100) }}%"></div>
                                </div>
                                <div class="small text-muted mt-1" style="font-size: 0.72rem;">
                                    Pagu: Rp {{ number_format($totalBudget, 0, ',', '.') }}
                                </div>
                            </td>
                            <td>
                                @if ($scanMedia)
                                    <a href="{{ route('media.download', ['media' => $scanMedia, 'view' => 1]) }}" target="_blank" class="btn btn-sm btn-outline-info text-truncate" style="max-width: 150px;">
                                        <x-lucide-file-text class="icon icon-sm me-1 text-primary" />
                                        Scan LPJ PDF
                                    </a>
                                    <div class="small text-muted mt-1" style="font-size: 0.72rem;">
                                        {{ $scanMedia->created_at?->format('d/m/Y H:i') }}
                                    </div>
                                @else
                                    <span class="badge bg-secondary-lt">
                                        <x-lucide-file-x class="icon icon-sm me-1 text-secondary" />
                                        Belum Ada Scan
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($isApproved)
                                    <span class="badge bg-success-lt d-inline-flex align-items-center gap-1">
                                        <x-lucide-check-circle class="icon icon-sm text-success" />
                                        Disahkan LPPM
                                    </span>
                                    <div class="small text-muted mt-1" style="font-size: 0.72rem;">
                                        {{ \Carbon\Carbon::parse($proposal->logbook_approved_at)->format('d/m/Y H:i') }}
                                    </div>
                                @elseif ($scanMedia)
                                    <span class="badge bg-warning-lt d-inline-flex align-items-center gap-1">
                                        <x-lucide-clock class="icon icon-sm text-warning" />
                                        Menunggu Pengesahan
                                    </span>
                                @elseif ($proposal->dailyNotes->count() > 0)
                                    <span class="badge bg-info-lt d-inline-flex align-items-center gap-1">
                                        <x-lucide-edit-3 class="icon icon-sm text-info" />
                                        {{ $proposal->dailyNotes->count() }} Catatan
                                    </span>
                                @else
                                    <span class="badge bg-secondary-lt">Belum Diisi</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="btn-list flex-nowrap justify-content-end">
                                    <a href="{{ $dailyNoteRoute }}" class="btn btn-sm btn-outline-secondary" title="Tinjau Rincian Logbook & Nota">
                                        <x-lucide-eye class="icon icon-sm me-1" />
                                        Tinjau
                                    </a>

                                    @if ($isApproved)
                                        <button type="button" wire:click="unapproveLpj({{ $proposal->id }})" class="btn btn-sm btn-outline-danger"
                                            wire:confirm="Yakin ingin membatalkan pengesahan LPJ proposal ini? Dosen akan dapat merevisi kembali berkas/catatan."
                                            wire:loading.attr="disabled">
                                            <x-lucide-rotate-ccw class="icon icon-sm me-1" />
                                            Batal Sahkan
                                        </button>
                                    @else
                                        <button type="button" wire:click="approveLpj({{ $proposal->id }})" class="btn btn-sm btn-success"
                                            wire:confirm="Sahkan Laporan Keuangan (LPJ) untuk proposal ini?"
                                            wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="approveLpj({{ $proposal->id }})">
                                                <x-lucide-check class="icon icon-sm me-1" />
                                                Sahkan LPJ
                                            </span>
                                            <span wire:loading wire:target="approveLpj({{ $proposal->id }})">
                                                <span class="spinner-border spinner-border-sm"></span>
                                            </span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center">
                                <div class="mb-3">
                                    <x-lucide-inbox class="text-secondary icon icon-lg" />
                                </div>
                                <p class="text-secondary mb-0">Tidak ada data usulan / LPJ yang cocok dengan filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->proposals->hasPages())
            <div class="d-flex align-items-center card-footer border-0">
                {{ $this->proposals->links() }}
            </div>
        @endif
    </div>
</div>
