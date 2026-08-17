{{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
<div class="card mb-3 border-primary-subtle shadow-sm">
    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center">
            <x-lucide-calendar class="icon me-2 text-primary" />
            <h3 class="card-title mb-0">
                Jadwal Pelaksanaan Kegiatan
                <span class="badge bg-blue-lt ms-2">Lampiran 3 PDF</span>
            </h3>
        </div>
        @if ($canEdit)
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <button type="button" wire:click="resetScheduleToDefault" wire:loading.attr="disabled"
                    class="btn btn-outline-secondary btn-sm"
                    wire:confirm="Kembalikan jadwal ke template default? Perubahan yang belum disimpan akan digantikan.">
                    <x-lucide-rotate-ccw class="icon icon-sm me-1" /> Template Default
                </button>
                <button type="button" wire:click="addScheduleItem" wire:loading.attr="disabled"
                    class="btn btn-outline-primary btn-sm">
                    <x-lucide-plus class="icon icon-sm me-1" /> Tambah Baris
                </button>
                <button type="button" wire:click="saveScheduleItems" wire:loading.attr="disabled"
                    class="btn btn-primary btn-sm">
                    <x-lucide-save class="icon icon-sm me-1" />
                    <span wire:loading.remove wire:target="saveScheduleItems">Simpan Jadwal</span>
                    <span wire:loading wire:target="saveScheduleItems">Menyimpan...</span>
                </button>
            </div>
        @endif
    </div>
    <div class="card-body p-0">
        @if ($errors->has('scheduleItems*'))
            <div class="alert alert-danger m-3 mb-0">
                <ul class="mb-0 small">
                    @foreach ($errors->get('scheduleItems*') as $messages)
                        @foreach ($messages as $msg)
                            <li>{{ $msg }}</li>
                        @endforeach
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-vcenter table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th width="45%">Nama Kegiatan / Tahapan</th>
                        <th width="15%" class="text-center">Tahun Ke-</th>
                        <th width="15%" class="text-center">Bulan Mulai</th>
                        <th width="15%" class="text-center">Bulan Selesai</th>
                        @if ($canEdit)
                            <th width="5%" class="text-center">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($scheduleItems as $sIdx => $sItem)
                        <tr wire:key="sched-row-{{ $sIdx }}">
                            <td class="text-center text-muted fw-bold">{{ $sIdx + 1 }}</td>
                            <td>
                                @if ($canEdit)
                                    <input type="text"
                                        wire:model="scheduleItems.{{ $sIdx }}.activity_name"
                                        class="form-control form-control-sm @error('scheduleItems.'.$sIdx.'.activity_name') is-invalid @enderror"
                                        placeholder="Contoh: Studi Literatur, Pengumpulan Data, dll.">
                                @else
                                    <span class="fw-semibold">{{ $sItem['activity_name'] ?? '-' }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($canEdit)
                                    <select wire:model="scheduleItems.{{ $sIdx }}.year"
                                        class="form-select form-select-sm text-center">
                                        @for ($y = 1; $y <= max((int) ($proposal->duration_in_years ?? 1), 1); $y++)
                                            <option value="{{ $y }}">Tahun {{ $y }}</option>
                                        @endfor
                                    </select>
                                @else
                                    <span class="badge bg-secondary-lt">Tahun {{ $sItem['year'] ?? 1 }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($canEdit)
                                    <select wire:model="scheduleItems.{{ $sIdx }}.start_month"
                                        class="form-select form-select-sm text-center">
                                        @for ($m = 1; $m <= 12; $m++)
                                            <option value="{{ $m }}">Bulan {{ $m }}</option>
                                        @endfor
                                    </select>
                                @else
                                    <span>Bulan {{ $sItem['start_month'] ?? 1 }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($canEdit)
                                    <select wire:model="scheduleItems.{{ $sIdx }}.end_month"
                                        class="form-select form-select-sm text-center">
                                        @for ($m = 1; $m <= 12; $m++)
                                            <option value="{{ $m }}">Bulan {{ $m }}</option>
                                        @endfor
                                    </select>
                                @else
                                    <span>Bulan {{ $sItem['end_month'] ?? 12 }}</span>
                                @endif
                            </td>
                            @if ($canEdit)
                                <td class="text-center">
                                    <button type="button" wire:click="removeScheduleItem({{ $sIdx }})"
                                        class="btn btn-ghost-danger btn-icon btn-sm" title="Hapus Baris">
                                        <x-lucide-trash-2 class="icon icon-sm" />
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canEdit ? 6 : 5 }}" class="text-center text-muted py-4">
                                <x-lucide-calendar-x class="icon mb-2 text-secondary" style="width: 2rem; height: 2rem;" />
                                <div>Belum ada jadwal kegiatan.</div>
                                @if ($canEdit)
                                    <button type="button" wire:click="resetScheduleToDefault"
                                        class="btn btn-outline-primary btn-sm mt-2">
                                        <x-lucide-wand-2 class="icon icon-sm me-1" /> Muat Template Jadwal
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($canEdit && !empty($scheduleItems))
            <div class="p-2 bg-light border-top d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <x-lucide-info class="icon icon-inline me-1" /> Klik tombol <strong>Simpan Jadwal</strong> untuk menerapkan perubahan ke dokumen PDF.
                </small>
                <button type="button" wire:click="saveScheduleItems" wire:loading.attr="disabled"
                    class="btn btn-primary btn-sm">
                    <x-lucide-save class="icon icon-sm me-1" /> Simpan Jadwal
                </button>
            </div>
        @endif
    </div>
</div>
