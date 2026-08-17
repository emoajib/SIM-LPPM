<!-- Section: RAB (Rencana Anggaran Biaya) -->
@php
    $startYear = (int) ($form->start_year ?: date('Y'));
    $duration = (int) ($form->duration_in_years ?: 1);
    $currentYear = (int) ($form->start_year ?: date('Y'));
    $currentSemester = $form->semester ?: 'ganjil';
    $schemeId = $form->research_scheme_id ?: $form->community_service_scheme_id;
    $budgetCap = \App\Models\BudgetCap::getCapForPeriod($currentYear, $currentSemester, 'community_service', $schemeId ? (int) $schemeId : null);

    // Calculate totals per group for percentage visualization
    $totalBudget = collect($form->budget_items)->sum(fn($item) => (float) ($item['total'] ?? 0));
    $groupTotals = collect($form->budget_items)->groupBy('budget_group_id')->map(fn($items) => $items->sum(fn($item) => (float) ($item['total'] ?? 0)));

    // Calculate totals per year
    $yearTotals = collect($form->budget_items)->groupBy('year')->map(fn($items) => $items->sum(fn($item) => (float) ($item['total'] ?? 0)))->toArray();
@endphp

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center">
                <x-lucide-calculator class="icon me-3" />
                <h3 class="card-title mb-0">Rencana Anggaran Biaya (RAB)</h3>
            </div>
            <button type="button" wire:click="addBudgetItem" class="btn btn-primary btn-sm">
                <x-lucide-plus class="icon" />
                Tambah Item
            </button>
        </div>

        <!-- Budget Group Information Alert -->
        <div class="alert alert-info mb-3" role="alert">
            <div class="d-flex">
                <div>
                    <x-lucide-info class="icon alert-icon" />
                </div>
                <div class="w-100">
                    <h4 class="alert-title">Batasan Persentase Kelompok Anggaran</h4>
                    <div class="text-muted">
                        Pastikan alokasi anggaran sesuai dengan batasan berikut:
                    </div>
                    <ul class="mb-0 mt-2">
                        @foreach ($this->budgetGroups->whereNotNull('percentage') as $group)
                            @php
                                $groupTotal = $groupTotals[$group->id] ?? 0;
                                $percentageUsed = $budgetCap > 0 ? ($groupTotal / $budgetCap) * 100 : 0;
                                $allowedPercentage = (float) $group->percentage;
                                $isOver = $percentageUsed > $allowedPercentage;
                                $isMinimum = $group->code === 'TEKNOLOGI';
                                $isBelowMinimum = $isMinimum && $percentageUsed < $allowedPercentage;
                            @endphp
                            <li class="mb-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $group->name }}</strong>:
                                        @if ($isMinimum)
                                            <x-tabler.badge color="success">
                                                Minimal {{ number_format($allowedPercentage, 0) }}%
                                            </x-tabler.badge>
                                        @else
                                            <x-tabler.badge color="warning">
                                                Maksimal {{ number_format($allowedPercentage, 0) }}%
                                            </x-tabler.badge>
                                        @endif
                                        <small class="text-muted"> - {{ $group->description }}</small>
                                    </div>
                                     @if ($budgetCap > 0)
                                         <x-tabler.badge :color="$isOver || $isBelowMinimum ? 'danger' : 'success'">
                                             {{ number_format($percentageUsed, 1) }}%
                                             (Rp {{ number_format($groupTotal, 0, ',', '.') }})
                                         </x-tabler.badge>
                                         @if ($totalBudget > 0)
                                             <small class="text-muted ms-2">{{ number_format(($groupTotal / $totalBudget) * 100, 1) }}% dari total RAB</small>
                                         @endif
                                     @elseif ($totalBudget > 0)
                                         <x-tabler.badge color="info">
                                             {{ number_format(($groupTotal / $totalBudget) * 100, 1) }}%
                                             (Rp {{ number_format($groupTotal, 0, ',', '.') }})
                                         </x-tabler.badge>
                                     @endif
                                </div>
                                @if ($budgetCap > 0)
                                     <div class="progress mt-1" style="height: 6px;">
                                         <div class="progress-bar {{ $isOver || $isBelowMinimum ? 'bg-danger' : 'bg-success' }}"
                                             role="progressbar"
                                             style="width: {{ min($percentageUsed, 100) }}%">
                                         </div>
                                     </div>
                                 @elseif ($totalBudget > 0)
                                     <div class="progress mt-1" style="height: 6px;">
                                         <div class="progress-bar bg-info"
                                             role="progressbar"
                                             style="width: {{ min(($groupTotal / $totalBudget) * 100, 100) }}%">
                                         </div>
                                     </div>
                                 @endif
                            </li>
                        @endforeach
                    </ul>
                    @if ($budgetCap)
                        <div class="border-top mt-2 pt-2">
                            <strong>Batas Maksimal Anggaran Pengabdian {{ $currentYear }}:</strong>
                            <x-tabler.badge color="danger">
                                Rp {{ number_format($budgetCap, 0, ',', '.') }}
                            </x-tabler.badge>
                        </div>
                    @endif
                    @if (!$budgetCap && $totalBudget > 0)
                        <div class="alert alert-warning mb-0 mt-3" role="alert">
                            <div class="d-flex">
                                <div>
                                    <x-lucide-alert-triangle class="icon alert-icon" />
                                </div>
                                <div>
                                    @php $semesterLabel = ucfirst($currentSemester); @endphp
                                    Batas anggaran tahun {{ $currentYear }} (Semester {{ $semesterLabel }}) belum ditetapkan Admin LPPM. Persentase ditampilkan terhadap total RAB sementara.
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Year Summary Cards (for multi-year proposals) -->
        @if ($duration > 1 && !empty($form->budget_items))
            <div class="row g-3 mb-3">
                @for ($y = 1; $y <= $duration; $y++)
                    @php
                        $yearTotal = $yearTotals[$y] ?? 0;
                    @endphp
                    <div class="col-md-{{ 12 / min($duration, 4) }}">
                        <div class="card card-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <span class="bg-primary text-white stamp me-3">{{ $y }}</span>
                                    <div>
                                        <div class="text-muted small">Tahun {{ $y }} ({{ $startYear + $y - 1 }})</div>
                                        <div class="h4 mb-0">Rp {{ number_format($yearTotal, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        @endif

        <!-- Real-time Validation Feedback -->
        @if (!empty($budgetValidationErrors))
            <div class="alert alert-danger" role="alert">
                <div class="d-flex">
                    <div>
                        <x-lucide-alert-circle class="icon alert-icon" />
                    </div>
                    <div>
                        <h4 class="alert-title">Peringatan: Alokasi Anggaran Melebihi Batas</h4>
                        <ul class="mb-0">
                            @foreach ($budgetValidationErrors as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        @error('form.budget_items')
            <div class="alert alert-danger mb-3">
                <div class="d-flex">
                    <x-lucide-alert-circle class="icon me-2" />
                    <div>{{ $message }}</div>
                </div>
            </div>
        @enderror

        @if (empty($form->budget_items))
            <div class="alert alert-info">
                <x-lucide-info class="icon me-2" />
                Belum ada item anggaran. Klik tombol "Tambah Item" untuk menambahkan.
            </div>
        @else
            <div class="table-responsive">
                <table class="table-bordered table">
                    <thead>
                        <tr>
                            <th width="8%">Tahun Ke-</th>
                            <th width="13%">Kelompok RAB <span class="text-danger">*</span></th>
                            <th width="13%">Komponen <span class="text-danger">*</span></th>
                            <th width="18%">Item <span class="text-danger">*</span></th>
                            <th width="8%">Satuan</th>
                            <th width="8%">Volume <span class="text-danger">*</span></th>
                            <th width="15%">Harga Satuan <span class="text-danger">*</span></th>
                            <th width="13%">Total</th>
                            <th width="5%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($form->budget_items as $index => $item)
                            @php
                                $selectedGroupValue =
                                    isset($item['budget_group_id']) && $item['budget_group_id'] !== ''
                                        ? $item['budget_group_id']
                                        : null;
                                $selectedComponentValue =
                                    isset($item['budget_component_id']) && $item['budget_component_id'] !== ''
                                        ? $item['budget_component_id']
                                        : null;
                            @endphp
                            <tr wire:key="budget-{{ $index }}" x-data="{
                                selectedGroup: @js($selectedGroupValue),
                                selectedComponent: @js($selectedComponentValue),
                                components: @js($this->budgetComponents->groupBy('budget_group_id')->map(fn($items) => $items->map(fn($i) => ['id' => $i->id, 'name' => $i->name, 'unit' => $i->unit])->values())->toArray()),
                                get filteredComponents() {
                                    if (!this.selectedGroup) return [];
                                    return this.components[this.selectedGroup] || [];
                                },
                                autoFillUnit() {
                                    if (this.selectedComponent) {
                                        const comp = this.filteredComponents.find(c => c.id == this.selectedComponent);
                                        if (comp) {
                                            @this.set('form.budget_items.{{ $index }}.unit', comp.unit);
                                        }
                                    }
                                }
                            }">
                                <td>
                                    <select wire:model="form.budget_items.{{ $index }}.year"
                                        class="form-select-sm form-select">
                                        @for ($y = 1; $y <= $duration; $y++)
                                            <option value="{{ $y }}">{{ $y }} ({{ $startYear + $y - 1 }})</option>
                                        @endfor
                                    </select>
                                </td>
                                <td>
                                    <select wire:model.live="form.budget_items.{{ $index }}.budget_group_id"
                                        x-model="selectedGroup" class="form-select-sm form-select">
                                        <option value="">-- Pilih --</option>
                                        @foreach ($this->budgetGroups as $group)
                                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select wire:model="form.budget_items.{{ $index }}.budget_component_id"
                                        x-model="selectedComponent" x-on:change="autoFillUnit()"
                                        class="form-select-sm form-select" :disabled="!selectedGroup">
                                        <option value="">-- Pilih --</option>
                                        <template x-for="comp in filteredComponents" :key="comp.id">
                                            <option :value="comp.id" x-text="comp.name"
                                                :selected="comp.id == selectedComponent"></option>
                                        </template>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" wire:model="form.budget_items.{{ $index }}.item"
                                        class="form-control form-control-sm" placeholder="Item">
                                </td>
                                <td>
                                    <input type="text" wire:model="form.budget_items.{{ $index }}.unit"
                                        class="bg-body-tertiary form-control form-control-sm disabled" placeholder="Satuan"
                                        readonly disabled>
                                </td>
                                <td>
                                    <input type="number"
                                        wire:model.live="form.budget_items.{{ $index }}.volume"
                                        wire:change="calculateTotal({{ $index }})"
                                        class="form-control form-control-sm" placeholder="0" min="0"
                                        step="0.01">
                                </td>
                                <td x-data="moneyInput({{ $index }})">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text"
                                            x-model="display"
                                            x-ref="input"
                                            @focus="handleFocus"
                                            @input="handleInput"
                                            class="form-control" placeholder="0">
                                    </div>
                                </td>
                                <td x-data="{ total: @entangle("form.budget_items.{$index}.total") }">
                                    <input type="text" 
                                        class="form-control form-control-sm bg-body-tertiary text-end" 
                                        :value="new Intl.NumberFormat('id-ID').format(total || 0)" 
                                        readonly>
                                </td>
                                <td>
                                    <button type="button" wire:click="removeBudgetItem({{ $index }})"
                                        class="btn btn-sm btn-danger">
                                        <x-lucide-trash-2 class="icon" />
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="7" class="text-end"><strong>Total Anggaran:</strong></td>
                            <td colspan="2">
                                <strong>Rp
                                    {{ number_format($totalBudget, 0, ',', '.') }}</strong>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>

<!-- Section: Jadwal Pelaksanaan Pengabdian (Lampiran 3 PDF) -->
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <div class="d-flex align-items-center">
                <x-lucide-calendar class="icon me-3" />
                <h3 class="card-title mb-0">Jadwal Pelaksanaan Pengabdian
                    <small class="text-muted fw-normal ms-2" style="font-size:0.85rem;">(Lampiran 3 PDF)</small>
                </h3>
            </div>
            <div class="d-flex gap-2">
                <button type="button" wire:click="initDefaultSchedule" class="btn btn-outline-secondary btn-sm"
                    wire:confirm="Muat ulang template jadwal default?">
                    <x-lucide-rotate-ccw class="icon icon-sm me-1" /> Template Default
                </button>
                <button type="button" wire:click="addScheduleItem" class="btn btn-primary btn-sm">
                    <x-lucide-plus class="icon icon-sm me-1" /> Tambah Baris
                </button>
            </div>
        </div>

        @if (empty($form->schedule_items))
            <div class="text-center text-muted py-4 border rounded-2">
                <x-lucide-calendar-x class="icon mb-2 text-secondary" style="width: 2rem; height: 2rem;" />
                <p class="mb-2">Belum ada jadwal kegiatan.</p>
                <button type="button" wire:click="initDefaultSchedule" class="btn btn-sm btn-outline-primary">
                    <x-lucide-wand-2 class="icon icon-sm me-1" /> Generate Template Otomatis
                </button>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="45%">Nama Kegiatan / Tahapan</th>
                            <th width="15%" class="text-center">Tahun Ke-</th>
                            <th width="15%" class="text-center">Bulan Mulai</th>
                            <th width="15%" class="text-center">Bulan Selesai</th>
                            <th width="5%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($form->schedule_items as $sIdx => $sItem)
                            <tr wire:key="proposal-schedule-{{ $sIdx }}">
                                <td class="text-center text-muted">{{ $sIdx + 1 }}</td>
                                <td>
                                    <input type="text"
                                        wire:model="form.schedule_items.{{ $sIdx }}.activity_name"
                                        class="form-control form-control-sm"
                                        placeholder="Nama kegiatan/tahapan">
                                </td>
                                <td>
                                    <select wire:model="form.schedule_items.{{ $sIdx }}.year"
                                        class="form-select form-select-sm text-center">
                                        @for ($y = 1; $y <= max((int) ($form->duration_in_years ?: 1), 1); $y++)
                                            <option value="{{ $y }}">Tahun {{ $y }}</option>
                                        @endfor
                                    </select>
                                </td>
                                <td>
                                    <select wire:model="form.schedule_items.{{ $sIdx }}.start_month"
                                        class="form-select form-select-sm text-center">
                                        @for ($m = 1; $m <= 12; $m++)
                                            <option value="{{ $m }}">Bulan {{ $m }}</option>
                                        @endfor
                                    </select>
                                </td>
                                <td>
                                    <select wire:model="form.schedule_items.{{ $sIdx }}.end_month"
                                        class="form-select form-select-sm text-center">
                                        @for ($m = 1; $m <= 12; $m++)
                                            <option value="{{ $m }}">Bulan {{ $m }}</option>
                                        @endfor
                                    </select>
                                </td>
                                <td class="text-center">
                                    <button type="button" wire:click="removeScheduleItem({{ $sIdx }})"
                                        class="btn btn-ghost-danger btn-sm btn-icon" title="Hapus Baris">
                                        <x-lucide-trash-2 class="icon icon-sm" />
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

