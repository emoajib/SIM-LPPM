<div>
    <x-tabler.alert />
    <div class="card">
        <div class="d-flex align-items-center justify-content-between card-header">
            <h3 class="card-title">Fakultas</h3>
            <button type="button" class="btn btn-primary" wire:click='create' data-bs-toggle="modal"
                data-bs-target="#modal-faculty">
                
                Tambah Fakultas
            </button>
        </div>
        <div class="table-responsive">
            <table class="card-table table table-vcenter">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Kode</th>
                        <th>Institusi</th>
                        <th>Dekan</th>
                        <th class="w-10">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($faculties as $item)
                        <tr wire:key="faculty-{{ $item->id }}">
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->code }}</td>
                             <td>{{ $item->institution?->name ?? 'N/A' }}</td>
                             <td>
                                 @if($item->dean_name)
                                     <div class="font-weight-medium">{{ $item->dean_name }}</div>
                                     <div class="text-secondary small">{{ $item->dean_id }}</div>
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
            {{ $faculties->links() }}
        </div>
    </div>



    <x-tabler.modal-confirmation wire:key="modal-confirm-delete-faculty" id="modal-confirm-delete-faculty"
        title="Konfirmasi Hapus" message="Apakah Anda yakin ingin menghapus {{ $deleteItemName ?? '' }}?"
        confirm-text="Ya, Hapus" cancel-text="Batal" component-id="{{ $this->getId() }}"
        on-confirm="handleConfirmDeleteAction" />
    <x-tabler.modal wire:key="modal-faculty" id="modal-faculty" :title="$modalTitle" onHide="resetForm"
        component-id="{{ $this->getId() }}">
        <x-slot:body>
            <form wire:submit="save" id="form-faculty">
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" wire:model="name" class="form-control" placeholder="Enter name">
                    @error('name')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Kode</label>
                    <input type="text" wire:model="code" class="form-control" placeholder="Enter code">
                    @error('code')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Institusi</label>
                    <select wire:model="institutionId" class="form-control">
                        <option value="">Select institution</option>
                        @foreach ($institutions as $institution)
                            <option value="{{ $institution->id }}">{{ $institution->name }}</option>
                        @endforeach
                    </select>
                    @error('institutionId')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="hr-text">Detail Dekan</div>
                <div class="mb-3">
                    <label class="form-label">
                        Pilih dari Data Dosen (Opsional)
                        <span wire:loading wire:target="deanUserId" class="spinner-border spinner-border-sm text-primary ms-2" role="status"></span>
                    </label>
                    <select wire:model.live="deanUserId" class="form-select">
                        <option value="">-- Pilih Dosen --</option>
                        @foreach($lecturers as $lecturer)
                            <option value="{{ $lecturer->id }}">
                                {{ $lecturer->name }} 
                                @if($lecturer->identity?->identity_id) ({{ $lecturer->identity->identity_id }}) @endif
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Memilih dosen akan otomatis mengisi kolom nama dan NIP di bawah.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap Dekan (beserta gelar)</label>
                    <input type="text" wire:model="deanName" class="form-control" placeholder="Dr. ..., M.T.">
                    @error('deanName')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">NIP / NIDN Dekan</label>
                    <input type="text" wire:model="deanId" class="form-control" placeholder="19...">
                    @error('deanId')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </form>
        </x-slot:body>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" form="form-faculty" class="btn btn-primary" wire:loading.class="btn-loading"
                wire:target="save">Simpan</button>
        </x-slot:footer>
    </x-tabler.modal>

</div>
