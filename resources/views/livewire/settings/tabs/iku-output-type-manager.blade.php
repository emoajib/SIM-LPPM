<div>
    <x-tabler.alert />
    <div class="card">
        <div class="d-flex align-items-center justify-content-between card-header">
            <h3 class="card-title">Jenis Luaran IKU</h3>
            <button type="button" class="btn btn-primary" wire:click='create' data-bs-toggle="modal"
                data-bs-target="#modal-iku-output-type">
                
                Tambah Jenis Luaran
            </button>
        </div>
        <div class="table-responsive">
            <table class="card-table table table-vcenter">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Grup</th>
                        <th>Status</th>
                        <th class="w-25">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr wire:key="iku-type-{{ $item->id }}">
                            <td>{{ $item->name }}</td>
                            <td>
                                <span class="badge bg-blue-lt">
                                    {{ ucfirst($item->group) }}
                                </span>
                            </td>
                            <td>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox"
                                        wire:click="toggleStatus({{ $item->id }})" @if($item->is_active) checked @endif>
                                </div>
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
            {{ $items->links() }}
        </div>
    </div>

    <x-tabler.modal-confirmation wire:key="modal-confirm-delete-iku-output-type"
        id="modal-confirm-delete-iku-output-type" title="Konfirmasi Hapus"
        message="Apakah Anda yakin ingin menghapus {{ $deleteItemName ?? '' }}?" confirm-text="Ya, Hapus"
        cancel-text="Batal" component-id="{{ $this->getId() }}" on-confirm="handleConfirmDeleteAction" />

    <x-tabler.modal wire:key="modal-iku-output-type" id="modal-iku-output-type" :title="$modalTitle" onHide="resetForm"
        component-id="{{ $this->getId() }}">
        <x-slot:body>
            <form wire:submit="save" id="form-iku-output-type">
                <div class="mb-3">
                    <label class="form-label">Nama Jenis Luaran</label>
                    <input type="text" wire:model="name" class="form-control"
                        placeholder="Contoh: Jurnal Internasional">
                    @error('name')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Grup</label>
                    <select wire:model="group" class="form-select">
                        <option value="publication">Publication</option>
                        <option value="hki">HKI</option>
                        <option value="product">Product</option>
                        <option value="pakar">Pakar</option>
                    </select>
                    @error('group')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-check form-switch px-0">
                        <input class="form-check-input" type="checkbox" wire:model="is_active">
                        <span class="form-check-label">Aktif</span>
                    </label>
                </div>
            </form>
        </x-slot:body>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" form="form-iku-output-type" class="btn btn-primary" wire:loading.class="btn-loading"
                wire:target="save">Simpan</button>
        </x-slot:footer>
    </x-tabler.modal>
</div>