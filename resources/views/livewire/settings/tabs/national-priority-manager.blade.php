<div>
    <x-tabler.alert />
    <div class="card">
        <div class="d-flex align-items-center justify-content-between card-header">
            <h3 class="card-title">Prioritas Nasional</h3>
            <button type="button" class="btn btn-primary" wire:click='create' data-bs-toggle="modal"
                data-bs-target="#modal-national-priority">
                
                Tambah Prioritas Nasional
            </button>
        </div>
        <div class="table-responsive">
            <table class="card-table table table-vcenter">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th class="w-25">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($nationalPriorities as $item)
                        <tr wire:key="priority-{{ $item->id }}">
                            <td>{{ $item->name }}</td>
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
            {{ $nationalPriorities->links() }}
        </div>
    </div>



    <x-tabler.modal-confirmation wire:key="modal-confirm-delete-national-priority"
        id="modal-confirm-delete-national-priority" title="Konfirmasi Hapus"
        message="Apakah Anda yakin ingin menghapus {{ $deleteItemName ?? '' }}?" confirm-text="Ya, Hapus"
        cancel-text="Batal" component-id="{{ $this->getId() }}" on-confirm="handleConfirmDeleteAction" />
    <x-tabler.modal wire:key="modal-national-priority" id="modal-national-priority" :title="$modalTitle" onHide="resetForm"
        component-id="{{ $this->getId() }}">
        <x-slot:body>
            <form wire:submit="save" id="form-national-priority">
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" wire:model="name" class="form-control" placeholder="Enter name">
                    @error('name')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </form>
        </x-slot:body>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" form="form-national-priority" class="btn btn-primary"
                wire:loading.class="btn-loading" wire:target="save">Simpan</button>
        </x-slot:footer>
    </x-tabler.modal>

</div>
