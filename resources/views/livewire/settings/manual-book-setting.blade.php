<div>
    <div class="card">
        <div class="d-flex align-items-center justify-content-between card-header">
            <h3 class="card-title">Manual Book</h3>
            <button type="button" class="btn btn-primary" wire:click='create'>
                <x-lucide-plus class="icon" />
                Tambah Manual Book
            </button>
        </div>
        <div class="table-responsive">
            <table class="card-table table table-vcenter">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Role</th>
                        <th>Versi</th>
                        <th>File</th>
                        <th>Status</th>
                        <th class="w-25">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($manualBooks as $item)
                        <tr wire:key="manual-book-{{ $item->id }}">
                            <td class="fw-medium">{{ $item->title }}</td>
                            <td>
                                @foreach($item->assigned_roles as $role)
                                    <span class="badge bg-secondary me-1">{{ format_role_name($role) }}</span>
                                @endforeach
                            </td>
                            <td>v{{ $item->version_number }}</td>
                            <td>
                                @if($item->getFirstMedia('manual_book_file'))
                                    <span class="text-success">
                                        <i class="icon icon-tabler icon-tabler-file-check"></i> Terupload
                                    </span>
                                @else
                                    <span class="text-muted">
                                        <i class="icon icon-tabler icon-tabler-file-off"></i> Tidak ada
                                    </span>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-sm {{ $item->status === 'active' ? 'btn-success' : 'btn-secondary' }}"
                                    wire:click="toggleStatus('{{ $item->id }}')"
                                    wire:confirm="Ubah status manual book ini?">
                                    {{ $item->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>
                            <td>
                                <div class="btn-list">
                                    <button type="button" class="btn-outline-warning btn btn-sm"
                                        wire:click="edit('{{ $item->id }}')">
                                        Edit
                                    </button>
                                    <button type="button" class="btn-outline-danger btn btn-sm"
                                        wire:click="confirmDelete('{{ $item->id }}')" wire:loading.attr="disabled">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $manualBooks->links() }}
        </div>
    </div>

    <x-tabler.modal-confirmation wire:key="modal-confirm-delete-manual-book" id="modal-confirm-delete-manual-book"
        title="Konfirmasi Hapus" message="Apakah Anda yakin ingin menghapus {{ $deleteItemName ?? '' }}?"
        confirm-text="Ya, Hapus" cancel-text="Batal" component-id="{{ $this->getId() }}"
        on-confirm="handleConfirmDeleteAction" />

    <x-tabler.modal wire:key="modal-manual-book" id="modal-manual-book" :title="$modalTitle" onHide="resetForm"
        component-id="{{ $this->getId() }}">
        <x-slot:body>
            <form wire:submit="save" id="form-manual-book">
                <div class="mb-3">
                    <label class="form-label required">Judul Manual Book</label>
                    <input type="text" wire:model="title" class="form-control" placeholder="Masukkan judul">
                    @error('title')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea wire:model="description" class="form-control" rows="2" placeholder="Deskripsi (opsional)"></textarea>
                    @error('description')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label required">Role Pengguna</label>
                    <p class="text-muted small mb-2">Pilih role yang akan melihat manual book ini.</p>
                    <div class="row g-2">
                        @foreach($allRoles as $role)
                            <div class="col-md-4 col-sm-6">
                                <label class="form-check">
                                    <input type="checkbox" class="form-check-input"
                                        wire:model="assignedRoles" value="{{ $role }}">
                                    <span class="form-check-label">{{ format_role_name($role) }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    @error('assignedRoles')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Versi</label>
                        <input type="text" wire:model="version_number" class="form-control" placeholder="1.0">
                        @error('version_number')
                            <div class="d-block invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Status</label>
                        <select wire:model="status" class="form-select">
                            <option value="active">Aktif</option>
                            <option value="inactive">Nonaktif</option>
                        </select>
                        @error('status')
                            <div class="d-block invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">File PDF</label>
                    @if($existingFile)
                        <div class="mb-2 p-2 border rounded">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <i class="icon icon-tabler icon-tabler-file-check text-success me-1"></i>
                                    <span class="small">{{ $existingFile['name'] }}</span>
                                    <span class="text-muted small ms-2">
                                        ({{ number_format($existingFile['size'] / 1024, 1) }} KB)
                                    </span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                    wire:click="removeFile" wire:confirm="Hapus file ini?">
                                    <i class="icon icon-tabler icon-tabler-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endif
                    <input type="file" wire:model="file" class="form-control" accept=".pdf">
                    <small class="text-muted">Format: PDF, maks 10 MB</small>
                    <div wire:loading wire:target="file" class="text-info small mt-1">
                        <i class="icon icon-tabler icon-tabler-loader icon-spin"></i> Mengupload...
                    </div>
                    @error('file')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </form>
        </x-slot:body>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" form="form-manual-book" class="btn btn-primary" wire:loading.class="btn-loading"
                wire:target="save">Simpan</button>
        </x-slot:footer>
    </x-tabler.modal>
</div>
