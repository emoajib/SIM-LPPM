<div>
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="mb-1">Kriteria Penilaian</h2>
            <p class="text-muted">Kelola kriteria dan bobot penilaian untuk proposal penelitian dan pengabdian
                masyarakat.</p>
        </div>
    </div>

    <div class="row">
        {{-- Penelitian --}}
        <div class="mb-4 col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title">Penelitian</h3>
                    <button class="btn btn-sm btn-primary" wire:click="openCreate('research')">
                        
                        Tambah Kriteria
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="card-table table table-vcenter text-nowrap datatable">
                        <thead>
                            <tr>
                                <th class="w-1">No</th>
                                <th>Kriteria</th>
                                <th>Acuan / Deskripsi</th>
                                <th class="text-center">Bobot (%)</th>
                                <th class="text-center">Status</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->researchCriterias as $criteria)
                                <tr>
                                    <td>{{ $criteria->order }}</td>
                                    <td>{{ $criteria->criteria }}</td>
                                    <td class="text-muted text-wrap small">{{ $criteria->description }}</td>
                                    <td class="text-center">{{ number_format($criteria->weight, 0) }}%</td>
                                    <td class="text-center">
                                        <label class="form-check-inline m-0 form-check form-switch">
                                            <input class="form-check-input" type="checkbox"
                                                wire:click="toggleActive({{ $criteria->id }})"
                                                @if ($criteria->is_active) checked @endif>
                                        </label>
                                    </td>
                                    <td>
                                        <div class="btn-list gap-1">
                                            <button class="btn btn-icon btn-ghost-primary"
                                                wire:click="edit({{ $criteria->id }})">
                                                
                                            </button>
                                            <button class="btn btn-icon btn-ghost-danger"
                                                wire:click="delete({{ $criteria->id }})"
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus kriteria ini?')">
                                                <x-lucide-trash-2 class="icon" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="bg-body-tertiary">
                                <td colspan="3" class="text-end fw-bold">Total Bobot:</td>
                                <td class="text-center fw-bold">
                                    {{ number_format($this->researchCriterias->sum('weight'), 0) }}%</td>
                                <td colspan="2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Pengabdian --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title">Pengabdian Masyarakat (PkM)</h3>
                    <button class="btn btn-sm btn-primary" wire:click="openCreate('community_service')">
                        
                        Tambah Kriteria
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="card-table table table-vcenter text-nowrap datatable">
                        <thead>
                            <tr>
                                <th class="w-1">No</th>
                                <th>Kriteria</th>
                                <th>Acuan / Deskripsi</th>
                                <th class="text-center">Bobot (%)</th>
                                <th class="text-center">Status</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->pkmCriterias as $criteria)
                                <tr>
                                    <td>{{ $criteria->order }}</td>
                                    <td>{{ $criteria->criteria }}</td>
                                    <td class="text-muted text-wrap small">{{ $criteria->description }}</td>
                                    <td class="text-center">{{ number_format($criteria->weight, 0) }}%</td>
                                    <td class="text-center">
                                        <label class="form-check-inline m-0 form-check form-switch">
                                            <input class="form-check-input" type="checkbox"
                                                wire:click="toggleActive({{ $criteria->id }})"
                                                @if ($criteria->is_active) checked @endif>
                                        </label>
                                    </td>
                                    <td>
                                        <div class="btn-list gap-1">
                                            <button class="btn btn-icon btn-ghost-primary"
                                                wire:click="edit({{ $criteria->id }})">
                                                
                                            </button>
                                            <button class="btn btn-icon btn-ghost-danger"
                                                wire:click="delete({{ $criteria->id }})"
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus kriteria ini?')">
                                                <x-lucide-trash-2 class="icon" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="bg-body-tertiary">
                                <td colspan="3" class="text-end fw-bold">Total Bobot:</td>
                                <td class="text-center fw-bold">
                                    {{ number_format($this->pkmCriterias->sum('weight'), 0) }}%</td>
                                <td colspan="2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <div class="row mt-4">
        <div class="col-12 mb-4">
            <h2 class="mb-1">Monitoring & Evaluasi (Monev)</h2>
            <p class="text-muted">Kelola kriteria penilaian laporan akhir untuk monitoring dan evaluasi.</p>
        </div>

        {{-- Monev Penelitian --}}
        <div class="mb-4 col-12">
            <div class="card shadow-sm border-blue-lt">
                <div class="card-status-start bg-blue"></div>
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title">Monev Penelitian</h3>
                    <button class="btn btn-sm btn-primary" wire:click="openCreate('monev_research')">
                        
                        Tambah Kriteria
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="card-table table table-vcenter text-nowrap datatable">
                        <thead>
                            <tr>
                                <th class="w-1">No</th>
                                <th>Kriteria</th>
                                <th>Deskripsi</th>
                                <th class="text-center">Bobot (%)</th>
                                <th class="text-center">Status</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->monevResearchCriterias as $criteria)
                                <tr>
                                    <td>{{ $criteria->order }}</td>
                                    <td>{{ $criteria->criteria }}</td>
                                    <td class="text-muted text-wrap small">{{ $criteria->description }}</td>
                                    <td class="text-center">{{ number_format($criteria->weight, 0) }}%</td>
                                    <td class="text-center">
                                        <label class="form-check-inline m-0 form-check form-switch">
                                            <input class="form-check-input" type="checkbox"
                                                wire:click="toggleActive({{ $criteria->id }})"
                                                @if ($criteria->is_active) checked @endif>
                                        </label>
                                    </td>
                                    <td>
                                        <div class="btn-list gap-1">
                                            <button class="btn btn-icon btn-ghost-primary"
                                                wire:click="edit({{ $criteria->id }})">
                                                
                                            </button>
                                            <button class="btn btn-icon btn-ghost-danger"
                                                wire:click="delete({{ $criteria->id }})"
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus kriteria ini?')">
                                                <x-lucide-trash-2 class="icon" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="bg-body-tertiary">
                                <td colspan="3" class="text-end fw-bold">Total Bobot:</td>
                                <td class="text-center fw-bold">
                                    {{ number_format($this->monevResearchCriterias->sum('weight'), 0) }}%</td>
                                <td colspan="2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Monev Pengabdian --}}
        <div class="col-12">
            <div class="card shadow-sm border-green-lt">
                <div class="card-status-start bg-green"></div>
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title">Monev Pengabdian Masyarakat (PkM)</h3>
                    <button class="btn btn-sm btn-primary" wire:click="openCreate('monev_community_service')">
                        
                        Tambah Kriteria
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="card-table table table-vcenter text-nowrap datatable">
                        <thead>
                            <tr>
                                <th class="w-1">No</th>
                                <th>Kriteria</th>
                                <th>Deskripsi</th>
                                <th class="text-center">Bobot (%)</th>
                                <th class="text-center">Status</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->monevPkmCriterias as $criteria)
                                <tr>
                                    <td>{{ $criteria->order }}</td>
                                    <td>{{ $criteria->criteria }}</td>
                                    <td class="text-muted text-wrap small">{{ $criteria->description }}</td>
                                    <td class="text-center">{{ number_format($criteria->weight, 0) }}%</td>
                                    <td class="text-center">
                                        <label class="form-check-inline m-0 form-check form-switch">
                                            <input class="form-check-input" type="checkbox"
                                                wire:click="toggleActive({{ $criteria->id }})"
                                                @if ($criteria->is_active) checked @endif>
                                        </label>
                                    </td>
                                    <td>
                                        <div class="btn-list gap-1">
                                            <button class="btn btn-icon btn-ghost-primary"
                                                wire:click="edit({{ $criteria->id }})">
                                                
                                            </button>
                                            <button class="btn btn-icon btn-ghost-danger"
                                                wire:click="delete({{ $criteria->id }})"
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus kriteria ini?')">
                                                <x-lucide-trash-2 class="icon" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="bg-body-tertiary">
                                <td colspan="3" class="text-end fw-bold">Total Bobot:</td>
                                <td class="text-center fw-bold">
                                    {{ number_format($this->monevPkmCriterias->sum('weight'), 0) }}%</td>
                                <td colspan="2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
    @if ($editing)
        <div class="modal modal-blur fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Kriteria Penilaian</h5>
                        <button type="button" class="btn-close" wire:click="cancelEdit"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required">Nama Kriteria</label>
                            <input type="text" class="form-control @error('editing.criteria') is-invalid @enderror"
                                wire:model="editing.criteria">
                            @error('editing.criteria')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Deskripsi / Acuan Penilaian</label>
                            <textarea class="form-control @error('editing.description') is-invalid @enderror" rows="3"
                                wire:model="editing.description"></textarea>
                            @error('editing.description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Bobot (%)</label>
                            <div class="input-group">
                                <input type="number" step="0.01"
                                    class="form-control @error('editing.weight') is-invalid @enderror"
                                    wire:model="editing.weight">
                                <span class="input-group-text">%</span>
                                @error('editing.weight')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" wire:click="cancelEdit">
                            Batal
                        </button>
                        <button type="button" class="ms-auto btn btn-primary" wire:click="save">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Create Modal --}}
    @if ($creating)
        <div class="modal modal-blur fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Kriteria Penilaian</h5>
                        <button type="button" class="btn-close" wire:click="cancelCreate"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required">Jenis Kriteria</label>
                            <select class="form-select @error('creating.type') is-invalid @enderror"
                                wire:model="creating.type" disabled>
                                <option value="">-- Pilih Jenis --</option>
                                <option value="research">Penelitian</option>
                                <option value="community_service">Pengabdian Masyarakat (PkM)</option>
                                <option value="monev_research">Monev Penelitian</option>
                                <option value="monev_community_service">Monev Pengabdian Masyarakat (PkM)</option>
                            </select>
                            @error('creating.type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Nama Kriteria</label>
                            <input type="text" class="form-control @error('creating.criteria') is-invalid @enderror"
                                wire:model="creating.criteria" placeholder="Contoh: Originalitas, Metodologi, dll">
                            @error('creating.criteria')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Deskripsi / Acuan Penilaian</label>
                            <textarea class="form-control @error('creating.description') is-invalid @enderror" rows="3"
                                wire:model="creating.description" placeholder="Jelaskan kriteria penilaian ini..."></textarea>
                            @error('creating.description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Bobot (%)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100"
                                    class="form-control @error('creating.weight') is-invalid @enderror"
                                    wire:model="creating.weight" placeholder="Contoh: 25">
                                <span class="input-group-text">%</span>
                                @error('creating.weight')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="text-muted">Pastikan total bobot semua kriteria = 100%</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" wire:click="cancelCreate">
                            Batal
                        </button>
                        <button type="button" class="ms-auto btn btn-primary" wire:click="createCriteria">
                            Tambah Kriteria
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
