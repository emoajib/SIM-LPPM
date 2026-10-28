<div>
    <x-tabler.alert />
    <div class="card border-0 shadow-sm" style="border-radius: 16px;">
        <div class="d-flex align-items-center justify-content-between card-header bg-white py-3">
            <div>
                <h3 class="card-title font-weight-bold">Pengaturan Master IKU</h3>
                <div class="text-muted small">Kelola target dan rumus pencapaian IKU</div>
            </div>
            <button class="btn btn-primary btn-sm rounded-pill shadow-sm" wire:click="create">
                
                Tambah IKU
            </button>
        </div>
        <div class="table-responsive">
            <table class="card-table table table-vcenter">
                <thead>
                    <tr>
                        <th class="w-1">Kode</th>
                        <th>Indikator Kinerja Utama</th>
                        <th class="text-center">Target</th>
                        <th class="text-center">Bobot</th>
                        <th class="text-center">Mode</th>
                        <th class="text-center">Status</th>
                        <th class="w-1">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr wire:key="master-iku-{{ $item->id }}">
                            <td class="font-weight-bold text-primary">{{ $item->code }}</td>
                            <td>
                                <div class="font-weight-semi-bold">{{ $item->name }}</div>
                                <div class="text-muted small">{{ Str::limit($item->description, 80) }}</div>
                            </td>
                            <td class="text-center">
                                <span
                                    class="badge bg-blue-lt px-2 py-1">{{ number_format($item->target_percentage, 1) }}%</span>
                            </td>
                            <td class="text-center">
                                <span
                                    class="text-dark font-weight-medium">{{ number_format($item->internal_weight, 1) }}%</span>
                            </td>
                            <td class="text-center">
                                @php $isManual = (bool) \App\Models\Setting::where('key', "iku_manual_{$item->code}")->value('value'); @endphp
                                @if($isManual)
                                    <span class="badge bg-warning-lt" title="Input Manual Diaktifkan">
                                        <x-lucide-edit-3 class="icon icon-sm me-1" />
                                        Manual
                                    </span>
                                @else
                                    <span class="badge bg-success-lt" title="Rumus Otomatis Aktif">
                                        
                                        Otomatis
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch mb-0 d-inline-block">
                                    <input class="form-check-input" type="checkbox"
                                        wire:click="toggleStatus({{ $item->id }})" @if($item->is_active) checked @endif>
                                </div>
                            </td>
                            <td>
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill"
                                    wire:click="edit({{ $item->id }})">
                                    
                                    Atur
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0">
            {{ $items->links() }}
        </div>
    </div>

    <!-- Modal Edit Master IKU -->
    <x-tabler.modal wire:key="modal-master-iku" id="modal-master-iku" :title="$modalTitle" onHide="resetForm"
        component-id="{{ $this->getId() }}" size="lg">
        <x-slot:body>
            <form wire:submit="save" id="form-master-iku">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Kode IKU <span class="text-danger">*</span></label>
                        <input type="text" wire:model="code" class="form-control" placeholder="Contoh: IKU-10"
                            @if($editingId) readonly disabled @endif>
                        @error('code') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label font-weight-bold">Nama Indikator <span
                                class="text-danger">*</span></label>
                        <input type="text" wire:model="name" class="form-control"
                            placeholder="Contoh: Publikasi Terapan">
                        @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-12">
                        <label class="form-label font-weight-bold">Deskripsi</label>
                        <textarea wire:model="description" class="form-control" rows="2"
                            placeholder="Penjelasan singkat indikator..."></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label font-weight-bold text-primary">Target Capaian (%)</label>
                        <div class="input-group">
                            <input type="number" step="0.1" wire:model="target_percentage" class="form-control"
                                placeholder="0.0">
                            <span class="input-group-text">%</span>
                        </div>
                        @error('target_percentage') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label font-weight-bold text-info">Bobot Internal (%)</label>
                        <div class="input-group">
                            <input type="number" step="0.1" wire:model="internal_weight" class="form-control"
                                placeholder="0.0">
                            <span class="input-group-text">%</span>
                        </div>
                        @error('internal_weight') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-12 mt-4">
                        <div class="card bg-light-soft border-0 shadow-none"
                            style="background-color: rgba(30, 41, 59, 0.03);">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 class="mb-0 font-weight-bold">
                                        
                                        Kontrol Manual (Override)
                                    </h6>
                                    <div class="form-check form-switch px-0">
                                        <input class="form-check-input" type="checkbox" wire:model.live="is_manual">
                                        <span class="form-check-label small">Aktifkan Input Manual</span>
                                    </div>
                                </div>
                                <p class="text-muted small mb-3">Jika diaktifkan, sistem akan mengabaikan rumus otomatis
                                    dan menggunakan nilai yang Anda input di bawah ini.</p>

                                @if($is_manual)
                                    <div class="mb-2">
                                        <label class="form-label small font-weight-bold">Nilai Capaian Manual (%)</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" wire:model="manual_value"
                                                class="form-control form-control-sm" placeholder="Contoh: 85.5">
                                            <span class="input-group-text input-group-text-sm">%</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12 mt-3">
                        <label class="form-check form-switch px-0">
                            <input class="form-check-input" type="checkbox" wire:model="is_active">
                            <span class="form-check-label">Indikator Aktif</span>
                        </label>
                    </div>
                </div>
            </form>
        </x-slot:body>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary shadow-none" data-bs-dismiss="modal">Batal</button>
            <button type="submit" form="form-master-iku" class="btn btn-primary shadow-sm"
                wire:loading.class="btn-loading" wire:target="save">Simpan Perubahan</button>
        </x-slot:footer>
    </x-tabler.modal>

    <style>
        .font-weight-semi-bold {
            font-weight: 600;
        }

        .font-weight-medium {
            font-weight: 500;
        }

        .bg-light-soft {
            border-radius: 12px;
        }
    </style>
</div>