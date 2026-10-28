<div>
    <x-tabler.alert />

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Manajemen TKT</h3>
    </div>

    {{-- Main Split Panel Layout --}}
    <div class="row g-3">
        {{-- Left Panel: Tree View --}}
        <div class="col-md-4 col-lg-3">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Kategori TKT</h4>
                    <div class="card-actions">
                        <button type="button" class="btn btn-primary btn-sm" wire:click="startAddCategory"
                            title="Tambah Kategori">
                            <x-lucide-plus class="icon" />
                        </button>
                    </div>
                </div>
                <div class="card-body p-2">
                    {{-- Search Box --}}
                    <div class="mb-2">
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <x-lucide-search class="icon" />
                            </span>
                            <input type="text" class="form-control form-control-sm" placeholder="Cari kategori..."
                                wire:model.live.debounce.300ms="search">
                        </div>
                    </div>

                    {{-- Add Category Form --}}
                    @if ($addingCategory)
                        <div class="mb-2 p-2 border rounded">
                            <div class="mb-2">
                                <input type="text" class="form-control form-control-sm @error('newCategoryName') is-invalid @enderror"
                                    wire:model="newCategoryName" placeholder="Nama kategori baru..."
                                    wire:keydown.enter="saveNewCategory" wire:keydown.escape="cancelAddCategory"
                                    autofocus>
                                @error('newCategoryName')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="btn-list">
                                <button type="button" class="btn btn-success btn-sm" wire:click="saveNewCategory" wire:loading.class="btn-loading" wire:target="saveNewCategory" title="Simpan" aria-label="Simpan">
                                    <x-lucide-check class="icon" />
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" wire:click="cancelAddCategory" title="Batal" aria-label="Batal">
                                    <x-lucide-x class="icon" />
                                </button>
                            </div>
                        </div>
                    @endif

                    {{-- Tree View --}}
                    <div class="tkt-tree" style="max-height: 500px; overflow-y: auto;">
                        @forelse($this->typesWithLevels as $type => $levels)
                            <div class="tkt-tree-item" wire:key="type-{{ md5($type) }}">
                                {{-- Category Header --}}
                                <div class="d-flex align-items-center py-1 px-2 rounded tkt-tree-header
                                    {{ $selectedType === $type ? 'bg-primary-subtle' : '' }}"
                                    style="cursor: pointer;"
                                    @if ($editingCategoryName !== $type)
                                        wire:click="toggleType('{{ $type }}')"
                                    @endif>

                                    @if ($editingCategoryName === $type)
                                        {{-- Edit Category Name --}}
                                        <div class="flex-grow-1 d-flex align-items-center gap-1" wire:click.stop>
                                            <input type="text"
                                                class="form-control form-control-sm @error('categoryNameInput') is-invalid @enderror"
                                                wire:model="categoryNameInput"
                                                wire:keydown.enter="saveCategory"
                                                wire:keydown.escape="cancelEditCategory"
                                                autofocus
                                                style="font-size: 0.85rem;">
                                            <button type="button" class="btn btn-success btn-sm p-1" wire:click="saveCategory" wire:loading.class="btn-loading" wire:target="saveCategory" title="Simpan" aria-label="Simpan">
                                                <x-lucide-check class="icon" style="width: 14px; height: 14px;" />
                                            </button>
                                            <button type="button" class="btn btn-secondary btn-sm p-1" wire:click="cancelEditCategory" title="Batal" aria-label="Batal">
                                                <x-lucide-x class="icon" style="width: 14px; height: 14px;" />
                                            </button>
                                        </div>
                                    @else
                                        {{-- Expand/Collapse Icon --}}
                                        <span class="me-1">
                                            @if ($this->isTypeExpanded($type))
                                                <x-lucide-chevron-down class="icon" style="width: 16px; height: 16px;" />
                                            @else
                                                <x-lucide-chevron-right class="icon" style="width: 16px; height: 16px;" />
                                            @endif
                                        </span>

                                        {{-- Category Name --}}
                                        <span class="flex-grow-1 text-truncate" style="font-size: 0.85rem; font-weight: 500;"
                                            title="{{ $type }}">
                                            {{ $type }}
                                        </span>

                                        {{-- Level Count Badge --}}
                                        <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $levels->count() }}</span>

                                        {{-- Action Buttons --}}
                                        <div class="btn-group ms-1" wire:click.stop>
                                            <button type="button" class="btn btn-ghost-primary btn-sm p-1"
                                                wire:click="startEditCategory('{{ $type }}')" title="Rename">
                                                <x-lucide-pencil class="icon" style="width: 12px; height: 12px;" />
                                            </button>
                                            <button type="button" class="btn btn-ghost-danger btn-sm p-1"
                                                wire:click="confirmDeleteCategory('{{ $type }}')"
                                                wire:loading.attr="disabled"
                                                title="Hapus">
                                                <x-lucide-trash-2 class="icon" style="width: 12px; height: 12px;" />
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                {{-- Levels List (Collapsible) --}}
                                @if ($this->isTypeExpanded($type))
                                    <div class="tkt-tree-levels ps-3 border-start ms-2">
                                        @foreach ($levels as $level)
                                            <div class="d-flex align-items-center py-1 px-2 rounded tkt-level-item
                                                {{ $selectedLevelId === $level->id ? 'bg-primary text-white' : '' }}"
                                                style="cursor: pointer; font-size: 0.8rem;"
                                                wire:click="selectLevel({{ $level->id }})"
                                                wire:key="level-{{ $level->id }}">
                                                <span class="me-2">
                                                    <x-lucide-minus class="icon" style="width: 12px; height: 12px;" />
                                                </span>
                                                <span class="flex-grow-1">Level {{ $level->level }}</span>
                                                <span class="badge {{ $selectedLevelId === $level->id ? 'bg-white text-primary' : 'bg-secondary-subtle text-secondary' }}"
                                                    style="font-size: 0.65rem;">
                                                    {{ $level->indicators->count() }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-muted text-center py-3">
                                @if ($search)
                                    Tidak ada kategori yang cocok
                                @else
                                    Belum ada kategori TKT
                                @endif
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Panel: Detail --}}
        <div class="col-md-8 col-lg-9">
            @if ($this->selectedLevel)
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title mb-1">
                                {{ $selectedType }} - Level {{ $this->selectedLevel->level }}
                            </div>
                            <div class="text-muted" style="font-size: 0.8rem;">
                                Kelola deskripsi dan indikator untuk level ini
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        {{-- Level Description Section --}}
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0 fw-semibold">Pengaturan Level & Deskripsi</label>
                                @if (!$editingLevelDesc)
                                    <button type="button" class="btn btn-ghost-primary btn-sm"
                                        wire:click="startEditLevelDesc">
                                        <x-lucide-pencil class="icon me-1" /> Edit
                                    </button>
                                @endif
                            </div>

                            @if ($editingLevelDesc)
                                <div>
                                    <textarea class="form-control mb-3 @error('levelDescriptionInput') is-invalid @enderror"
                                        wire:model="levelDescriptionInput" rows="3"
                                        wire:keydown.escape="cancelEditLevelDesc"></textarea>
                                    @error('levelDescriptionInput')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror

                                    <div class="d-flex gap-3 mb-3">
                                        <label class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" wire:model="editIsActiveForResearch">
                                            <span class="form-check-label text-muted" style="font-size: 0.85rem;">Aktif untuk Penelitian</span>
                                        </label>
                                        <label class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" wire:model="editIsActiveForCommunityService">
                                            <span class="form-check-label text-muted" style="font-size: 0.85rem;">Aktif untuk Pengabdian</span>
                                        </label>
                                    </div>

                                    <div class="mt-2 btn-list">
                                        <button type="button" class="btn btn-primary btn-sm" wire:click="saveLevelDesc" wire:loading.class="btn-loading" wire:target="saveLevelDesc">
                                            <x-lucide-check class="icon me-1" /> Simpan
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm"
                                            wire:click="cancelEditLevelDesc">
                                            Batal
                                        </button>
                                    </div>
                                </div>
                            @else
                                <div class="p-3 rounded border mb-3">
                                    {{ $this->selectedLevel->description ?: 'Belum ada deskripsi' }}
                                </div>
                                <div class="d-flex gap-2">
                                    <span class="text-muted me-2" style="font-size: 0.85rem;">Status Akses:</span>
                                    @if($this->selectedLevel->is_active_for_research)
                                        <span class="badge bg-success text-success-fg">Penelitian</span>
                                    @else
                                        <span class="badge bg-secondary text-secondary-fg">Penelitian</span>
                                    @endif
                                    @if($this->selectedLevel->is_active_for_community_service)
                                        <span class="badge bg-success text-success-fg">Pengabdian</span>
                                    @else
                                        <span class="badge bg-secondary text-secondary-fg">Pengabdian</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Indicators Section --}}
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0 fw-semibold">
                                    Indikator
                                    <span class="badge bg-secondary-subtle text-secondary ms-1">
                                        {{ $this->indicators->count() }}
                                    </span>
                                </label>
                                @if (!$addingIndicator)
                                    <button type="button" class="btn btn-primary btn-sm" wire:click="startAddIndicator">
                                        <x-lucide-plus class="icon me-1" /> Tambah Indikator
                                    </button>
                                @endif
                            </div>

                            {{-- Add Indicator Form --}}
                            @if ($addingIndicator)
                                <div class="p-3 mb-3 border rounded">
                                    <div class="row g-2">
                                        <div class="col-md-2">
                                            <label class="form-label">Kode</label>
                                            <input type="text"
                                                class="form-control form-control-sm @error('newIndicatorCode') is-invalid @enderror"
                                                wire:model="newIndicatorCode" placeholder="1.1">
                                            @error('newIndicatorCode')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-10">
                                            <label class="form-label">Teks Indikator</label>
                                            <textarea class="form-control form-control-sm @error('newIndicatorText') is-invalid @enderror"
                                                wire:model="newIndicatorText" rows="2"
                                                wire:keydown.escape="cancelAddIndicator"
                                                placeholder="Deskripsi indikator..."></textarea>
                                            @error('newIndicatorText')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="mt-2 btn-list">
                                        <button type="button" class="btn btn-success btn-sm" wire:click="saveNewIndicator" wire:loading.class="btn-loading" wire:target="saveNewIndicator">
                                            <x-lucide-check class="icon me-1" /> Simpan
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm"
                                            wire:click="cancelAddIndicator">
                                            Batal
                                        </button>
                                    </div>
                                </div>
                            @endif

                            {{-- Indicators Table --}}
                            <div class="table-responsive">
                                <table class="table table-vcenter card-table table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width: 80px;">Kode</th>
                                            <th>Indikator</th>
                                            <th style="width: 100px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($this->indicators as $indicator)
                                            <tr wire:key="indicator-{{ $indicator->id }}">
                                                @if ($editingIndicatorId === $indicator->id)
                                                    {{-- Edit Mode --}}
                                                    <td>
                                                        <input type="text"
                                                            class="form-control form-control-sm @error('indicatorCodeInput') is-invalid @enderror"
                                                            wire:model="indicatorCodeInput"
                                                            wire:keydown.escape="cancelEditIndicator">
                                                    </td>
                                                    <td>
                                                        <textarea
                                                            class="form-control form-control-sm @error('indicatorTextInput') is-invalid @enderror"
                                                            wire:model="indicatorTextInput" rows="2"
                                                            wire:keydown.escape="cancelEditIndicator"></textarea>
                                                        @error('indicatorTextInput')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </td>
                                                    <td>
                                                        <div class="btn-list flex-nowrap">
                                                            <button type="button" class="btn btn-success btn-sm p-1"
                                                                wire:click="saveIndicator" wire:loading.class="btn-loading" wire:target="saveIndicator" title="Simpan">
                                                                <x-lucide-check class="icon" />
                                                            </button>
                                                            <button type="button" class="btn btn-secondary btn-sm p-1"
                                                                wire:click="cancelEditIndicator" title="Batal">
                                                                <x-lucide-x class="icon" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                @else
                                                    {{-- Read Mode --}}
                                                    <td>
                                                        <span class="badge bg-secondary-subtle text-secondary">
                                                            {{ $indicator->code ?: '-' }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $indicator->indicator }}</td>
                                                    <td>
                                                        <div class="btn-list flex-nowrap">
                                                            <button type="button" class="btn btn-ghost-primary btn-sm p-1"
                                                                wire:click="startEditIndicator({{ $indicator->id }})"
                                                                title="Edit">
                                                                <x-lucide-pencil class="icon" />
                                                            </button>
                                                             <button type="button" class="btn btn-ghost-danger btn-sm p-1"
                                                                wire:click="confirmDeleteIndicator({{ $indicator->id }}, '{{ $indicator->indicator }}')"
                                                                wire:loading.attr="disabled"
                                                                title="Hapus">
                                                                <x-lucide-trash-2 class="icon" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                @endif
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-muted text-center py-4">
                                                    <x-lucide-inbox class="icon mb-2" style="width: 32px; height: 32px;" />
                                                    <div>Belum ada indikator untuk level ini</div>
                                                    <button type="button" class="btn btn-primary btn-sm mt-2"
                                                        wire:click="startAddIndicator">
                                                        <x-lucide-plus class="icon me-1" /> Tambah Indikator Pertama
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                {{-- Empty State --}}
                <div class="card">
                    <div class="card-body">
                        <div class="empty">
                            <div class="empty-img">
                                <x-lucide-layers class="icon" style="width: 64px; height: 64px; opacity: 0.5;" />
                            </div>
                            <p class="empty-title">Pilih Level TKT</p>
                            <p class="empty-subtitle text-muted">
                                Pilih kategori dan level dari panel kiri untuk melihat dan mengelola indikator TKT.
                            </p>
                            @if ($this->types->isEmpty())
                                <div class="empty-action">
                                    <button type="button" class="btn btn-primary" wire:click="startAddCategory">
                                        <x-lucide-plus class="icon me-1" /> Tambah Kategori TKT Pertama
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Custom Styles --}}
    <style>
        .tkt-tree-header:hover {
            background-color: var(--tblr-bg-surface-secondary);
        }

        .tkt-level-item:hover:not(.bg-primary) {
            background-color: var(--tblr-bg-surface-secondary);
        }

        .tkt-tree-levels {
            border-color: var(--tblr-border-color) !important;
        }

        .btn-ghost-primary {
            color: var(--tblr-primary);
            background: transparent;
            border: none;
        }

        .btn-ghost-primary:hover {
            background-color: var(--tblr-primary-bg-subtle);
        }

        .btn-ghost-danger {
            color: var(--tblr-danger);
            background: transparent;
            border: none;
        }

        .btn-ghost-danger:hover {
            background-color: var(--tblr-danger-bg-subtle);
        }
    </style>

    

    
@teleport('body')
<x-tabler.modal-confirmation wire:key="modal-confirm-delete-tkt-category" id="modal-confirm-delete-tkt-category" title="Konfirmasi Hapus Kategori"
        message="Apakah Anda yakin ingin menghapus kategori {{ $deleteType ?? '' }} beserta semua level dan indikatornya?"
        confirm-text="Ya, Hapus" cancel-text="Batal" component-id="{{ $this->getId() }}"
        on-confirm="handleConfirmDeleteCategory" />
<x-tabler.modal-confirmation wire:key="modal-confirm-delete-tkt-indicator" id="modal-confirm-delete-tkt-indicator" title="Konfirmasi Hapus Indikator"
        message="Apakah Anda yakin ingin menghapus indikator ini?" confirm-text="Ya, Hapus" cancel-text="Batal"
        component-id="{{ $this->getId() }}" on-confirm="handleConfirmDeleteIndicator" />
@endteleport
</div>
