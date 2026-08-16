<div>
    <x-slot:title>Manajemen Nomor Kontrak Usulan</x-slot:title>
    <x-slot:pageTitle>Manajemen Nomor Kontrak</x-slot:pageTitle>
    <x-slot:pageSubtitle>Pengelolaan nomor dan tanggal kontrak perjanjian penugasan penelitian & pengabdian</x-slot:pageSubtitle>
    <x-slot:pageActions>
        <div class="dropdown">
            <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <x-lucide-wand-2 class="icon me-1" />
                Generate Nomor Massal
                @if(count($selectedProposals) > 0)
                    <span class="badge bg-white text-primary ms-1">{{ count($selectedProposals) }}</span>
                @endif
            </button>
            <div class="dropdown-menu dropdown-menu-end shadow-sm">
                <a href="#" wire:click.prevent="openBatchModal('research')" class="dropdown-item">
                    <x-lucide-microscope class="icon me-2 text-primary" />
                    1. Generate Nomor Kontrak Penelitian
                </a>
                <a href="#" wire:click.prevent="openBatchModal('community-service')" class="dropdown-item">
                    <x-lucide-heart-handshake class="icon me-2 text-success" />
                    2. Generate Nomor Kontrak Pengabdian
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" wire:click.prevent="openBatchModal('all')" class="dropdown-item">
                    <x-lucide-layers class="icon me-2 text-secondary" />
                    Generate Semua Jenis (Otomatis L & PKM)
                </a>
            </div>
        </div>
    </x-slot:pageActions>

    <x-tabler.alert />

    <!-- Stat Cards -->
    <div class="row row-cards mb-3">
        <div class="col-sm-6 col-lg-4">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-primary text-white avatar">
                                <x-lucide-file-spreadsheet class="icon" />
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Total Usulan Didanai</div>
                            <div class="text-secondary">{{ $this->stats['total'] }} Proposal</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-success text-white avatar">
                                <x-lucide-check-circle-2 class="icon" />
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Sudah Ber-nomor Kontrak</div>
                            <div class="text-secondary">{{ $this->stats['has_contract'] }} Proposal</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-warning text-white avatar">
                                <x-lucide-alert-circle class="icon" />
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Belum Ber-nomor Kontrak</div>
                            <div class="text-secondary">{{ $this->stats['missing_contract'] }} Proposal</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Selection Action Banner -->
    @if(count($selectedProposals) > 0)
        <div class="alert alert-primary d-flex align-items-center justify-content-between mb-3 shadow-sm">
            <div class="d-flex align-items-center">
                <x-lucide-check-square class="icon alert-icon me-2 text-primary" />
                <span><strong>{{ count($selectedProposals) }}</strong> usulan telah dipilih untuk tindakan massal.</span>
            </div>
            <div class="d-flex gap-2">
                <button type="button" wire:click="openBatchModal('all')" class="btn btn-sm btn-primary">
                    <x-lucide-wand-2 class="icon me-1" />
                    Generate Nomor Kontrak ({{ count($selectedProposals) }})
                </button>
                <button type="button" wire:click="$set('selectedProposals', [])" class="btn btn-sm btn-outline-secondary">
                    Batal Pilih
                </button>
            </div>
        </div>
    @endif

    <!-- Filter & Table Card -->
    <div class="card shadow-sm">
        <div class="card-header py-3">
            <div class="row g-2 align-items-center w-100">
                <div class="col-md-4">
                    <div class="input-icon">
                        <span class="input-icon-addon"><x-lucide-search class="icon" /></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Cari judul, ketua, NIDN, kontrak...">
                    </div>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="type" class="form-select">
                        <option value="all">Semua Jenis</option>
                        <option value="research">Penelitian</option>
                        <option value="community-service">Pengabdian (PKM)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="statusFilter" class="form-select">
                        <option value="all">Semua Status</option>
                        <option value="missing_contract">Belum Ada Nomor</option>
                        <option value="has_contract">Sudah Ada Nomor</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="year" class="form-select">
                        <option value="">Semua Tahun</option>
                        @foreach($this->availableYears as $yr)
                            <option value="{{ $yr }}">Tahun {{ $yr }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="perPage" class="form-select">
                        <option value="10">10 / halaman</option>
                        <option value="15">15 / halaman</option>
                        <option value="25">25 / halaman</option>
                        <option value="50">50 / halaman</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-vcenter table-hover card-table">
                <thead>
                    <tr>
                        <th class="w-1 text-center">
                            <input type="checkbox" wire:model.live="selectAll" class="form-check-input">
                        </th>
                        <th>Usulan / Proposal</th>
                        <th>Ketua Pengusul</th>
                        <th>Nomor & Tanggal Kontrak</th>
                        <th class="text-end">Anggaran (RAB)</th>
                        <th class="w-1 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($proposals as $proposal)
                        @php
                            $isResearch = $proposal->detailable_type === 'App\Models\Research';
                            $schemeName = $isResearch ? ($proposal->researchScheme?->name ?? 'Penelitian') : ($proposal->communityServiceScheme?->name ?? 'Pengabdian');
                            $totalBudget = $proposal->budgetItems->sum('total_price');
                        @endphp
                        <tr wire:key="prop-row-{{ $proposal->id }}">
                            <td class="text-center">
                                <input type="checkbox" wire:model.live="selectedProposals" value="{{ (string)$proposal->id }}" class="form-check-input">
                            </td>
                            <td>
                                <div class="d-flex align-items-center mb-1">
                                    @if($isResearch)
                                        <span class="badge bg-blue-lt me-2">Penelitian</span>
                                    @else
                                        <span class="badge bg-green-lt me-2">Pengabdian</span>
                                    @endif
                                    <span class="badge bg-secondary-lt">{{ $schemeName }}</span>
                                    <span class="badge bg-light text-muted ms-1">Th. {{ $proposal->start_year ?? $proposal->created_at->year }}</span>
                                </div>
                                <div class="font-weight-medium text-wrap" style="max-width: 420px;">
                                    <a href="{{ $isResearch ? route('research.proposal.show', $proposal) : route('community-service.proposal.show', $proposal) }}" target="_blank" class="text-reset">
                                        {{ $proposal->title }}
                                    </a>
                                </div>
                            </td>
                            <td>
                                <div>{{ $proposal->submitter->name ?? '-' }}</div>
                                <small class="text-muted">
                                    NIDN: {{ $proposal->submitter->identity?->identity_id ?? '-' }}
                                    @if($proposal->submitter->identity?->studyProgram)
                                        | {{ $proposal->submitter->identity->studyProgram->name }}
                                    @endif
                                </small>
                            </td>
                            <td>
                                @if($proposal->contract_number)
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-success me-2">
                                            <x-lucide-check class="icon icon-inline" />
                                        </span>
                                        <div>
                                            <div class="font-weight-semibold text-monospace">{{ $proposal->contract_number }}</div>
                                            @if($proposal->contract_date)
                                                <small class="text-muted">Tgl: {{ \Carbon\Carbon::parse($proposal->contract_date)->translatedFormat('d F Y') }}</small>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <span class="badge bg-warning-lt">
                                        <x-lucide-alert-triangle class="icon icon-inline me-1" /> Belum Diterbitkan
                                    </span>
                                @endif
                            </td>
                            <td class="text-end font-weight-medium">
                                Rp {{ number_format($totalBudget, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <button type="button" wire:click="openEdit('{{ $proposal->id }}')" class="btn btn-sm btn-outline-primary" title="Edit Nomor Kontrak">
                                        <x-lucide-pencil class="icon" />
                                    </button>
                                    <a data-navigate-ignore="true" href="{{ route('reports.export-pdf', ['proposal' => $proposal, 'type' => 'final', 'preview' => 1]) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Pratinjau Cover & Laporan Akhir">
                                        <x-lucide-eye class="icon" />
                                    </a>
                                    <a data-navigate-ignore="true" href="{{ route('financial-reports.export-pdf', ['proposal' => $proposal, 'preview' => 1]) }}" target="_blank" class="btn btn-sm btn-outline-success" title="Pratinjau Laporan Keuangan (LPJ)">
                                        <x-lucide-file-spreadsheet class="icon" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <x-lucide-inbox class="icon icon-lg mb-2 text-secondary" />
                                <div>Tidak ada data usulan yang sesuai dengan filter pencarian.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($proposals->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-between">
                <div>Menampilkan {{ $proposals->firstItem() }} s/d {{ $proposals->lastItem() }} dari {{ $proposals->total() }} usulan</div>
                {{ $proposals->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Edit Single Contract -->
    @if($showEditModal)
        <div class="modal modal-blur fade show d-block" tabindex="-1" role="dialog" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <x-lucide-file-edit class="icon me-1 text-primary" />
                            Edit Nomor Kontrak Usulan
                        </h5>
                        <button type="button" wire:click="$set('showEditModal', false)" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-muted small mb-1">Judul Usulan</label>
                            <div class="font-weight-medium">{{ $editingProposalTitle }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Nomor Kontrak</label>
                            <input type="text" wire:model="editingContractNumber" class="form-control font-monospace" placeholder="Contoh: 012/ITSNU/LPPM/KTR-L/VIII/2026">
                            @error('editingContractNumber') <small class="text-danger">{{ $message }}</small> @enderror
                            <small class="form-hint">Nomor ini akan otomatis tercantum pada Cover Laporan Akhir & Cover Laporan Keuangan.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Tanggal Kontrak</label>
                            <input type="date" wire:model="editingContractDate" class="form-control">
                            @error('editingContractDate') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="$set('showEditModal', false)" class="btn btn-link link-secondary">Batal</button>
                        <button type="button" wire:click="saveSingle" wire:loading.attr="disabled" class="btn btn-primary">
                            <span wire:loading.remove wire:target="saveSingle"><x-lucide-save class="icon me-1" /> Simpan Nomor Kontrak</span>
                            <span wire:loading wire:target="saveSingle"><span class="spinner-border spinner-border-sm"></span> Menyimpan...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Batch Auto-Numbering Generator -->
    @if($showBatchModal)
        <div class="modal modal-blur fade show d-block" tabindex="-1" role="dialog" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary-lt">
                        <h5 class="modal-title text-primary">
                            <x-lucide-wand-2 class="icon me-1" />
                            Generate Nomor Kontrak Massal
                        </h5>
                        <button type="button" wire:click="$set('showBatchModal', false)" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Jenis Usulan Selection Tabs -->
                        <div class="mb-3">
                            <label class="form-label required">Kategori Usulan yang Digenerate</label>
                            <div class="btn-group w-100" role="group">
                                <button type="button" wire:click="setBatchScopeType('research')" class="btn {{ $batchScopeType === 'research' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <x-lucide-microscope class="icon me-1" />
                                    1. Khusus Penelitian (KTR-L)
                                </button>
                                <button type="button" wire:click="setBatchScopeType('community-service')" class="btn {{ $batchScopeType === 'community-service' ? 'btn-success' : 'btn-outline-success' }}">
                                    <x-lucide-heart-handshake class="icon me-1" />
                                    2. Khusus Pengabdian (KTR-PKM)
                                </button>
                                <button type="button" wire:click="setBatchScopeType('all')" class="btn {{ $batchScopeType === 'all' ? 'btn-secondary' : 'btn-outline-secondary' }}">
                                    <x-lucide-layers class="icon me-1" />
                                    Semua Jenis (L & PKM)
                                </button>
                            </div>
                        </div>

                        <!-- Target Selection -->
                        <div class="mb-3">
                            <label class="form-label required">Cakupan Target Usulan</label>
                            <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column gap-2">
                                <label class="form-selectgroup-item flex-fill">
                                    <input type="radio" wire:model.live="batchTarget" value="selected" class="form-selectgroup-input" {{ empty($selectedProposals) ? 'disabled' : '' }}>
                                    <div class="form-selectgroup-label d-flex align-items-center p-2 px-3 text-start">
                                        <div class="me-3">
                                            <span class="form-selectgroup-check"></span>
                                        </div>
                                        <div>
                                            <div class="font-weight-semibold">Hanya Usulan yang Dicentang ({{ count($selectedProposals) }} Terpilih)</div>
                                            <div class="text-muted small">Menerapkan nomor urut hanya pada baris usulan yang Anda centang pada tabel.</div>
                                        </div>
                                    </div>
                                </label>
                                <label class="form-selectgroup-item flex-fill">
                                    <input type="radio" wire:model.live="batchTarget" value="all_filtered" class="form-selectgroup-input">
                                    <div class="form-selectgroup-label d-flex align-items-center p-2 px-3 text-start">
                                        <div class="me-3">
                                            <span class="form-selectgroup-check"></span>
                                        </div>
                                        <div>
                                            <div class="font-weight-semibold">Seluruh Usulan Sesuai Filter Saat Ini ({{ $proposals->total() }} Total)</div>
                                            <div class="text-muted small">Menerapkan nomor urut ke semua usulan hasil filter saat ini (Tahun {{ $year ?: 'Semua' }}).</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">Nomor Urut Awal</label>
                                <input type="number" wire:model="batchStartNumber" min="1" class="form-control">
                                @error('batchStartNumber') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Jumlah Digit Angka (Leading Zero)</label>
                                <select wire:model="batchNumberDigits" class="form-select">
                                    <option value="1">1 digit (Contoh: 1, 2, 3)</option>
                                    <option value="2">2 digit (Contoh: 01, 02, 03)</option>
                                    <option value="3">3 digit (Contoh: 001, 002, 003)</option>
                                    <option value="4">4 digit (Contoh: 0001, 0002, 0003)</option>
                                </select>
                                @error('batchNumberDigits') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-12">
                                <label class="form-label required">Format Pola Nomor Kontrak</label>
                                <input type="text" wire:model="batchPattern" class="form-control font-monospace">
                                @error('batchPattern') <small class="text-danger">{{ $message }}</small> @enderror
                                <div class="form-hint mt-1">
                                    Tag pengganti:
                                    <code>{num}</code> = Nomor urut (001), 
                                    <code>{type}</code> = Kode tipe (L / PKM), 
                                    <code>{month}</code> = Bulan Romawi (VIII), 
                                    <code>{year}</code> = Tahun (2026)
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label required">Tanggal Kontrak Massal</label>
                                <input type="date" wire:model="batchContractDate" class="form-control">
                                @error('batchContractDate') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="$set('showBatchModal', false)" class="btn btn-link link-secondary">Batal</button>
                        <button type="button" wire:click="generateBatch" wire:loading.attr="disabled" class="btn btn-primary">
                            <span wire:loading.remove wire:target="generateBatch"><x-lucide-check class="icon me-1" /> Terapkan Penomoran Massal</span>
                            <span wire:loading wire:target="generateBatch"><span class="spinner-border spinner-border-sm"></span> Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
