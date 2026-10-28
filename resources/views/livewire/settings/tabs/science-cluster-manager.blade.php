<div>
    <x-tabler.alert />

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h3 class="mb-0">Manajemen Cluster Ilmu</h3>
    </div>

    {{-- Main Split Panel Layout --}}
    <div class="row g-3">
        {{-- Left Panel: Tree View --}}
        <div class="col-md-4 col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0 card-title">Hierarki Cluster</h4>
                    <div class="card-actions">
                        <button type="button" class="btn btn-primary btn-sm" wire:click="startAddCluster(null)"
                            title="Tambah Level 1">
                            <x-lucide-plus class="icon" />
                        </button>
                    </div>
                </div>
                <div class="p-2 card-body">
                    {{-- Search Box --}}
                    <div class="mb-2">
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <x-lucide-search class="icon" />
                            </span>
                            <input type="text" class="form-control form-control-sm" placeholder="Cari cluster..."
                                wire:model.live.debounce.300ms="search">
                        </div>
                    </div>

                    {{-- Add Cluster Form --}}
                    @if ($addingCluster)
                        <div class="bg-success-subtle mb-2 p-2 border rounded">
                            <div class="mb-2">
                                <input type="text"
                                    class="form-control form-control-sm @error('newClusterName') is-invalid @enderror"
                                    wire:model="newClusterName" placeholder="Nama cluster baru..."
                                    wire:keydown.enter="saveNewCluster" wire:keydown.escape="cancelAddCluster"
                                    autofocus>
                                @error('newClusterName')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @if ($newClusterParentId)
                                    <small class="text-muted">
                                        Parent: {{ \App\Models\ScienceCluster::find($newClusterParentId)?->name }}
                                    </small>
                                @endif
                            </div>
                            <div class="mb-2 d-flex gap-2">
                                <label class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" wire:model="newIsActiveForResearch">
                                    <span class="form-check-label" style="font-size: 0.75rem;">Penelitian</span>
                                </label>
                                <label class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" wire:model="newIsActiveForCommunityService">
                                    <span class="form-check-label" style="font-size: 0.75rem;">Pengabdian</span>
                                </label>
                            </div>
                            <div class="btn-list">
                                <button type="button" class="btn btn-success btn-sm" wire:click="saveNewCluster"
                                    wire:loading.class="btn-loading" wire:target="saveNewCluster" title="Simpan" aria-label="Simpan">
                                    <x-lucide-check class="icon" />
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" wire:click="cancelAddCluster" title="Batal" aria-label="Batal">
                                    <x-lucide-x class="icon" />
                                </button>
                            </div>
                        </div>
                    @endif

                    {{-- Tree View --}}
                    <div class="cluster-tree" style="max-height: 500px; overflow-y: auto;">
                        @forelse($this->level1Clusters as $level1)
                            <div class="cluster-tree-item" wire:key="l1-{{ $level1->id }}">
                                {{-- Level 1 Header --}}
                                <div class="d-flex align-items-center cluster-tree-header {{ $selectedClusterId === $level1->id ? 'bg-primary-subtle' : '' }} rounded px-2 py-1"
                                    style="cursor: pointer;"
                                    @if ($editingClusterId !== $level1->id) wire:click="toggleLevel1({{ $level1->id }})" @endif>

                                    @if ($editingClusterId === $level1->id)
                                        {{-- Edit Mode --}}
                                        <div class="d-flex flex-column flex-grow-1 gap-1 py-1" wire:click.stop>
                                            <div class="d-flex align-items-center gap-1">
                                                <input type="text"
                                                    class="form-control form-control-sm @error('clusterNameInput') is-invalid @enderror"
                                                    wire:model="clusterNameInput" wire:keydown.enter="saveCluster"
                                                    wire:keydown.escape="cancelEditCluster" autofocus
                                                    style="font-size: 0.85rem;">
                                                <button type="button" class="p-1 btn btn-success btn-sm"
                                                    wire:click="saveCluster" wire:loading.class="btn-loading"
                                                    wire:target="saveCluster" title="Simpan" aria-label="Simpan">
                                                    <x-lucide-check class="icon" style="width: 14px; height: 14px;" />
                                                </button>
                                                <button type="button" class="p-1 btn btn-secondary btn-sm"
                                                    wire:click="cancelEditCluster" title="Batal" aria-label="Batal">
                                                    <x-lucide-x class="icon" style="width: 14px; height: 14px;" />
                                                </button>
                                            </div>
                                            <div class="d-flex gap-2 mt-1">
                                                <label class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" wire:model="editIsActiveForResearch">
                                                    <span class="form-check-label text-muted" style="font-size: 0.65rem;">Penelitian</span>
                                                </label>
                                                <label class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" wire:model="editIsActiveForCommunityService">
                                                    <span class="form-check-label text-muted" style="font-size: 0.65rem;">Pengabdian</span>
                                                </label>
                                            </div>
                                        </div>
                                    @else
                                        {{-- Expand/Collapse Icon --}}
                                        <span class="me-1">
                                            @if ($this->isLevel1Expanded($level1->id))
                                                <x-lucide-chevron-down class="icon"
                                                    style="width: 16px; height: 16px;" />
                                            @else
                                                <x-lucide-chevron-right class="icon"
                                                    style="width: 16px; height: 16px;" />
                                            @endif
                                        </span>

                                        {{-- Cluster Name with Icon --}}
                                        <x-lucide-folder class="me-1 text-primary icon"
                                            style="width: 14px; height: 14px;" />
                                        <span class="flex-grow-1 text-truncate fw-semibold" style="font-size: 0.85rem;"
                                            title="{{ $level1->name }}"
                                            wire:click.stop="selectCluster({{ $level1->id }})">
                                            {{ $level1->name }}
                                        </span>

                                        {{-- Children Count Badge --}}
                                        <span class="bg-secondary-subtle ms-1 text-secondary badge">
                                            {{ $level1->children_count }}
                                        </span>

                                        {{-- Action Buttons --}}
                                        <div class="btn-group ms-1" wire:click.stop>
                                            <button type="button" class="p-1 btn btn-ghost-success btn-sm"
                                                wire:click="startAddCluster({{ $level1->id }})"
                                                title="Tambah Sub-Cluster">
                                                <x-lucide-plus class="icon" style="width: 12px; height: 12px;" />
                                            </button>
                                            <button type="button" class="p-1 btn btn-ghost-primary btn-sm"
                                                wire:click="startEditCluster({{ $level1->id }})" title="Rename">
                                                <x-lucide-pencil class="icon" style="width: 12px; height: 12px;" />
                                            </button>
                                            <button type="button" class="p-1 btn btn-ghost-danger btn-sm"
                                                wire:click="confirmDelete({{ $level1->id }})"
                                                wire:loading.attr="disabled" title="Hapus">
                                                <x-lucide-trash-2 class="icon" style="width: 12px; height: 12px;" />
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                {{-- Level 2 Children (Collapsible) --}}
                                @if ($this->isLevel1Expanded($level1->id) && $level1->children->isNotEmpty())
                                    <div class="ms-2 ps-3 border-start cluster-tree-levels">
                                        @foreach ($level1->children as $level2)
                                            <div class="cluster-tree-item" wire:key="l2-{{ $level2->id }}">
                                                {{-- Level 2 Header --}}
                                                <div class="d-flex align-items-center cluster-tree-header {{ $selectedClusterId === $level2->id ? 'bg-primary text-white' : '' }} rounded px-2 py-1"
                                                    style="cursor: pointer; font-size: 0.8rem;"
                                                    @if ($editingClusterId !== $level2->id) wire:click="toggleLevel2({{ $level2->id }})" @endif>

                                                    @if ($editingClusterId === $level2->id)
                                                        {{-- Edit Mode --}}
                                                        <div class="d-flex flex-column flex-grow-1 gap-1 py-1"
                                                            wire:click.stop>
                                                            <div class="d-flex align-items-center gap-1">
                                                                <input type="text"
                                                                    class="form-control form-control-sm @error('clusterNameInput') is-invalid @enderror"
                                                                    wire:model="clusterNameInput"
                                                                    wire:keydown.enter="saveCluster"
                                                                    wire:keydown.escape="cancelEditCluster" autofocus
                                                                    style="font-size: 0.75rem;">
                                                                <button type="button" class="p-1 btn btn-success btn-sm"
                                                                    wire:click="saveCluster" title="Simpan" aria-label="Simpan">
                                                                    <x-lucide-check class="icon"
                                                                        style="width: 12px; height: 12px;" />
                                                                </button>
                                                                <button type="button"
                                                                    class="p-1 btn btn-secondary btn-sm"
                                                                    wire:click="cancelEditCluster" title="Batal" aria-label="Batal">
                                                                    <x-lucide-x class="icon"
                                                                        style="width: 12px; height: 12px;" />
                                                                </button>
                                                            </div>
                                                            <div class="d-flex gap-2 mt-1">
                                                                <label class="form-check form-switch mb-0">
                                                                    <input class="form-check-input" type="checkbox" wire:model="editIsActiveForResearch">
                                                                    <span class="form-check-label text-muted" style="font-size: 0.65rem;">Penelitian</span>
                                                                </label>
                                                                <label class="form-check form-switch mb-0">
                                                                    <input class="form-check-input" type="checkbox" wire:model="editIsActiveForCommunityService">
                                                                    <span class="form-check-label text-muted" style="font-size: 0.65rem;">Pengabdian</span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    @else
                                                        {{-- Expand/Collapse Icon --}}
                                                        <span class="me-1">
                                                            @if ($this->isLevel2Expanded($level2->id))
                                                                <x-lucide-chevron-down class="icon"
                                                                    style="width: 14px; height: 14px;" />
                                                            @else
                                                                <x-lucide-chevron-right class="icon"
                                                                    style="width: 14px; height: 14px;" />
                                                            @endif
                                                        </span>

                                                        {{-- Cluster Name with Icon --}}
                                                        <x-lucide-folder-open class="me-1 text-success icon"
                                                            style="width: 12px; height: 12px;" />
                                                        <span class="flex-grow-1 text-truncate"
                                                            wire:click.stop="selectCluster({{ $level2->id }})">
                                                            {{ $level2->name }}
                                                        </span>

                                                        {{-- Children Count Badge --}}
                                                        <span
                                                            class="badge {{ $selectedClusterId === $level2->id ? 'bg-white text-primary' : 'bg-secondary-subtle text-secondary' }}"
                                                            style="font-size: 0.65rem;">
                                                            {{ $level2->children_count }}
                                                        </span>

                                                        {{-- Action Buttons --}}
                                                        <div class="btn-group ms-1" wire:click.stop>
                                                            <button type="button"
                                                                class="p-1 btn btn-ghost-success btn-sm"
                                                                wire:click="startAddCluster({{ $level2->id }})"
                                                                title="Tambah Sub-Cluster">
                                                                <x-lucide-plus class="icon"
                                                                    style="width: 10px; height: 10px;" />
                                                            </button>
                                                            <button type="button"
                                                                class="p-1 btn btn-ghost-primary btn-sm"
                                                                wire:click="startEditCluster({{ $level2->id }})"
                                                                title="Rename">
                                                                <x-lucide-pencil class="icon"
                                                                    style="width: 10px; height: 10px;" />
                                                            </button>
                                                            <button type="button"
                                                                class="p-1 btn btn-ghost-danger btn-sm"
                                                                wire:click="confirmDelete({{ $level2->id }})"
                                                                wire:loading.attr="disabled" title="Hapus">
                                                                <x-lucide-trash-2 class="icon"
                                                                    style="width: 10px; height: 10px;" />
                                                            </button>
                                                        </div>
                                                    @endif
                                                </div>

                                                {{-- Level 3 Children (Collapsible) --}}
                                                @if ($this->isLevel2Expanded($level2->id) && $level2->children->isNotEmpty())
                                                    <div class="ms-2 ps-3 border-start cluster-tree-levels">
                                                        @foreach ($level2->children as $level3)
                                                            <div class="d-flex align-items-center cluster-tree-header {{ $selectedClusterId === $level3->id ? 'bg-primary text-white' : '' }} rounded px-2 py-1"
                                                                style="cursor: pointer; font-size: 0.75rem;"
                                                                wire:click="selectCluster({{ $level3->id }})"
                                                                wire:key="l3-{{ $level3->id }}">

                                                                @if ($editingClusterId === $level3->id)
                                                                    {{-- Edit Mode --}}
                                                                    <div class="d-flex flex-column flex-grow-1 gap-1 py-1"
                                                                        wire:click.stop>
                                                                        <div class="d-flex align-items-center gap-1">
                                                                            <input type="text"
                                                                                class="form-control form-control-sm @error('clusterNameInput') is-invalid @enderror"
                                                                                wire:model="clusterNameInput"
                                                                                wire:keydown.enter="saveCluster"
                                                                                wire:keydown.escape="cancelEditCluster"
                                                                                autofocus style="font-size: 0.7rem;">
                                                                            <button type="button"
                                                                                class="p-1 btn btn-success btn-sm"
                                                                                wire:click="saveCluster" title="Simpan" aria-label="Simpan">
                                                                                <x-lucide-check class="icon"
                                                                                    style="width: 10px; height: 10px;" />
                                                                            </button>
                                                                            <button type="button"
                                                                                class="p-1 btn btn-secondary btn-sm"
                                                                                wire:click="cancelEditCluster" title="Batal" aria-label="Batal">
                                                                                <x-lucide-x class="icon"
                                                                                    style="width: 10px; height: 10px;" />
                                                                            </button>
                                                                        </div>
                                                                        <div class="d-flex gap-2 mt-1">
                                                                            <label class="form-check form-switch mb-0">
                                                                                <input class="form-check-input" type="checkbox" wire:model="editIsActiveForResearch">
                                                                                <span class="form-check-label text-muted" style="font-size: 0.65rem;">Penelitian</span>
                                                                            </label>
                                                                            <label class="form-check form-switch mb-0">
                                                                                <input class="form-check-input" type="checkbox" wire:model="editIsActiveForCommunityService">
                                                                                <span class="form-check-label text-muted" style="font-size: 0.65rem;">Pengabdian</span>
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                @else
                                                                    <span class="me-1">
                                                                        <x-lucide-minus class="icon"
                                                                            style="width: 12px; height: 12px;" />
                                                                    </span>

                                                                    {{-- Cluster Name with Icon --}}
                                                                    <x-lucide-file-text class="me-1 text-orange icon"
                                                                        style="width: 10px; height: 10px;" />
                                                                    <span class="flex-grow-1 text-truncate">
                                                                        {{ $level3->name }}
                                                                    </span>

                                                                    {{-- Action Buttons --}}
                                                                    <div class="btn-group ms-1" wire:click.stop>
                                                                        <button type="button"
                                                                            class="p-1 btn btn-ghost-primary btn-sm"
                                                                            wire:click="startEditCluster({{ $level3->id }})"
                                                                            title="Rename">
                                                                            <x-lucide-pencil class="icon"
                                                                                style="width: 9px; height: 9px;" />
                                                                        </button>
                                                                        <button type="button"
                                                                            class="p-1 btn btn-ghost-danger btn-sm"
                                                                            wire:click="confirmDelete({{ $level3->id }})"
                                                                            wire:loading.attr="disabled"
                                                                            title="Hapus">
                                                                            <x-lucide-trash-2 class="icon"
                                                                                style="width: 9px; height: 9px;" />
                                                                        </button>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="py-3 text-muted text-center">
                                @if ($search)
                                    Tidak ada cluster yang cocok
                                @else
                                    Belum ada cluster
                                @endif
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Panel: Detail --}}
        <div class="col-md-8 col-lg-7">
            @if ($this->selectedCluster)
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="mb-1 card-title">
                                {{ $this->selectedCluster->name }}
                                <span class="bg-secondary-subtle ms-2 text-secondary badge">
                                    Level {{ $this->selectedCluster->level }}
                                </span>
                            </div>
                            <div class="text-muted" style="font-size: 0.8rem;">
                                @if ($this->selectedCluster->parent)
                                    Parent: <strong>{{ $this->selectedCluster->parent->name }}</strong>
                                @else
                                    Cluster Level 1 (Root)
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        {{-- Cluster Info --}}
                        <div class="mb-4 row">
                            <div class="col-md-4">
                                <div class="mb-2">
                                    <label class="mb-1 text-muted form-label">Nama Cluster</label>
                                    <div class="fw-semibold">{{ $this->selectedCluster->name }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-2">
                                    <label class="mb-1 text-muted form-label">Jumlah Sub-Cluster</label>
                                    <div class="fw-semibold">{{ $this->selectedCluster->children->count() }}</div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="mb-2">
                                    <label class="mb-1 text-muted form-label">Status Akses</label>
                                    <div class="d-flex gap-2 mt-1">
                                        @if($this->selectedCluster->is_active_for_research)
                                            <span class="badge bg-success text-success-fg">Penelitian</span>
                                        @else
                                            <span class="badge bg-secondary text-secondary-fg">Penelitian</span>
                                        @endif
                                        @if($this->selectedCluster->is_active_for_community_service)
                                            <span class="badge bg-success text-success-fg">Pengabdian</span>
                                        @else
                                            <span class="badge bg-secondary text-secondary-fg">Pengabdian</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Sub-Clusters Section --}}
                        @if ($this->selectedCluster->level < 3)
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label class="mb-0 form-label fw-semibold">
                                        Sub-Cluster Level {{ $this->selectedCluster->level + 1 }}
                                        <span class="bg-secondary-subtle ms-1 text-secondary badge">
                                            {{ $this->selectedCluster->children->count() }}
                                        </span>
                                    </label>
                                    <button type="button" class="btn btn-primary btn-sm"
                                        wire:click="startAddCluster({{ $this->selectedCluster->id }})">
                                        <x-lucide-plus class="me-1 icon" /> Tambah Sub-Cluster
                                    </button>
                                </div>

                                <div class="table-responsive">
                                    <table class="card-table table table-hover table-vcenter">
                                        <thead>
                                            <tr>
                                                <th>Nama</th>
                                                <th style="width: 100px;">Sub-Cluster</th>
                                                <th style="width: 100px;">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($this->selectedCluster->children as $child)
                                                <tr wire:key="child-{{ $child->id }}"
                                                    wire:click="selectCluster({{ $child->id }})"
                                                    style="cursor: pointer;">
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            @if ($child->level === 2)
                                                                <x-lucide-folder-open class="me-2 text-success icon" />
                                                            @else
                                                                <x-lucide-file-text class="me-2 text-orange icon" />
                                                            @endif
                                                            {{ $child->name }}
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="bg-secondary-subtle text-secondary badge">
                                                            {{ $child->children_count }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="flex-nowrap btn-list" wire:click.stop>
                                                            <button type="button"
                                                                class="p-1 btn btn-ghost-primary btn-sm"
                                                                wire:click="startEditCluster({{ $child->id }})"
                                                                title="Edit">
                                                                <x-lucide-pencil class="icon" />
                                                            </button>
                                                            <button type="button"
                                                                class="p-1 btn btn-ghost-danger btn-sm"
                                                                wire:click="confirmDelete({{ $child->id }})"
                                                                wire:loading.attr="disabled" title="Hapus">
                                                                <x-lucide-trash-2 class="icon" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="py-4 text-muted text-center">
                                                        <x-lucide-inbox class="mb-2 icon"
                                                            style="width: 32px; height: 32px;" />
                                                        <div>Belum ada sub-cluster</div>
                                                        <button type="button" class="mt-2 btn btn-primary btn-sm"
                                                            wire:click="startAddCluster({{ $this->selectedCluster->id }})">
                                                            <x-lucide-plus class="me-1 icon" /> Tambah Sub-Cluster
                                                            Pertama
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <x-lucide-info class="me-2 icon" />
                                Cluster Level 3 adalah level terakhir dan tidak dapat memiliki sub-cluster.
                            </div>
                        @endif
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
                            <p class="empty-title">Pilih Cluster Ilmu</p>
                            <p class="text-muted empty-subtitle">
                                Pilih cluster dari panel kiri untuk melihat detail dan mengelola sub-cluster.
                            </p>
                            @if ($this->level1Clusters->isEmpty())
                                <div class="empty-action">
                                    <button type="button" class="btn btn-primary"
                                        wire:click="startAddCluster(null)">
                                        <x-lucide-plus class="me-1 icon" /> Tambah Cluster Level 1 Pertama
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
        .cluster-tree-header:hover {
            background-color: var(--tblr-bg-surface-secondary);
        }

        .cluster-tree-levels {
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

        .btn-ghost-success {
            color: var(--tblr-success);
            background: transparent;
            border: none;
        }

        .btn-ghost-success:hover {
            background-color: var(--tblr-success-bg-subtle);
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
        <x-tabler.modal-confirmation wire:key="modal-confirm-delete-science-cluster"
            id="modal-confirm-delete-science-cluster" title="Konfirmasi Hapus"
            message="Apakah Anda yakin ingin menghapus {{ $deleteItemName ?? '' }}? Tindakan ini akan menghapus semua sub-cluster di bawahnya."
            confirm-text="Ya, Hapus" cancel-text="Batal" component-id="{{ $this->getId() }}"
            on-confirm="handleConfirmDeleteAction" />
    @endteleport
</div>
