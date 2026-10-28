<div>
    <x-tabler.alert />
    <div class="card">
        <div class="d-flex align-items-center justify-content-between card-header">
            <h3 class="card-title">SDG</h3>
            <button type="button" class="btn btn-primary" wire:click='create' data-bs-toggle="modal"
                data-bs-target="#modal-sdg">
                
                Tambah SDG
            </button>
        </div>
        <div class="table-responsive">
            <table class="card-table table table-vcenter">
                <thead>
                    <tr>
                        <th class="w-1">ID</th>
                        <th class="w-25">Nama</th>
                        <th>Deskripsi</th>
                        <th class="w-25 text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sdgs as $item)
                        <tr wire:key="sdg-{{ $item->id }}">
                            <td>{{ $item->id }}</td>
                            <td class="fw-bold">{{ $item->name }}</td>
                            <td class="text-muted text-wrap">{{ $item->description ?: '-' }}</td>
                            <td class="text-end">
                                <div class="btn-list justify-content-end">
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
            {{ $sdgs->links() }}
        </div>
    </div>

    <x-tabler.modal-confirmation wire:key="modal-confirm-delete-sdg" id="modal-confirm-delete-sdg"
        title="Konfirmasi Hapus" message="Apakah Anda yakin ingin menghapus {{ $deleteItemName ?? '' }}?"
        confirm-text="Ya, Hapus" cancel-text="Batal" component-id="{{ $this->getId() }}"
        on-confirm="handleConfirmDeleteAction" />

    <x-tabler.modal wire:key="modal-sdg" id="modal-sdg" :title="$modalTitle" onHide="resetForm"
        component-id="{{ $this->getId() }}">
        <x-slot:body>
            <form wire:submit="save" id="form-sdg">
                <div class="mb-3">
                    <label class="form-label required">Nama</label>
                    <input type="text" wire:model="name" class="form-control" placeholder="Nama SDG (Max 255 karakter)">
                    @error('name')
                        <div class="d-block invalid-feedback text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea wire:model="description" class="form-control" rows="4"
                        placeholder="Keterangan singkat tentang poin SDG ini..."></textarea>
                    @error('description')
                        <div class="d-block invalid-feedback text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </form>
        </x-slot:body>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" form="form-sdg" class="btn btn-primary" wire:loading.class="btn-loading"
                wire:target="save">Simpan</button>
        </x-slot:footer>
    </x-tabler.modal>

</div>