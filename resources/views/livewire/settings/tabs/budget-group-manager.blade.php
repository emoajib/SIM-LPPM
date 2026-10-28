<div>
    <x-tabler.alert />
    <div class="card">
        <div class="d-flex align-items-center justify-content-between card-header">
            <h3 class="card-title">Kelompok Anggaran</h3>
            <button type="button" class="btn btn-primary" wire:click='create' data-bs-toggle="modal"
                data-bs-target="#modal-budget-group">
                
                Tambah Kelompok Anggaran
            </button>
        </div>
        <div class="table-responsive">
            <table class="card-table table table-vcenter">
<thead>
                     <tr>
                         <th>Kode</th>
                         <th>Nama</th>
                         <th>Deskripsi</th>
                         <th>Persentase</th>
                         <th>Tipe</th>
                         <th>Status</th>
                         <th class="w-25">Aksi</th>
                     </tr>
                 </thead>
                <tbody>
                    @foreach ($budgetGroups as $item)
                        <tr wire:key="budget-group-{{ $item->id }}">
                            <td><span class="bg-blue-lt badge">{{ $item->code }}</span></td>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->description ?? '-' }}</td>
<td>
                                 @if ($item->percentage)
                                     <span class="bg-green-lt badge">{{ number_format($item->percentage, 0) }}%</span>
                                     @if ($item->percentage_type)
                                         <small class="text-muted">({{ ucfirst($item->percentage_type) }})</small>
                                     @endif
                                 @else
                                     <span class="text-muted">-</span>
                                 @endif
                             </td>
                             <td>
                                 @if ($item->proposal_type)
                                     <span class="badge bg-blue-lt">{{ ucfirst($item->proposal_type) }}</span>
                                 @else
                                     <span class="text-muted">-</span>
                                 @endif
                             </td>
                             <td>
                                 @if ($item->is_active)
                                     <span class="badge bg-success-lt">Aktif</span>
                                 @else
                                     <span class="badge bg-secondary-lt">Nonaktif</span>
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
            {{ $budgetGroups->links() }}
        </div>
    </div>



    <x-tabler.modal-confirmation wire:key="modal-confirm-delete-budget-group" id="modal-confirm-delete-budget-group"
        title="Konfirmasi Hapus" message="Apakah Anda yakin ingin menghapus {{ $deleteItemName ?? '' }}?"
        confirm-text="Ya, Hapus" cancel-text="Batal" component-id="{{ $this->getId() }}"
        on-confirm="handleConfirmDeleteAction" />
    <x-tabler.modal wire:key="modal-budget-group" id="modal-budget-group" :title="$modalTitle" onHide="resetForm"
        component-id="{{ $this->getId() }}">
        <x-slot:body>
            <form wire:submit="save" id="form-budget-group">
                <div class="mb-3">
                    <label class="form-label">Kode</label>
                    <input type="text" wire:model="code" class="form-control" placeholder="Contoh: A, B, C">
                    @error('code')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" wire:model="name" class="form-control" placeholder="Nama kelompok">
                    @error('name')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Deskripsi (Opsional)</label>
                    <textarea wire:model="description" class="form-control" rows="3" placeholder="Deskripsi kelompok"></textarea>
                    @error('description')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
<div class="mb-3">
                     <label class="form-label">Persentase Alokasi (0-100%)</label>
                     <div class="input-group">
                         <input type="number" wire:model="percentage" class="form-control" placeholder="30"
                             step="1" min="0" max="100">
                         <span class="input-group-text">%</span>
                     </div>
                     <small class="form-hint">Kosongkan jika tidak ada batasan persentase untuk kelompok ini.</small>
                     @error('percentage')
                         <div class="d-block invalid-feedback">{{ $message }}</div>
                     @enderror
                 </div>
                 <div class="mb-3">
                     <label class="form-label">Tipe Proposal</label>
                     <select wire:model="proposal_type" class="form-select">
                         <option value="">Semua Tipe (Grandfather)</option>
                         <option value="research">Penelitian</option>
                         <option value="community_service">Pengabdian Masyarakat</option>
                     </select>
                     <small class="form-hint">Pilih "Semua Tipe" agar grup muncul di kedua jenis proposal.</small>
                     @error('proposal_type')
                         <div class="d-block invalid-feedback">{{ $message }}</div>
                     @enderror
                 </div>
                 <div class="mb-3">
                     <label class="form-label">Tipe Batasan Persentase</label>
                     <select wire:model="percentage_type" class="form-select">
                         <option value="">Tidak ada batasan</option>
                         <option value="max">Maksimal (Max)</option>
                         <option value="min">Minimal (Min)</option>
                     </select>
                     <small class="form-hint">Max: persentase tidak boleh melebihi. Min: persentase harus mencapai minimal.</small>
                     @error('percentage_type')
                         <div class="d-block invalid-feedback">{{ $message }}</div>
                     @enderror
                 </div>
                 <div class="mb-3">
                     <div class="form-check">
                         <input type="checkbox" wire:model="is_active" class="form-check-input" id="is_active">
                         <label class="form-check-label" for="is_active">Aktif</label>
                     </div>
                     <small class="form-hint">Jika tidak aktif, grup tidak akan muncul di form RAB.</small>
                     @error('is_active')
                         <div class="d-block invalid-feedback">{{ $message }}</div>
                     @enderror
                 </div>
             </form>
        </x-slot:body>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" form="form-budget-group" class="btn btn-primary" wire:loading.class="btn-loading"
                wire:target="save">Simpan</button>
        </x-slot:footer>
    </x-tabler.modal>

</div>
