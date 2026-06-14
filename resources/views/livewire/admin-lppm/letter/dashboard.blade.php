<x-slot:title>Dashboard Persuratan</x-slot:title>
<x-slot:pageTitle>Dashboard Persuratan</x-slot:pageTitle>
<x-slot:pageSubtitle>Statistik, arsip, dan manajemen jenis surat LPPM</x-slot:pageSubtitle>

{{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
<div>
    <x-tabler.alert />

    {{-- Stats Cards --}}
    @if($activeTab === 'letters')
    <div class="row row-deck row-cards mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="text-muted small">Total Surat</span>
                            <h2 class="mb-0 mt-1">{{ $stats['total'] }}</h2>
                        </div>
                        <div class="avatar avatar-lg bg-primary-lt">
                            <i class="ti ti-mail avatar-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="text-muted small">Perlu Diproses</span>
                            <h2 class="mb-0 mt-1 text-warning">{{ $stats['pending'] }}</h2>
                        </div>
                        <div class="avatar avatar-lg bg-warning-lt">
                            <i class="ti ti-clock avatar-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="text-muted small">Diterbitkan</span>
                            <h2 class="mb-0 mt-1 text-success">{{ $stats['published'] }}</h2>
                        </div>
                        <div class="avatar avatar-lg bg-success-lt">
                            <i class="ti ti-check avatar-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="text-muted small">Ditolak</span>
                            <h2 class="mb-0 mt-1 text-danger">{{ $stats['rejected'] }}</h2>
                        </div>
                        <div class="avatar avatar-lg bg-danger-lt">
                            <i class="ti ti-x avatar-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Main Container Card --}}
    <div class="card border-0 shadow-sm">
        {{-- Card Header (Dynamic based on active tab for Livewire reactivity) --}}
        <div class="card-header bg-transparent border-0 pt-4 pb-0">
            <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-3">
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="nav-item">
                        <a class="nav-link @if($activeTab === 'letters' && $statusFilter === 'all') active @endif" href="#" wire:click.prevent="setTab('letters', 'all')">
                            <i class="ti ti-mail me-1"></i> Semua
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if($activeTab === 'letters' && $statusFilter === 'pending_approval') active @endif" href="#" wire:click.prevent="setTab('letters', 'pending_approval')">
                            <i class="ti ti-clock me-1"></i> Perlu Diproses
                            <span class="badge bg-warning-lt ms-1">{{ $stats['pending'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if($activeTab === 'letters' && $statusFilter === 'published') active @endif" href="#" wire:click.prevent="setTab('letters', 'published')">
                            <i class="ti ti-check me-1"></i> Diterbitkan
                            <span class="badge bg-success-lt ms-1">{{ $stats['published'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if($activeTab === 'letters' && $statusFilter === 'rejected') active @endif" href="#" wire:click.prevent="setTab('letters', 'rejected')">
                            <i class="ti ti-x me-1"></i> Ditolak
                            <span class="badge bg-danger-lt ms-1">{{ $stats['rejected'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if($activeTab === 'letters' && $statusFilter === 'cancelled') active @endif" href="#" wire:click.prevent="setTab('letters', 'cancelled')">
                            <i class="ti ti-ban me-1"></i> Dibatalkan
                            <span class="badge bg-secondary-lt ms-1">{{ $stats['cancelled'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if($activeTab === 'letters' && $statusFilter === 'ready_to_print') active @endif" href="#" wire:click.prevent="setTab('letters', 'ready_to_print')">
                            <i class="ti ti-printer me-1"></i> Siap Cetak
                            <span class="badge bg-info-lt ms-1">{{ $stats['ready_to_print'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if($activeTab === 'types') active @endif" href="#" wire:click.prevent="setTab('types')">
                            <i class="ti ti-settings me-1"></i> Kelola Jenis Surat
                        </a>
                    </li>
                </ul>

                {{-- Action Button inside Livewire DOM --}}
                @if($activeTab === 'types')
                    <button class="btn btn-primary btn-sm shadow-sm" wire:click="openCreateModal">
                        <i class="ti ti-plus me-1"></i> Tambah Jenis Surat
                    </button>
                @endif
            </div>
        </div>

        {{-- Tab Content --}}
        @if($activeTab === 'letters')
            {{-- Search & Filters --}}
            <div class="card-body bg-transparent border-0 pt-4 pb-2">
                <div class="row align-items-end g-3">
                    <div class="col-md-4 col-sm-12">
                        <label class="form-label">Pencarian</label>
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <i class="ti ti-search"></i>
                            </span>
                            <input type="text" class="form-control" placeholder="Cari nomor surat, nama dosen, atau jenis..." wire:model.live="search">
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">Jenis Surat</label>
                        <select class="form-select" wire:model.live="typeFilter">
                            <option value="">Semua Jenis</option>
                            @foreach($letterTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->code }} - {{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-3">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control" wire:model.live="dateFrom">
                    </div>
                    <div class="col-md-2 col-sm-3">
                        <label class="form-label">Tanggal Akhir</label>
                        <input type="date" class="form-control" wire:model.live="dateTo">
                    </div>
                    <div class="col-md-1 col-sm-12">
                        <button type="button" class="btn btn-outline-secondary w-100" wire:click="resetFilters" title="Reset Filters">
                            <i class="ti ti-rotate-2"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Letters Table --}}
            <div class="table-responsive mt-3">
                <table class="table table-vcenter card-table table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nomor Surat</th>
                            <th>Jenis</th>
                            <th>Pengaju</th>
                            <th>Sumber</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($letters as $letter)
                        <tr>
                            <td class="text-muted">{{ $letters->firstItem() + $loop->index }}</td>
                            <td>
                                <div class="fw-bold">{{ $letter->letter_number ?? 'Sedang Diproses' }}</div>
                            </td>
                            <td>
                                <div>{{ $letter->letterType->name }}</div>
                                <div class="text-muted small">{{ $letter->letterType->code }}</div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm me-2 bg-primary-lt">{{ $letter->user->initials() }}</span>
                                    <div>
                                        <div>{{ $letter->user->name }}</div>
                                        <div class="text-muted small">{{ $letter->user->identity->identity_id ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $letter->source === 'manual' ? 'info' : 'secondary' }}-lt">
                                    {{ $letter->source === 'manual' ? 'Manual' : 'Proposal' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ \App\Models\Letter::statusColor($letter->status) }}-lt px-2 py-1">
                                    <span class="badge bg-{{ \App\Models\Letter::statusColor($letter->status) }} me-1"></span> {{ \App\Models\Letter::statusLabel($letter->status) }}
                                </span>
                            </td>
                            <td class="text-muted">
                                {{ $letter->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    @if($letter->file_path)
                                    <a href="{{ route('letter.view', $letter->id) }}" target="_blank" class="btn btn-outline-info btn-sm" title="Lihat PDF">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                    @endif
                                    <a href="{{ route('letter.download', $letter->id) }}" target="_blank" class="btn btn-outline-info btn-sm" title="Unduh PDF">
                                        <i class="ti ti-download"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                Tidak ada surat yang sesuai filter.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-transparent border-0 pb-4">
                {{ $letters->links() }}
            </div>

        @elseif($activeTab === 'types')
            {{-- Types Table --}}
            <div class="table-responsive mt-3">
                <table class="table table-vcenter card-table table-hover">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th>Format Nomor</th>
                            <th>Uploadable</th>
                            <th>Status</th>
                            <th>Jumlah Surat</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($letterTypesList as $type)
                        <tr>
                            <td><span class="badge bg-primary-lt">{{ $type->code }}</span></td>
                            <td>
                                <div class="fw-bold">{{ $type->name }}</div>
                                @if($type->description)
                                <div class="text-muted small">{{ Str::limit($type->description, 50) }}</div>
                                @endif
                            </td>
                            <td><span class="badge bg-secondary-lt">{{ ucfirst($type->category) }}</span></td>
                            <td><code class="small">{{ $type->numbering_format }}</code></td>
                            <td>
                                <span class="badge bg-{{ $type->is_uploadable ? 'info' : 'secondary' }}-lt">
                                    {{ $type->is_uploadable ? 'Upload' : 'Template' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $type->is_active ? 'success' : 'danger' }}-lt">
                                    {{ $type->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>{{ $type->letters_count }}</td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <button class="btn btn-outline-primary btn-sm shadow-sm" wire:click="openEditModal('{{ $type->id }}')">
                                        <i class="ti ti-pencil"></i>
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm shadow-sm" wire:click="confirmDelete('{{ $type->id }}')" {{ $type->letters_count > 0 ? 'disabled' : '' }}>
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                Belum ada jenis surat.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-transparent border-0 pb-4">
                {{ $letterTypesList->links() }}
            </div>
        @endif
    </div>

    {{-- Create/Edit Letter Type Modal --}}
    <div class="modal modal-blur fade @if($showModal) show @endif" 
         style="display: @if($showModal) block @else none @endif;" 
         tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $editMode ? 'Edit' : 'Tambah' }} Jenis Surat</h5>
                    <button type="button" class="btn-close" wire:click="$set('showModal', false)"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit="save">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label required">Kode Surat</label>
                                <input type="text" class="form-control" wire:model="code" placeholder="ST" maxlength="10" required>
                                @error('code')
                                <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-8">
                                <label class="form-label required">Nama Surat</label>
                                <input type="text" class="form-control" wire:model="name" placeholder="Surat Tugas" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" wire:model="description" rows="2" placeholder="Deskripsi kegunaan surat..."></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label required">Kategori</label>
                                <select class="form-select" wire:model="category" required>
                                    <option value="persiapan">Persiapan</option>
                                    <option value="etik">Etik</option>
                                    <option value="pelaksanaan">Pelaksanaan</option>
                                    <option value="pelaporan">Pelaporan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Template View</label>
                                <input type="text" class="form-control" wire:model="templateView" placeholder="pdf.letters.surat-tugas">
                            </div>
                        </div>

                        {{-- Numbering Format --}}
                        <div class="card bg-light border-0 mb-3">
                            <div class="card-body">
                                <label class="form-label required">Format Nomor Surat Otomatis</label>
                                <input type="text" class="form-control font-monospace" wire:model="numberingFormat" required>
                                <div class="form-text">
                                    <strong>Preview:</strong> 
                                    @php
                                        $preview = str_replace(
                                            ['{NOMOR}', '{CODE}', '{BULAN-ROMAWI}', '{TAHUN}'],
                                            ['001', strtoupper($this->code ?: 'XX'), 'VI', date('Y')],
                                            $this->numberingFormat
                                        );
                                    @endphp
                                    <code>{{ $preview }}</code>
                                </div>
                                <div class="form-text mt-1">
                                    <strong>Placeholder:</strong>
                                    <code>{NOMOR}</code> = Nomor urut (001, 002, ...) |
                                    <code>{CODE}</code> = Kode surat |
                                    <code>{BULAN-ROMAWI}</code> = Bulan (I-XII) |
                                    <code>{TAHUN}</code> = Tahun
                                    <br><span class="text-danger">⚠️ {NOMOR} harus di posisi pertama!</span>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" wire:model="isUploadable" id="isUploadable">
                                    <label class="form-check-label" for="isUploadable">Upload Manual (bukan template)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" wire:model="isActive" id="isActive">
                                    <label class="form-check-label" for="isActive">Aktif</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-link" wire:click="$set('showModal', false)">Batal</button>
                            <button type="submit" class="btn btn-primary shadow-sm">
                                <i class="ti ti-check me-1"></i> {{ $editMode ? 'Simpan Perubahan' : 'Tambah' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @if($showModal)
    <div class="modal-backdrop fade show"></div>
    @endif

    {{-- Delete Letter Type Confirmation Modal --}}
    <div class="modal modal-blur fade @if($showDeleteModal) show @endif" 
         style="display: @if($showDeleteModal) block @else none @endif;" 
         tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-sm">
                <div class="modal-status bg-danger"></div>
                <div class="modal-body text-center py-4">
                    <i class="ti ti-alert-triangle icon-lg text-danger mb-2"></i>
                    <h3>Hapus Jenis Surat?</h3>
                    <p class="text-muted">Yakin ingin menghapus <strong>{{ $deletingLetterType?->name }} ({{ $deletingLetterType?->code }})</strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" wire:click="$set('showDeleteModal', false)">Batal</button>
                    <button type="button" class="btn btn-danger shadow-sm" wire:click="delete">
                        <i class="ti ti-trash me-1"></i> Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
    @if($showDeleteModal)
    <div class="modal-backdrop fade show" style="z-index: 1040;"></div>
    @endif
</div>
