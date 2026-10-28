<div>
    <x-tabler.alert />
    <div class="card">
        <div class="d-flex align-items-center justify-content-between card-header">
            <h3 class="card-title">Pesan Kuota</h3>
            <button type="button" class="btn btn-primary" wire:click='create' data-bs-toggle="modal"
                data-bs-target="#modal-quota-message">
                
                Tambah Pesan Kuota
            </button>
        </div>
        <div class="table-responsive">
            <table class="card-table table table-vcenter">
                <thead>
                    <tr>
                        <th>Key</th>
                        <th>Pesan</th>
                        <th class="w-25">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($quotaMessages as $item)
                        <tr wire:key="quota-message-{{ $item->id }}">
                            <td><code>{{ $item->key }}</code></td>
                            <td>{{ $item->message }}</td>
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
            {{ $quotaMessages->links() }}
        </div>
    </div>

    <x-tabler.modal-confirmation wire:key="modal-confirm-delete-quota-message" id="modal-confirm-delete-quota-message"
        title="Konfirmasi Hapus" message="Apakah Anda yakin ingin menghapus pesan dengan key {{ $deleteItemKey ?? '' }}?"
        confirm-text="Ya, Hapus" cancel-text="Batal" component-id="{{ $this->getId() }}"
        on-confirm="handleConfirmDeleteAction" />

    <x-tabler.modal wire:key="modal-quota-message" id="modal-quota-message" :title="$modalTitle" onHide="resetForm"
        component-id="{{ $this->getId() }}">
        <x-slot:body>
            <form wire:submit="save" id="form-quota-message">
                <div class="mb-3">
                    <label class="form-label">Key</label>
                    <input type="text" wire:model="key" class="form-control" placeholder="Contoh: research_limit_exceeded" @if($editingId) disabled @endif>
                    @error('key')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Pesan</label>
                    <textarea wire:model="message" class="form-control" rows="4" placeholder="Masukkan pesan kuota"></textarea>
                    @error('message')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </form>
        </x-slot:body>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" form="form-quota-message" class="btn btn-primary" wire:loading.class="btn-loading"
                wire:target="save">Simpan</button>
        </x-slot:footer>
    </x-tabler.modal>
</div>
