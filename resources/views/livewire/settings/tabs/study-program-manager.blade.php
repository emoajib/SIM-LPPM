<div>
    <x-tabler.alert />
    <div class="card">
        <div class="d-flex align-items-center justify-content-between card-header">
            <h3 class="card-title">Program Studi</h3>
            <button type="button" class="btn btn-primary" wire:click='create' data-bs-toggle="modal"
                data-bs-target="#modal-study-program">
                <x-lucide-plus class="icon" />
                Tambah Program Studi
            </button>
        </div>
        <div class="table-responsive">
            <table class="card-table table table-vcenter">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Kode</th>
                        <th>Institusi</th>
                        <th>Fakultas</th>
                        <th>Kaprodi</th>
                        <th>Status Roadmap</th>
                        <th class="w-25">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($studyPrograms as $item)
                        <tr wire:key="program-{{ $item->id }}">
                            <td>{{ $item->name }}</td>
                            <td><span class="badge bg-blue text-blue-fg">{{ $item->code ?? '-' }}</span></td>
                            <td>{{ $item->institution?->name ?? 'N/A' }}</td>
                            <td>{{ $item->faculty?->name ?? 'N/A' }}</td>
                            <td>
                                @if($item->kaprodi)
                                    <span class="text-primary fw-bold">{{ $item->kaprodi->name }}</span>
                                @else
                                    <span class="text-secondary fst-italic">Belum ditetapkan</span>
                                @endif
                            </td>
                            <td>
                                @if(\App\Models\Setting::get('feature_roadmap_active', false))
                                    @php
                                        $statusColors = [
                                            'draft' => 'secondary',
                                            'submitted' => 'warning',
                                            'approved' => 'success',
                                            'rejected' => 'danger',
                                        ];
                                        $color = $statusColors[$item->roadmap_status ?? 'draft'] ?? 'secondary';
                                        $label = match($item->roadmap_status ?? 'draft') {
                                            'draft' => 'Draft',
                                            'submitted' => 'Menunggu Validasi',
                                            'approved' => 'Disetujui Dekan',
                                            'rejected' => 'Ditolak',
                                            default => 'Draft'
                                        };
                                    @endphp
                                    <x-tabler.badge :color="$color">{{ $label }}</x-tabler.badge>
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-list">
                                    @if($item->roadmap_status === 'submitted' && active_has_any_role(['dekan', 'admin lppm', 'superadmin']))
                                        <button type="button" class="btn btn-sm btn-success"
                                            wire:click="$set('validatingProgramId', '{{ $item->id }}'); $dispatch('open-modal', { modalId: 'modal-roadmap-validation' })">
                                            <x-lucide-check class="icon me-1" /> Validasi
                                        </button>
                                    @endif
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
            {{ $studyPrograms->links() }}
        </div>
    </div>

    <x-tabler.modal-confirmation wire:key="modal-confirm-delete-study-program" id="modal-confirm-delete-study-program"
        title="Konfirmasi Hapus" message="Apakah Anda yakin ingin menghapus {{ $deleteItemName ?? '' }}?"
        confirm-text="Ya, Hapus" cancel-text="Batal" component-id="{{ $this->getId() }}"
        on-confirm="handleConfirmDeleteAction" />

    <x-tabler.modal wire:key="modal-roadmap-validation" id="modal-roadmap-validation"
        title="Validasi Roadmap Program Studi" onHide="resetRoadmapValidation" component-id="{{ $this->getId() }}">
        <x-slot:body>
            <div class="mb-3">
                <label class="form-label">Catatan Validasi</label>
                <textarea wire:model="roadmapValidationNotes" class="form-control" rows="4"
                    placeholder="Tambahkan catatan validasi (opsional)..."></textarea>
                <div class="form-text">Catatan akan dikirimkan kepada Kaprodi sebagai feedback.</div>
            </div>
        </x-slot:body>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="button" class="btn btn-danger" wire:click="rejectRoadmap('{{ $validatingProgramId }}')"
                wire:loading.attr="disabled">
                <x-lucide-x class="icon me-1" /> Tolak
            </button>
            <button type="button" class="btn btn-success" wire:click="approveRoadmap('{{ $validatingProgramId }}')"
                wire:loading.attr="disabled">
                <x-lucide-check class="icon me-1" /> Setujui
            </button>
        </x-slot:footer>
    </x-tabler.modal>
    <x-tabler.modal wire:key="modal-study-program" id="modal-study-program" :title="$modalTitle" onHide="resetForm"
        component-id="{{ $this->getId() }}">
        <x-slot:body>
            <form wire:submit="save" id="form-study-program">
                <div class="mb-4">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link @if($activeTab === 'basic') active @endif"
                                wire:click="$set('activeTab', 'basic')" type="button">
                                Data Dasar
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link @if($activeTab === 'kaprodi') active @endif"
                                wire:click="$set('activeTab', 'kaprodi')" type="button">
                                Kaprodi
                            </button>
                        </li>
                        @if(\App\Models\Setting::get('feature_roadmap_active', false))
                            <li class="nav-item" role="presentation">
                                <button class="nav-link @if($activeTab === 'roadmap') active @endif"
                                    wire:click="$set('activeTab', 'roadmap')" type="button">
                                    Peta Jalan (Roadmap)
                                </button>
                            </li>
                        @endif
                    </ul>
                </div>

                @if($activeTab === 'basic')
                    <div class="row">
                        <div class="col-md-9">
                            <div class="mb-3">
                                <label class="form-label">Nama</label>
                                <input type="text" wire:model="name" class="form-control" placeholder="Enter name">
                                @error('name')
                                    <div class="d-block invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Kode</label>
                                <input type="text" wire:model="code" class="form-control" placeholder="e.g. TI">
                                @error('code')
                                    <div class="d-block invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Institusi</label>
                        <select wire:model.live="institutionId" class="form-control">
                            <option value="">Select institution</option>
                            @foreach ($institutions as $institution)
                                <option value="{{ $institution->id }}">{{ $institution->name }}</option>
                            @endforeach
                        </select>
                        @error('institutionId')
                            <div class="d-block invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fakultas</label>
                        <select wire:model="facultyId" class="form-control" wire:key="faculty-select-{{ $institutionId }}">
                            <option value="">Select faculty</option>
                            @foreach ($faculties as $faculty)
                                <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                            @endforeach
                        </select>
                        @error('facultyId')
                            <div class="d-block invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                @endif

                @if($activeTab === 'kaprodi')
                    <div class="mb-3">
                        <label class="form-label">Ketua Program Studi (Kaprodi)</label>
                        <select wire:model="kaprodiUserId" class="form-control" x-data="tomSelect">
                            <option value="">Belum ditetapkan</option>
                            @foreach ($kaprodiUsers as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                        <div class="form-text">Pilih dosen yang akan ditugaskan sebagai Kaprodi untuk program studi ini.</div>
                    </div>
                @endif

                @if($activeTab === 'roadmap' && \App\Models\Setting::get('feature_roadmap_active', false))
                    <div class="alert alert-info border-0 shadow-sm mb-4">
                        <div class="d-flex align-items-start">
                            <x-lucide-info class="me-2 icon" />
                            <div>
                                <strong>Peta Jalan Penelitian Prodi</strong>
                                <p class="mb-0 small">Isi roadmap berdasarkan CPL dan fokus keilmuan program studi. Roadmap ini akan digunakan sebagai acuan alignment proposal dosen.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Periode Roadmap</label>
                        <input type="text" wire:model="researchRoadmap.period" class="form-control" placeholder="2025-2029">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Prioritas Tahunan</label>
                        <div class="border p-3 rounded bg-light">
                            @forelse($researchRoadmap['priorities'] as $index => $priority)
                                <div class="card mb-2 shadow-sm" wire:key="priority-{{ $index }}">
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-md-2">
                                                <label class="form-label small">Tahun</label>
                                                <input type="number" wire:model="researchRoadmap.priorities.{{ $index }}.year" class="form-control form-control-sm">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">Topik Riset</label>
                                                <input type="text" wire:model="researchRoadmap.priorities.{{ $index }}.themes" class="form-control form-control-sm" placeholder="Topik 1, Topik 2">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">Fokus TKT</label>
                                                <input type="text" wire:model="researchRoadmap.priorities.{{ $index }}.tkt_focus" class="form-control form-control-sm" placeholder="1-3">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">Alokasi Dana</label>
                                                <input type="text" wire:model="researchRoadmap.priorities.{{ $index }}.funding" class="form-control form-control-sm" placeholder="Internal, BIMA">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label small">Alignment CPL</label>
                                                <textarea wire:model="researchRoadmap.priorities.{{ $index }}.cpl_alignment" class="form-control form-control-sm" rows="1" placeholder="Keterkaitan dengan CPL prodi..."></textarea>
                                            </div>
                                        </div>
                                        <div class="mt-2 text-end">
                                            <button type="button" class="btn btn-sm btn-danger" wire:click="$set('researchRoadmap.priorities.{{ $index }}', null)">Hapus</button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-secondary py-3">Belum ada prioritas tahunan. Klik tombol di bawah untuk menambahkan.</div>
                            @endforelse
                            <button type="button" class="btn btn-sm btn-primary" wire:click="$push('researchRoadmap.priorities', { year: {{ now()->year }}, themes: '', tkt_focus: '', funding: '', cpl_alignment: '' })">
                                <x-lucide-plus class="icon me-1" /> Tambah Prioritas
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Pohon Riset</label>
                        <textarea wire:model="researchRoadmap.research_tree" class="form-control" rows="3" placeholder="Fundamental, Applied, Development (pisahkan dengan koma)"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Indikator Keberhasilan</label>
                        <textarea wire:model="researchRoadmap.success_indicators" class="form-control" rows="3" placeholder="Publications, HKI, Student involvement (pisahkan dengan koma)"></textarea>
                    </div>
                @endif
            </form>
        </x-slot:body>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" form="form-study-program" class="btn btn-primary" wire:loading.class="btn-loading"
                wire:target="save">Simpan</button>
        </x-slot:footer>
    </x-tabler.modal>
</div>
