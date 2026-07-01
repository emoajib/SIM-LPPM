<div>
    <x-tabler.alert />
    <div class="card">
        <div class="d-flex align-items-center justify-content-between card-header">
            <h3 class="card-title">Skema Penelitian</h3>
            <button type="button" class="btn btn-primary" wire:click='create' data-bs-toggle="modal"
                data-bs-target="#modal-research-scheme">
                <x-lucide-plus class="icon" />
                Tambah Skema Penelitian
            </button>
        </div>
        <div class="table-responsive">
            <table class="card-table table table-vcenter">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Strata</th>
                        <th class="w-25">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($researchSchemes as $item)
                        <tr wire:key="scheme-{{ $item->id }}">
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->strata }}</td>
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
            {{ $researchSchemes->links() }}
        </div>
    </div>



    <x-tabler.modal-confirmation wire:key="modal-confirm-delete-research-scheme"
        id="modal-confirm-delete-research-scheme" title="Konfirmasi Hapus"
        message="Apakah Anda yakin ingin menghapus {{ $deleteItemName ?? '' }}?" confirm-text="Ya, Hapus"
        cancel-text="Batal" component-id="{{ $this->getId() }}" on-confirm="handleConfirmDeleteAction" />
    <x-tabler.modal wire:key="modal-research-scheme" id="modal-research-scheme" :title="$modalTitle" onHide="resetForm"
        component-id="{{ $this->getId() }}">
        <x-slot:body>
            <form wire:submit="save" id="form-research-scheme">
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" wire:model="name" class="form-control" placeholder="Enter name">
                    @error('name')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Strata</label>
                    <select wire:model="strata" class="form-control">
                        <option value="">Select strata</option>
                        @foreach($strataOptions as $opt)
                            <option value="{{ $opt->name }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                    @error('strata')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Minimum Target TKT <span class="text-muted">(opsional)</span></label>
                        <input type="number" wire:model="min_tkt" class="form-control" placeholder="1-9" min="1" max="9">
                        <small class="form-hint">Kosongkan untuk mengikuti aturan Strata bawaan.</small>
                        @error('min_tkt')
                            <div class="d-block invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Maksimum Target TKT <span class="text-muted">(opsional)</span></label>
                        <input type="number" wire:model="max_tkt" class="form-control" placeholder="1-9" min="1" max="9">
                        @error('max_tkt')
                            <div class="d-block invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <hr class="my-4">
                <h4 class="mb-3 text-primary">Info Eligibilitas (Kriteria Pengusul)</h4>

                <div class="mb-3">
                    <label class="form-label">Jabatan Fungsional Minimal / Diperbolehkan</label>
                    <div class="row g-2">
                        @foreach($functionalPositionOptions as $position)
                            <div class="col-6">
                                <label class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox"
                                        wire:model="allowed_functional_positions" value="{{ $position }}">
                                    <span class="form-check-label">{{ $position }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Minimal Skor SINTA</label>
                            <input type="number" wire:model="min_sinta_score" class="form-control"
                                placeholder="Kosongkan jika tidak ada">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Min. Skor Scopus</label>
                            <input type="number" wire:model="min_scopus_score" class="form-control"
                                placeholder="H-Index">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Min. Mahasiswa</label>
                            <input type="number" wire:model="min_students_involved" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Maks. Usulan (Ketua)</label>
                            <input type="number" wire:model="max_proposals_as_head" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Maks. Usulan (Anggota)</label>
                            <input type="number" wire:model="max_proposals_as_member" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Maks. Total Usulan (Ketua - Semua Skema)</label>
                            <input type="number" wire:model="max_total_proposals_as_head" class="form-control"
                                placeholder="Contoh: 1 untuk mencegah ketua di skema lain">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Maks. Total Usulan (Anggota - Semua Proposal)</label>
                            <input type="number" wire:model="max_total_proposals_as_member" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Minimal Anggota</label>
                            <input type="number" wire:model="min_members" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Maksimal Anggota</label>
                            <input type="number" wire:model="max_members" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="row align-items-center mb-3">
                    <div class="col-md-6">
                        <label class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" wire:model.live="require_cross_prodi">
                            <span class="form-check-label">Wajib Lintas Prodi (Anggota)</span>
                        </label>
                    </div>
                    @if($require_cross_prodi)
                        <div class="col-md-6">
                            <div class="input-group input-group-flat">
                                <span class="input-group-text">Min anggota lintas prodi:</span>
                                <input type="number" wire:model="min_cross_prodi_members" class="form-control ps-1" min="1">
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mb-3">
                    <label class="form-label">Blokir jika punya Tunggakan Laporan/Luaran</label>
                    <select wire:model="pending_report_block_role" class="form-select">
                        <option value="none">Tidak Ada Blokir</option>
                        <option value="leader">Hanya Ketua</option>
                        <option value="member">Hanya Anggota</option>
                        <option value="both">Keduanya (Ketua & Anggota)</option>
                    </select>
                </div>
            </form>
        </x-slot:body>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" form="form-research-scheme" class="btn btn-primary" wire:loading.class="btn-loading"
                wire:target="save">Simpan</button>
        </x-slot:footer>
    </x-tabler.modal>

</div>