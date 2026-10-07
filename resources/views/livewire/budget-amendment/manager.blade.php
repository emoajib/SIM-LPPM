<div>
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title"><x-lucide-coins class="icon me-2" />Amandemen RAB</h3>
        <div class="card-actions">
            @if ($canManage && ! $pendingExists && ! $proposal->logbook_approved_at)
                <button type="button" wire:click="openRequestModal" class="btn btn-sm btn-outline-primary">
                    <x-lucide-plus class="icon icon-sm me-1" /> Ajukan Amandemen
                </button>
            @endif
        </div>
    </div>
    <div class="card-body p-0">
        @if ($amendments->isEmpty())
            <p class="text-muted small px-3 py-3 mb-0">Belum ada amandemen. RAB aktif adalah versi awal yang disetujui. Bila kebutuhan lapangan berubah, ajukan amandemen — versi aktif berganti hanya setelah disetujui LPPM.</p>
        @else
            <div class="table-responsive">
                <table class="table table-vcenter table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Versi</th>
                            <th>Status</th>
                            <th>Alasan</th>
                            <th>Diajukan</th>
                            <th>Keputusan</th>
                            @if ($canManage || $canDecide)
                                <th class="w-1">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($amendments as $amendment)
                            <tr wire:key="amendment-{{ $amendment->id }}">
                                <td class="fw-bold">v{{ $amendment->version }}</td>
                                <td><x-tabler.badge color="{{ $amendment->status->color() }}">{{ $amendment->status->label() }}</x-tabler.badge></td>
                                <td><small>{{ \Illuminate\Support\Str::limit($amendment->reason, 80) }}</small></td>
                                <td><small class="text-muted">{{ $amendment->requester?->name }}<br>{{ $amendment->created_at?->format('d/m/Y H:i') }}</small></td>
                                <td><small class="text-muted">{{ $amendment->decision_notes ?? '—' }}</small></td>
                                @if ($canManage || $canDecide)
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            @if ($canManage && $amendment->status->value === 'pending')
                                                <button type="button" wire:click="cancelAmendment('{{ $amendment->id }}')"
                                                    wire:confirm="Batalkan pengajuan amandemen ini?"
                                                    class="btn btn-sm btn-outline-danger" title="Batalkan pengajuan">Batal</button>
                                            @endif
                                            @if ($canDecide && $amendment->status->value === 'pending')
                                                <button type="button" wire:click="approveAmendment('{{ $amendment->id }}')"
                                                    wire:confirm="Setujui amandemen ini? RAB aktif akan diperbarui."
                                                    class="btn btn-sm btn-success">Setujui</button>
                                                <button type="button" wire:click="rejectAmendment('{{ $amendment->id }}')"
                                                    class="btn btn-sm btn-danger">Tolak</button>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($canDecide && $pendingExists)
                <div class="px-3 py-2 border-top">
                    <label class="form-label small">Catatan keputusan (wajib bila menolak)</label>
                    <input type="text" wire:model="decisionNotes" class="form-control form-control-sm" placeholder="Catatan untuk dosen...">
                    @error('decision_notes')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
            @endif
        @endif
    </div>
</div>

@teleport('body')
<x-tabler.modal id="budget-amendment-modal" title="Ajukan Amandemen RAB" wire:ignore.self size="xl">
    <x-slot:body>
        <div class="alert alert-info small">Ubah salinan RAB di bawah. Versi aktif TIDAK berubah sampai amandemen disetujui LPPM. Total usulan tetap dibatasi pagu skema.</div>
        @error('amendment')<div class="alert alert-danger small">{{ $message }}</div>@enderror
        @error('items')<div class="alert alert-danger small">{{ $message }}</div>@enderror
        @foreach ($items as $index => $row)
            <div class="row g-2 mb-2 align-items-end" wire:key="amend-row-{{ $index }}">
                <div class="col-md-3">
                    <label class="form-label small">Kelompok</label>
                    <select wire:model="items.{{ $index }}.budget_group_id" class="form-select form-select-sm">
                        <option value="">-- Pilih --</option>
                        @foreach ($budgetGroups as $bg)
                            <option value="{{ $bg->id }}">{{ $bg->name }}</option>
                        @endforeach
                    </select>
                    @error("items.{$index}")<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Deskripsi</label>
                    <input type="text" wire:model="items.{{ $index }}.item_description" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Volume</label>
                    <input type="number" min="0" step="0.01" wire:model="items.{{ $index }}.volume" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Harga Satuan</label>
                    <input type="number" min="0" step="0.01" wire:model="items.{{ $index }}.unit_price" class="form-control form-control-sm">
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" wire:click="removeItemRow({{ $index }})" class="btn btn-sm btn-outline-danger" title="Hapus baris"><x-lucide-trash-2 class="icon icon-sm" /></button>
                </div>
            </div>
        @endforeach
        <button type="button" wire:click="addItemRow" class="btn btn-sm btn-outline-primary"><x-lucide-plus class="icon icon-sm me-1" /> Tambah Baris</button>
        <div class="mt-3">
            <label class="form-label required">Alasan Amandemen (min. 10 karakter)</label>
            <textarea wire:model="reason" rows="3" class="form-control @error('reason') is-invalid @enderror"></textarea>
            @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </x-slot:body>
    <x-slot:footer>
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
        <button type="button" wire:click="submitRequest" class="btn btn-primary" wire:loading.attr="disabled">Ajukan</button>
    </x-slot:footer>
</x-tabler.modal>
@endteleport
</div>
