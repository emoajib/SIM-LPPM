<x-slot:title>Catatan Harian Penelitian</x-slot:title>
<x-slot:pageTitle>Catatan Harian Penelitian</x-slot:pageTitle>
<x-slot:pageSubtitle>
    Kelola buku harian (logbook) aktivitas penelitian Anda.
</x-slot:pageSubtitle>

<div>
    <x-tabler.alert />

    <!-- Role-based Tabs (only for regular dosen users) -->
    @if (auth()->user()->activeHasAnyRole(['dosen']))
        <div class="mb-3">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if ($roleFilter === 'ketua') active @endif"
                        wire:click="$set('roleFilter', 'ketua')" role="tab"
                        aria-selected="@if ($roleFilter === 'ketua') true @else false @endif">
                        <x-lucide-crown class="me-2 icon" />
                        Sebagai Ketua
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if ($roleFilter === 'anggota') active @endif"
                        wire:click="$set('roleFilter', 'anggota')" role="tab"
                        aria-selected="@if ($roleFilter === 'anggota') true @else false @endif">
                        <x-lucide-users class="me-2 icon" />
                        Sebagai Anggota
                    </button>
                </li>
            </ul>
        </div>
    @endif

    <!-- Search & Filter Section -->
    <div class="mb-3 row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input type="text" class="form-control"
                                placeholder="Cari berdasarkan judul atau peneliti..."
                                wire:model.live.debounce.300ms="search" />
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" wire:model.live="selectedYear">
                                <option value="">Semua Tahun</option>
                                @foreach ($this->availableYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-outline-secondary w-100" wire:click="resetFilters">
                                <x-lucide-rotate-ccw class="icon me-2" />
                                Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Proposals Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="card-table table table-vcenter">
                <thead>
                    <tr>
                        <th>Judul Penelitian</th>
                        <th>Peneliti</th>
                        <th>Status Logbook / LPJ</th>
                        <th>Realisasi Belanja</th>
                        <th>Catatan Terakhir</th>
                        <th class="w-1">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->proposals as $proposal)
                        @php
                            $totalBudget = (float) $proposal->budgetItems->sum('total_price');
                            $usedBudget = (float) $proposal->dailyNotes->sum('amount');
                            $notesCount = $proposal->dailyNotes->count();
                            $pctUsed = $totalBudget > 0 ? round(($usedBudget / $totalBudget) * 100, 1) : 0;
                            $hasScannedFile = $proposal->hasMedia('logbook_approval_file');
                            $isApproved = $proposal->logbook_approved_at !== null;
                            $isSigned = $proposal->logbook_signed_at !== null;
                            $latestNote = $proposal->dailyNotes->sortByDesc('activity_date')->first();
                        @endphp
                        <tr wire:key="proposal-{{ $proposal->id }}">
                            <td class="text-wrap">
                                <div class="text-reset fw-bold">{{ $proposal->title }}</div>
                                <small class="text-muted">{{ $proposal->researchScheme?->name }}</small>
                            </td>
                            <td>
                                <div>{{ $proposal->submitter?->name }}</div>
                                <small class="text-muted">{{ $proposal->submitter?->identity?->studyProgram?->name ?? '-' }}</small>
                            </td>
                            <td>
                                @if ($isApproved)
                                    <span class="badge bg-success-lt text-success fw-bold d-inline-flex align-items-center">
                                        <x-lucide-check-circle class="icon icon-sm me-1" /> Disetujui LPPM
                                    </span>
                                @elseif ($hasScannedFile)
                                    <span class="badge bg-warning-lt text-warning fw-bold d-inline-flex align-items-center" title="Dosen telah mengunggah scan pengesahan basah">
                                        <x-lucide-file-text class="icon icon-sm me-1" /> Menunggu Validasi (Berkas Scan)
                                    </span>
                                @elseif ($isSigned)
                                    <span class="badge bg-warning-lt text-warning fw-bold d-inline-flex align-items-center" title="Dosen telah menandatangani digital">
                                        <x-lucide-file-signature class="icon icon-sm me-1" /> Menunggu Validasi (TTD Digital)
                                    </span>
                                @elseif ($notesCount > 0)
                                    <span class="badge bg-info-lt text-info fw-bold d-inline-flex align-items-center">
                                        <x-lucide-clock class="icon icon-sm me-1" /> Sedang Berjalan ({{ $notesCount }} Catatan)
                                    </span>
                                @else
                                    <span class="badge bg-secondary-lt text-secondary d-inline-flex align-items-center">
                                        <x-lucide-help-circle class="icon icon-sm me-1" /> Belum Diisi
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div>
                                    <span class="fw-bold text-dark">Rp {{ number_format($usedBudget, 0, ',', '.') }}</span>
                                    <div class="small text-muted">
                                        Pagu: Rp {{ number_format($totalBudget, 0, ',', '.') }}
                                        <span class="badge {{ $pctUsed >= 70 ? 'bg-success-lt text-success' : 'bg-secondary-lt text-secondary' }} ms-1">{{ $pctUsed }}%</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($latestNote)
                                    <div class="small">
                                        <span class="text-secondary fw-semibold">{{ $latestNote->activity_date->format('d/m/Y') }}</span>
                                        <div class="text-truncate" style="max-width: 160px;" title="{{ $latestNote->activity_description }}">{{ $latestNote->activity_description }}</div>
                                    </div>
                                @else
                                    <span class="text-muted small">Belum ada catatan</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('research.daily-note.show', $proposal) }}"
                                    class="btn btn-primary btn-sm" wire:navigate.hover>
                                    <x-lucide-book class="icon me-1" />
                                    Buka Logbook
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center">
                                <div class="mb-3">
                                    <x-lucide-inbox class="text-secondary icon icon-lg" />
                                </div>
                                <p class="text-secondary">Tidak ada penelitian yang ditemukan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
