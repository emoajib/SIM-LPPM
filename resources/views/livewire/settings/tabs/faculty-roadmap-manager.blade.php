<div>
    <x-tabler.alert />
    <div class="card">
        <div class="d-flex align-items-center justify-content-between card-header">
            <h3 class="card-title">Peta Jalan Fakultas</h3>
            <button type="button" class="btn btn-primary" wire:click='create' data-bs-toggle="modal"
                data-bs-target="#modal-faculty-roadmap">
                <x-lucide-plus class="icon" />
                Tambah Peta Jalan
            </button>
        </div>
        <div class="table-responsive">
            <table class="card-table table table-vcenter">
                <thead>
                    <tr>
                        <th>Fakultas</th>
                        <th>Judul</th>
                        <th>Periode</th>
                        <th>Status</th>
                        <th class="w-10">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roadmaps as $item)
                        <tr wire:key="f-roadmap-{{ $item->id }}">
                            <td>{{ $item->faculty?->name ?? 'N/A' }}</td>
                            <td>
                                <div class="font-weight-medium">{{ $item->title }}</div>
                                @if($item->document_url)
                                    <a href="{{ $item->document_url }}" target="_blank" class="text-secondary small">
                                        <x-lucide-external-link class="icon icon-sm" /> Lihat Dokumen
                                    </a>
                                @endif
                            </td>
                            <td>{{ $item->period_start }} - {{ $item->period_end }}</td>
                            <td>
                                @if($item->is_active)
                                    <span class="badge bg-success text-success-fg">Aktif</span>
                                @else
                                    <span class="badge bg-danger text-danger-fg">Nonaktif</span>
                                @endif
                            </td>
                            <td>
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

    <x-tabler.modal-confirmation wire:key="modal-confirm-delete-faculty-roadmap" id="modal-confirm-delete-faculty-roadmap"
        title="Konfirmasi Hapus" message="Apakah Anda yakin ingin menghapus Peta Jalan: {{ $deleteItemName ?? '' }}?"
        confirm-text="Ya, Hapus" cancel-text="Batal" component-id="{{ $this->getId() }}"
        on-confirm="handleConfirmDeleteAction" />

    <x-tabler.modal wire:key="modal-faculty-roadmap" id="modal-faculty-roadmap" :title="$modalTitle" onHide="resetForm"
        component-id="{{ $this->getId() }}">
        <x-slot:body>
            <form wire:submit="save" id="form-faculty-roadmap">
                <div class="mb-3">
                    <label class="form-label required">Fakultas</label>
                    <select wire:model="facultyId" class="form-select" @if(active_has_role('dekan')) disabled @endif>
                        <option value="">-- Pilih Fakultas --</option>
                        @foreach ($faculties as $faculty)
                            <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                        @endforeach
                    </select>
                    @error('facultyId')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label required">Judul Peta Jalan</label>
                    <input type="text" wire:model="title" class="form-control" placeholder="Contoh: Peta Jalan Riset Fakultas Sains 2024-2028">
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
                    <textarea wire:model="vision" class="form-control" rows="2" placeholder="Visi roadmap penelitian..."></textarea>
                    @error('vision')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Tema Strategis (Garis Besar)</label>
                    <div class="text-muted small mb-1">Masukkan setiap tema di baris baru (tekan Enter).</div>
                    <textarea wire:model="strategicThemesInput" class="form-control" rows="4" placeholder="- Tema 1...&#10;- Tema 2..."></textarea>
                    @error('strategicThemesInput')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3" x-data="{ init() { new TomSelect($refs.select, {plugins: ['remove_button']}) } }">
                    <label class="form-label">Keterkaitan Area Fokus Institusi</label>
                    <div wire:ignore>
                        <select x-ref="select" wire:model="focusAreaIds" class="form-select" multiple>
                            @foreach ($focusAreas as $area)
                                <option value="{{ $area->id }}">{{ $area->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('focusAreaIds')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">URL Dokumen Lengkap (Google Drive / Cloud)</label>
                    <input type="url" wire:model="documentUrl" class="form-control" placeholder="https://...">
                    @error('documentUrl')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" wire:model="isActive">
                        <span class="form-check-label">Aktif (Dapat digunakan sebagai rujukan Prodi)</span>
                    </label>
                </div>
            </form>
        </x-slot:body>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" form="form-faculty-roadmap" class="btn btn-primary" wire:loading.class="btn-loading"
                wire:target="save">Simpan</button>
        </x-slot:footer>
    </x-tabler.modal>
</div>
