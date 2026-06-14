<x-slot:title>Laporan Kemajuan Penelitian</x-slot:title>
<x-slot:pageTitle>Laporan Kemajuan Penelitian</x-slot:pageTitle>
<x-slot:pageSubtitle>
    Buat dan kelola laporan kemajuan untuk penelitian yang telah disetujui.
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
                        <x-lucide-crown class="icon me-2" />
                        Sebagai Ketua
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if ($roleFilter === 'anggota') active @endif"
                        wire:click="$set('roleFilter', 'anggota')" role="tab"
                        aria-selected="@if ($roleFilter === 'anggota') true @else false @endif">
                        <x-lucide-users class="icon me-2" />
                        Sebagai Anggota
                    </button>
                </li>
            </ul>
        </div>
    @endif

    <!-- Search & Filter Section -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Search Input -->
                        <div class="col-md-6">
                            <input type="text" class="form-control"
                                placeholder="Cari berdasarkan judul atau peneliti..."
                                wire:model.live.debounce.300ms="search" />
                        </div>

                        <!-- Year Filter -->
                        <div class="col-md-3">
                            <select class="form-select" wire:model.live="selectedYear">
                                <option value="">Semua Tahun</option>
                                @foreach ($this->availableYears as $year)
                                    <option value="{{ $year }}" wire:key="year-{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Reset Button -->
                        <div class="col-md-3">
                            <button type="button" class="btn-outline-secondary w-100 btn" wire:click="resetFilters">
                                <x-lucide-rotate-ccw class="icon" />
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
            <table class="card-table table-vcenter table">
                <thead>
                    <tr>
                        <th>Judul Penelitian</th>
                        <th>Peneliti</th>
                        {{-- <th>Skema</th> --}}
                        <th>Status</th>
                        <th>Laporan Terakhir</th>
                        <th class="w-1">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->proposals as $proposal)
                        <tr wire:key="proposal-{{ $proposal->id }}">
                            <td class="text-wrap">
                                <div class="text-reset fw-bold">{{ $proposal->title }}</div>
                            </td>
                            <td>
                                <div>{{ $proposal->submitter?->name }}</div>
                                <small class="text-secondary">{{ $proposal->submitter?->identity?->identity_id ?? '-' }}</small>
                            </td>
                            {{-- <td>
                                <x-tabler.badge variant="outline">
                                    {{ $proposal->researchScheme?->name ?? '—' }}
                                </x-tabler.badge>
                            </td> --}}
                            <td>
                                <x-tabler.badge :color="$proposal->status->color()" class="fw-normal">
                                    {{ $proposal->status->label() }}
                                </x-tabler.badge>
                            </td>
                            <td>
                                @php
                                    $latestReport = $proposal->progressReports->first();
                                @endphp
                                @if ($latestReport)
                                    <small class="text-secondary">
                                        {{ $latestReport->reporting_year }} -
                                        {{ ucfirst(str_replace('_', ' ', $latestReport->reporting_period)) }}
                                        <br>
                                        <x-tabler.badge :color="$latestReport->status === 'approved' ? 'success' : ($latestReport->status === 'submitted' ? 'info' : 'secondary')" class="mt-1">
                                            {{ ucfirst((string) $latestReport->status) }}
                                        </x-tabler.badge>
                                    </small>
                                @else
                                    <small class="text-muted">Belum ada laporan</small>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('research.progress-report.show', $proposal) }}"
                                    class="btn btn-primary btn-sm" wire:navigate.hover>
                                    <x-lucide-file-edit class="icon" />
                                    {{ $proposal->progressReports->isEmpty() ? 'Buat Laporan' : 'Lihat Laporan' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center">
                                <div class="mb-3">
                                    <x-lucide-inbox class="text-secondary icon icon-lg" />
                                </div>
                                <p class="text-secondary">
                                    Tidak ada penelitian yang perlu dilaporkan.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
