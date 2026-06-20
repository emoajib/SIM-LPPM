<div>
    <x-tabler.alert />
    <div class="card">
        <div class="d-flex align-items-center justify-content-between card-header">
            <h3 class="card-title">Peta Jalan Program Studi</h3>
            @if($canMutate)
            <button type="button" class="btn btn-primary" wire:click='create' data-bs-toggle="modal"
                data-bs-target="#modal-study-program-roadmap">
                <x-lucide-plus class="icon" />
                Tambah Peta Jalan
            </button>
            @endif
        </div>
        <div class="table-responsive">
            <table class="card-table table table-vcenter">
                <thead>
                    <tr>
                        <th>Program Studi</th>
                        <th>Rujukan Fakultas</th>
                        <th>Judul & Periode</th>
                        <th>Status</th>
                        <th class="w-10">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roadmaps as $item)
                        <tr wire:key="sp-roadmap-{{ $item->id }}">
                            <td>{{ $item->studyProgram?->name ?? 'N/A' }}</td>
                            <td>
                                @if($item->facultyRoadmap)
                                    <div class="font-weight-medium">{{ $item->facultyRoadmap->title }}</div>
                                    <div class="text-muted small">Fak. {{ $item->facultyRoadmap->faculty?->name ?? '' }}</div>
                                @else
                                    <span class="text-muted italic">Tidak Merujuk Fakultas</span>
                                @endif
                            </td>
                            <td>
                                <div class="font-weight-medium">{{ $item->title }}</div>
                                <div class="text-muted small">{{ $item->period_start }} - {{ $item->period_end }}</div>
                            </td>
                            <td>
                                @if($item->is_active)
                                    <span class="badge bg-success text-success-fg">Aktif</span>
                                @else
                                    <span class="badge bg-danger text-danger-fg">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                @if($canMutate)
                                <div class="btn-list flex-nowrap">
                                    <button type="button" class="btn-outline-warning btn btn-sm"
                                        wire:click="edit('{{ $item->id }}')">
                                        Edit
                                    </button>
                                    <button type="button" class="btn-outline-danger btn btn-sm"
                                        wire:click="confirmDelete('{{ $item->id }}')" wire:loading.attr="disabled">
                                        Hapus
                                    </button>
                                </div>
                                @else
                                <span class="text-muted small">Lihat Saja</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $roadmaps->links() }}
        </div>
    </div>

    @if($canMutate)
    <x-tabler.modal-confirmation wire:key="modal-confirm-delete-study-program-roadmap" id="modal-confirm-delete-study-program-roadmap"
        title="Konfirmasi Hapus" message="Apakah Anda yakin ingin menghapus Peta Jalan: {{ $deleteItemName ?? '' }}?"
        confirm-text="Ya, Hapus" cancel-text="Batal" component-id="{{ $this->getId() }}"
        on-confirm="handleConfirmDeleteAction" />

    <x-tabler.modal wire:key="modal-study-program-roadmap" id="modal-study-program-roadmap" :title="$modalTitle" onHide="resetForm"
        component-id="{{ $this->getId() }}">
        <x-slot:body>
            <form wire:submit="save" id="form-study-program-roadmap">
                <div class="mb-3">
                    <label class="form-label required">Program Studi</label>
                    <select wire:model="studyProgramId" class="form-select" @if(active_has_role('kaprodi')) disabled @endif>
                        <option value="">-- Pilih Prodi --</option>
                        @foreach ($studyPrograms as $sp)
                            <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                        @endforeach
                    </select>
                    @error('studyProgramId')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Rujukan Peta Jalan Fakultas</label>
                    <select wire:model="facultyRoadmapId" class="form-select">
                        <option value="">-- Tidak Merujuk (Tidak Disarankan) --</option>
                        @foreach ($facultyRoadmaps as $fr)
                            <option value="{{ $fr->id }}">[{{ $fr->faculty?->name }}] {{ $fr->title }}</option>
                        @endforeach
                    </select>
                    @error('facultyRoadmapId')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label required">Judul Peta Jalan</label>
                    <input type="text" wire:model="title" class="form-control" placeholder="Contoh: Roadmap Riset TI 2024-2028">
                    @error('title')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label required">Periode Mulai</label>
                            <input type="number" wire:model="periodStart" class="form-control" placeholder="YYYY">
                            @error('periodStart')
                                <div class="d-block invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label required">Periode Selesai</label>
                            <input type="number" wire:model="periodEnd" class="form-control" placeholder="YYYY">
                            @error('periodEnd')
                                <div class="d-block invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Visi / Dekripsi Singkat</label>
                    <textarea wire:model="vision" class="form-control" rows="2" placeholder="Visi roadmap prodi..."></textarea>
                    @error('vision')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Pohon Riset (Research Tree)</label>
                    <div class="text-muted small mb-1">Masukkan setiap sub-bidang / topik di baris baru (tekan Enter).</div>
                    <textarea wire:model="researchTreeInput" class="form-control" rows="4" placeholder="- Data Mining...&#10;- Software Engineering..."></textarea>
                    @error('researchTreeInput')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Keselarasan CPL (Capaian Pembelajaran Lulusan)</label>
                    <textarea wire:model="cplAlignment" class="form-control" rows="2" placeholder="Penjelasan keselarasan dengan CPL..."></textarea>
                    @error('cplAlignment')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label required">Target TKT Minimal</label>
                            <input type="number" wire:model="tktTargetMin" class="form-control" min="1" max="9">
                            @error('tktTargetMin')
                                <div class="d-block invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label required">Target TKT Maksimal</label>
                            <input type="number" wire:model="tktTargetMax" class="form-control" min="1" max="9">
                            @error('tktTargetMax')
                                <div class="d-block invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" wire:model="isActive">
                        <span class="form-check-label">Aktif (Dapat digunakan sebagai rujukan Proposal Dosen)</span>
                    </label>
                </div>
            </form>
        </x-slot:body>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" form="form-study-program-roadmap" class="btn btn-primary" wire:loading.class="btn-loading"
                wire:target="save">Simpan</button>
        </x-slot:footer>
    </x-tabler.modal>
    @endif
</div>
