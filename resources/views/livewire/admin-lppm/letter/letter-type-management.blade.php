<div>
    <div class="page-body">
        <div class="container-xl">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center py-3">
                    <h5 class="card-title mb-0"><i class="ti ti-settings me-2"></i> Jenis Surat</h5>
                    <button class="btn btn-primary btn-sm" wire:click="openCreateModal">
                        <i class="ti ti-plus me-1"></i> Tambah Jenis Surat
                    </button>
                </div>

                <div class="table-responsive">
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
                            @forelse($letterTypes as $type)
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
                                        <button class="btn btn-outline-primary btn-sm" wire:click="openEditModal('{{ $type->id }}')">
                                            <i class="ti ti-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm" wire:click="confirmDelete('{{ $type->id }}')" {{ $type->letters_count > 0 ? 'disabled' : '' }}>
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
                    {{ $letterTypes->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Create/Edit Modal --}}
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

                        {{-- Format Nomor Surat --}}
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
                            <button type="submit" class="btn btn-primary">
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

    {{-- Delete Confirmation Modal --}}
    <div class="modal modal-blur fade @if($showDeleteModal) show @endif" 
         style="display: @if($showDeleteModal) block @else none @endif;" 
         tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-status bg-danger"></div>
                <div class="modal-body text-center py-4">
                    <i class="ti ti-alert-triangle icon-lg text-danger mb-2"></i>
                    <h3>Hapus Jenis Surat?</h3>
                    <p class="text-muted">Yakin ingin menghapus <strong>{{ $deletingLetterType?->name }} ({{ $deletingLetterType?->code }})</strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" wire:click="$set('showDeleteModal', false)">Batal</button>
                    <button type="button" class="btn btn-danger" wire:click="delete">
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
