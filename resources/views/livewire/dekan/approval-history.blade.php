<x-slot:title>Riwayat Persetujuan</x-slot:title>
<x-slot:pageTitle>Riwayat Persetujuan</x-slot:pageTitle>
<x-slot:pageSubtitle>Daftar proposal yang telah Anda tinjau dan berikan keputusan.</x-slot:pageSubtitle>

<div>
    <!-- Filter Section (Vetted by AI - Manual Review Required by Senior Engineer/Manager) -->
    <div class="mb-3 row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-4">
                            <input type="text" class="form-control" placeholder="Cari judul proposal / pengusul..."
                                wire:model.live.debounce.300ms="search" />
                        </div>

                        <div class="col-md-2">
                            <select class="form-select" wire:model.live="typeFilter">
                                <option value="all">Semua Jenis</option>
                                <option value="research">Penelitian</option>
                                <option value="community_service">Pengabdian</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <select class="form-select" wire:model.live="decisionFilter">
                                <option value="all">Semua Keputusan</option>
                                <option value="approved">Disetujui</option>
                                <option value="rejected">Ditolak</option>
                                <option value="need_assignment">Perlu Perbaikan</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <select class="form-select" wire:model.live="yearFilter">
                                <option value="">Semua Tahun</option>
                                @foreach ($this->availableYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-secondary w-100" wire:click="resetFilters" title="Reset Filter">
                                <x-lucide-rotate-ccw class="icon" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="card-table table table-vcenter">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Proposal</th>
                        <th>Keputusan</th>
                        <th>Catatan</th>
                        <th class="w-1">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->history as $log)
                        <tr>
                            <td class="text-nowrap">{{ $log->at->format('d M Y H:i') }}</td>
                            <td>
                                <div class="fw-bold">{{ $log->proposal?->title }}</div>
                                <div class="small text-secondary">
                                    {{ $log->proposal?->submitter?->name }} &middot; {{ $log->proposal?->focusArea?->name ?? '—' }}
                                </div>
                            </td>
                            <td>
                                @php
                                    $color = match($log->status_after?->value) {
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'need_assignment' => 'warning',
                                        default => 'secondary'
                                    };
                                    $label = match($log->status_after?->value) {
                                        'approved' => 'Disetujui',
                                        'rejected' => 'Ditolak',
                                        'need_assignment' => 'Perlu Perbaikan',
                                        default => $log->status_after?->value ?? '—'
                                    };
                                @endphp
                                <span class="badge bg-{{ $color }}-lt">{{ $label }}</span>
                            </td>
                            <td>
                                <div class="small text-secondary text-wrap" style="max-width: 300px;">
                                    {{ $log->notes ?: '—' }}
                                </div>
                            </td>
                            <td>
                                @if($log->proposal)
                                    <a href="{{ $log->proposal->detailable_type === 'App\Models\Research' ? route('research.proposal.show', $log->proposal) : route('community-service.proposal.show', $log->proposal) }}" 
                                        class="btn btn-sm btn-ghost-primary" wire:navigate.hover>
                                        Lihat
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">Belum ada riwayat persetujuan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($this->history->hasPages())
            <div class="card-footer">
                {{ $this->history->links() }}
            </div>
        @endif
    </div>
</div>
