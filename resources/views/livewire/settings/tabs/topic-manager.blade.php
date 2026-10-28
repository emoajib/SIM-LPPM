<div>
    <x-tabler.alert />
    <div class="card">
        <div class="d-flex align-items-center justify-content-between card-header">
            <h3 class="card-title">Topik</h3>
            <button type="button" class="btn btn-primary" wire:click='create' data-bs-toggle="modal"
                data-bs-target="#modal-topic">
                
                Tambah Topik
            </button>
        </div>
        <div class="table-responsive">
            <table class="card-table table table-vcenter">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Tema</th>
                        <th>Penelitian</th>
                        <th>Pengabdian</th>
                        <th class="w-25">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($topics as $item)
                        <tr wire:key="topic-{{ $item->id }}">
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->theme?->name ?? 'N/A' }}</td>
                            <td>
                                @if($item->is_active_for_research)
                                    <span class="badge bg-success text-success-fg">Aktif</span>
                                @else
                                    <span class="badge bg-secondary text-secondary-fg">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                @if($item->is_active_for_community_service)
                                    <span class="badge bg-success text-success-fg">Aktif</span>
                                @else
                                    <span class="badge bg-secondary text-secondary-fg">Nonaktif</span>
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
            {{ $topics->links() }}
        </div>
    </div>



    <x-tabler.modal-confirmation wire:key="modal-confirm-delete-topic" id="modal-confirm-delete-topic"
        title="Konfirmasi Hapus" message="Apakah Anda yakin ingin menghapus {{ $deleteItemName ?? '' }}?"
        confirm-text="Ya, Hapus" cancel-text="Batal" component-id="{{ $this->getId() }}"
        on-confirm="handleConfirmDeleteAction" />
    <x-tabler.modal wire:key="modal-topic" id="modal-topic" :title="$modalTitle" onHide="resetForm"
        component-id="{{ $this->getId() }}">
        <x-slot:body>
            <form wire:submit="save" id="form-topic">
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" wire:model="name" class="form-control" placeholder="Enter name">
                    @error('name')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Tema</label>
                    <select wire:model="themeId" class="form-control">
                        <option value="">Select theme</option>
                        @foreach ($themes as $theme)
                            <option value="{{ $theme->id }}">{{ $theme->name }}</option>
                        @endforeach
                    </select>
                    @error('themeId')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" wire:model="is_active_for_research">
                        <span class="form-check-label">Aktif untuk Penelitian</span>
                    </label>
                    @error('is_active_for_research')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" wire:model="is_active_for_community_service">
                        <span class="form-check-label">Aktif untuk Pengabdian</span>
                    </label>
                    @error('is_active_for_community_service')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </form>
        </x-slot:body>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" form="form-topic" class="btn btn-primary" wire:loading.class="btn-loading"
                wire:target="save">Simpan</button>
        </x-slot:footer>
    </x-tabler.modal>

</div>
