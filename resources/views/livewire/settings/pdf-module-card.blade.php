<div
    wire:key="pdf-module-card-{{ $moduleKey }}"
    class="card border-0 shadow-sm h-100"
    x-data="{ overridesActive: @js($this->hasOverrides()) }"
    @module-override-updated.window="if ($event.detail.moduleKey === '{{ $moduleKey }}') overridesActive = $event.detail.hasOverrides"
>
    <div class="card-body d-flex flex-column">
        {{-- Header: Name + Badges --}}
        <div class="d-flex align-items-start justify-content-between mb-2">
            <div class="d-flex align-items-center gap-2 min-w-0">
                <span
                    class="status-dot d-inline-block rounded-circle"
                    style="width: 10px; height: 10px; flex-shrink: 0; background: {{ $this->hasOverrides() ? '#2fb344' : '#d9d9d9' }};"
                    title="{{ $this->hasOverrides() ? 'Override aktif' : 'Mengikuti global' }}"
                ></span>
                <span class="fw-medium text-truncate" style="font-size: 0.9rem;">{{ $moduleName }}</span>
            </div>
            <span class="badge {{ $family === 'A' ? 'bg-blue-lt text-blue' : 'bg-green-lt text-green' }} ms-2 flex-shrink-0">
                {{ $familyLabel }}
            </span>
        </div>

        {{-- View Type --}}
        <div class="text-muted small mb-2">
            <code style="font-size: 10px;">{{ $viewType }}</code>
            @if($this->hasOverrides())
                <span class="badge bg-success-lt text-success ms-1" style="font-size: 9px;">Custom</span>
            @else
                <span class="badge bg-secondary-lt text-secondary ms-1" style="font-size: 9px;">Global</span>
            @endif
        </div>

        {{-- Current effective settings summary --}}
        <div class="small bg-light rounded p-2 mb-2" style="font-size: 11px; line-height: 1.6;">
            <div class="d-flex flex-wrap gap-x-3 gap-y-1">
                <span><strong>Font:</strong> {{ $this->fontFamily ?: $defaultFont }}</span>
                <span class="ms-3"><strong>Ukuran:</strong> {{ $this->fontSize ?: $defaultSize }}pt</span>
                <span class="ms-3"><strong>Kertas:</strong> {{ strtoupper($effectivePaper) }}</span>
                <span class="ms-3"><strong>Orientasi:</strong> {{ $effectiveOrientation ?: 'Default' }}</span>
            </div>
        </div>

        {{-- Inline editor toggle --}}
        <button
            type="button"
            class="btn btn-sm w-100 mb-2"
            style="border: 1px dashed {{ $this->hasOverrides() ? '#2fb344' : '#adb5bd' }}; color: {{ $this->hasOverrides() ? '#2fb344' : '#6c757d' }}; background: transparent;"
            wire:click="$toggle('showInlineEditor')"
        >
            <x-lucide-sliders class="icon icon-sm me-1" />
            {{ $showInlineEditor ? 'Sembunyikan Editor' : ($this->hasOverrides() ? 'Edit Override Khusus' : 'Atur Override Khusus') }}
        </button>

        {{-- Inline editor --}}
        @if($showInlineEditor)
            <div class="border-top pt-2 mt-1 small" style="font-size: 11px;">
                <div class="row g-1">
                    <div class="col-6">
                        <label class="form-label small mb-0">Font Family</label>
                        <select class="form-select form-select-sm" wire:model.live="fontFamily">
                            <option value="">-- Global --</option>
                            <option value="Times New Roman, Times, serif">Times New Roman</option>
                            <option value="Arial, Helvetica, sans-serif">Arial</option>
                            <option value="Georgia, serif">Georgia</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-0">Ukuran Font</label>
                        <select class="form-select form-select-sm" wire:model.live="fontSize">
                            <option value="">-- Global --</option>
                            <option value="7">7 pt</option>
                            <option value="8">8 pt</option>
                            <option value="9">9 pt</option>
                            <option value="10">10 pt</option>
                            <option value="11">11 pt</option>
                            <option value="12">12 pt</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-0">Ukuran Kertas</label>
                        <select class="form-select form-select-sm" wire:model.live="paperSize">
                            <option value="">-- Global --</option>
                            <option value="a4">A4</option>
                            <option value="folio">F4 / Folio</option>
                            <option value="letter">Letter</option>
                            <option value="legal">Legal</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-0">Orientasi</label>
                        <select class="form-select form-select-sm" wire:model.live="orientation">
                            <option value="">-- Default --</option>
                            <option value="portrait">Portrait</option>
                            <option value="landscape">Landscape</option>
                        </select>
                    </div>
                </div>

                {{-- Margin quick edit --}}
                <div class="mt-2">
                    <label class="form-label small mb-0">Margin (cm, kosong = global)</label>
                    <div class="row g-1">
                        <div class="col-3">
                            <input type="number" step="0.1" class="form-control form-control-sm" wire:model.live="marginTop" placeholder="Atas">
                        </div>
                        <div class="col-3">
                            <input type="number" step="0.1" class="form-control form-control-sm" wire:model.live="marginRight" placeholder="Kanan">
                        </div>
                        <div class="col-3">
                            <input type="number" step="0.1" class="form-control form-control-sm" wire:model.live="marginBottom" placeholder="Bawah">
                        </div>
                        <div class="col-3">
                            <input type="number" step="0.1" class="form-control form-control-sm" wire:model.live="marginLeft" placeholder="Kiri">
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Spacer --}}
        <div class="mt-auto"></div>

        {{-- Action buttons --}}
        <div class="d-flex gap-1 mt-2 pt-2 border-top">
            <button
                type="button"
                class="btn btn-sm btn-outline-primary flex-fill"
                wire:click="openModalEditor"
            >
                <x-lucide-edit-3 class="icon icon-sm me-1" /> Edit Detail
            </button>
            <button
                type="button"
                class="btn btn-sm btn-outline-danger"
                wire:click="resetOverrides"
                wire:confirm="Reset semua pengaturan kustom untuk '{{ $moduleName }}' ke nilai global?"
                @disabled(!$this->hasOverrides())
            >
                <x-lucide-rotate-ccw class="icon icon-sm" />
            </button>
        </div>
    </div>
</div>
