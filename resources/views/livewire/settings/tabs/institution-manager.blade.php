<div>
    <x-tabler.alert />
    <div class="card">
        <div class="d-flex align-items-center justify-content-between card-header">
            <h3 class="card-title">Institusi</h3>
            <button type="button" class="btn btn-primary" wire:click='create' data-bs-toggle="modal"
                data-bs-target="#modal-institution">
                <x-lucide-plus class="icon" />
                Tambah Institusi
            </button>
        </div>
        <div class="alert alert-info mb-3">
            <x-lucide-info class="icon me-2" />
            Institusi yang memiliki <strong>pengguna, fakultas, atau program studi</strong> tidak dapat dihapus.
        </div>
        <div class="table-responsive">
            <table class="card-table table table-vcenter">
                <thead>
                    <tr>
                        <th>ID / Kode</th>
                        <th>Nama & Alamat</th>
                        <th>Kepala LPPM</th>
                        <th class="w-10">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($institutions as $item)
                        <tr wire:key="institution-{{ $item->id }}">
                            <td>
                                @if($item->code)
                                    <span class="badge bg-blue text-blue-fg" title="Kode PT SINTA">
                                        <i class="ti ti-hash me-1 opacity-75"></i>{{ $item->code }}
                                    </span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="font-weight-medium">{{ $item->name }}</div>
                                <div class="text-secondary small">{{ $item->address }}</div>
                            </td>
                            <td>
                                @if($item->lppm_head_name)
                                    <div class="font-weight-medium">{{ $item->lppm_head_name }}</div>
                                    <div class="text-secondary small">{{ $item->lppm_head_id }}</div>
                                @else
                                    <span class="text-muted italic small">Belum diatur</span>
                                @endif
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
            {{ $institutions->links() }}
        </div>
    </div>



    <x-tabler.modal-confirmation wire:key="modal-confirm-delete-institution" id="modal-confirm-delete-institution"
        title="Konfirmasi Hapus" message="Apakah Anda yakin ingin menghapus {{ $deleteItemName ?? '' }}?"
        confirm-text="Ya, Hapus" cancel-text="Batal" component-id="{{ $this->getId() }}"
        on-confirm="handleConfirmDeleteAction" />
    <x-tabler.modal wire:key="modal-institution" id="modal-institution" :title="$modalTitle" onHide="resetForm"
        component-id="{{ $this->getId() }}">
        <x-slot:body>
            <form wire:submit="save" id="form-institution">
                <div class="row">
                    <div class="col-md-9">
                        <div class="mb-3">
                            <label class="form-label">Nama Institusi</label>
                            <input type="text" wire:model="name" class="form-control" placeholder="Nama Lengkap Kampus">
                            @error('name')
                                <div class="d-block invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Kode PT (SINTA) <span class="text-muted small fw-normal">opsional</span></label>
                            <input type="text" wire:model="code" class="form-control @error('code') is-invalid @enderror" placeholder="062045">
                            <div class="form-text">Kode PT numerik dari portal SINTA (misal: 062045)</div>
                            @error('code')
                                <div class="d-block invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Alamat</label>
                    <textarea wire:model="address" class="form-control" rows="2"></textarea>
                    @error('address')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="hr-text">Detail Kepala LPPM</div>
                <div class="mb-3">
                    <label class="form-label">
                        Pilih dari Data Dosen (Opsional)
                        <span wire:loading wire:target="lppmHeadUserId" class="spinner-border spinner-border-sm text-primary ms-2" role="status"></span>
                    </label>
                    <select wire:model.live="lppmHeadUserId" class="form-select">
                        <option value="">-- Pilih Dosen --</option>
                        @foreach($lecturers as $lecturer)
                            <option value="{{ $lecturer->id }}">
                                {{ $lecturer->name }} 
                                @if($lecturer->identity?->identity_id) ({{ $lecturer->identity->identity_id }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap Kepala LPPM (beserta gelar)</label>
                    <input type="text" wire:model="lppmHeadName" class="form-control" placeholder="Nama, M.Kom.">
                    @error('lppmHeadName')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">NIP / NIDN Kepala LPPM</label>
                    <input type="text" wire:model="lppmHeadId" class="form-control" placeholder="19...">
                    @error('lppmHeadId')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </form>
        </x-slot:body>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" form="form-institution" class="btn btn-primary" wire:loading.class="btn-loading"
                wire:target="save">Simpan</button>
        </x-slot:footer>
    </x-tabler.modal>

</div>
