<x-slot:title>Perbaikan Usulan Pengabdian</x-slot:title>
<x-slot:pageTitle>
    @if (auth()->user()->hasRole('dosen'))
        Usulan Pengabdian Perlu Diperbaiki
    @else
        Usulan Pengabdian yang Sudah Direview
    @endif
</x-slot:pageTitle>
<x-slot:pageSubtitle>
    @if (auth()->user()->hasRole('dosen'))
        Daftar usulan pengabdian yang perlu diperbaiki sesuai catatan reviewer.
    @elseif(auth()->user()->hasRole('reviewer'))
        Daftar usulan pengabdian yang sudah Anda review.
    @else
        Daftar usulan pengabdian yang sudah memiliki hasil review dari reviewer.
    @endif
</x-slot:pageSubtitle>

<div>
    <x-tabler.alert />

    <div class="mb-3 row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Search Input -->
                        <div class="col-md-6">
                            <input type="text" class="form-control"
                                placeholder="Cari berdasarkan judul atau pelaksana..."
                                wire:model.live.debounce.300ms="search" />
                        </div>

                        <!-- Year Filter -->
                        <div class="col-md-3">
                            <select class="form-select" wire:model.live="selectedYear">
                                <option value="">Semua Tahun</option>
                                @foreach ($this->availableYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
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
            <table class="card-table table table-vcenter">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Pelaksana</th>
                        <th>Bidang Fokus</th>
                        <th>Status</th>
                        <th>Jumlah Review</th>
                        <th>Tanggal Dibuat</th>
                        <th class="w-1">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->proposals as $proposal)
                        <tr wire:key="proposal-{{ $proposal->id }}">
                            <td class="text-wrap">
                                <div class="text-reset fw-bold">{{ $proposal->title }}</div>
                                @if ($proposal->reviewers->isNotEmpty())
                                    <small class="text-secondary">
                                        {{ $proposal->reviewers->count() }} reviewer telah menyelesaikan review
                                    </small>
                                @endif
                            </td>
                            <td>
                                <div>{{ $proposal->submitter?->name }}</div>
                                <small class="text-secondary">{{ $proposal->submitter?->identity?->identity_id ?? '-' }}</small>
                            </td>
                            <td>
                                <x-tabler.badge variant="outline">
                                    {{ $proposal->focusArea?->name ?? '—' }}
                                </x-tabler.badge>
                            </td>
                            <td>
                                <x-tabler.badge :color="$proposal->status->color()" class="fw-normal">
                                    {{ $proposal->status->label() }}
                                </x-tabler.badge>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    @foreach ($proposal->reviewers as $reviewer)
                                        <div class="d-flex align-items-center gap-2">
                                            <x-tabler.badge color="success" class="fw-normal">
                                                <x-lucide-check class="icon icon-sm" />
                                                {{ $reviewer->user->name }}
                                            </x-tabler.badge>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                <small class="text-secondary">
                                    {{ $proposal->created_at?->format('d M Y') }}
                                </small>
                            </td>
                            <td>
                                <div class="flex-nowrap btn-list">
                                    <a href="{{ route('community-service.proposal-revision.show', $proposal) }}"
                                        class="btn btn-icon btn-ghost-primary" title="Lihat Detail Revisi"
                                        wire:navigate.hover>
                                        <x-lucide-eye class="icon" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center">
                                <div class="mb-3">
                                    <x-lucide-inbox class="text-secondary icon icon-lg" />
                                </div>
                                <p class="text-secondary">
                                    @if (auth()->user()->hasRole('dosen'))
                                        Tidak ada usulan pengabdian yang perlu diperbaiki.
                                    @else
                                        Tidak ada usulan pengabdian yang sudah direview.
                                    @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
