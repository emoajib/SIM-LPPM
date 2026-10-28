<x-slot:title>Riwayat Review</x-slot:title>
<x-slot:pageTitle>Riwayat Review</x-slot:pageTitle>
<x-slot:pageSubtitle>Daftar proposal yang telah Anda selesai review.</x-slot:pageSubtitle>

<div>
    <div class="alert alert-info alert-dismissible fade show border-0 shadow-sm collapse" id="reviewHistoryInfo" role="alert">
        <div class="d-flex">
            <div>
                
            </div>
            <div>
                <h4 class="alert-title">Tentang Siklus Review</h4>
                <div class="text-secondary">
                    Siklus review menunjukkan putaran evaluasi proposal. 
                    <strong>Siklus #1</strong> adalah review awal. Jika proposal memerlukan perbaikan, 
                    <strong>Siklus #2</strong> dan seterusnya menunjukkan proses review ulang setelah dosen pengusul melakukan revisi.
                </div>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-toggle="collapse" data-bs-target="#reviewHistoryInfo" aria-label="Close"></button>
    </div>

    <div class="mb-3">
        <button class="btn btn-ghost-info btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#reviewHistoryInfo" aria-expanded="false" aria-controls="reviewHistoryInfo">
            
            Informasi Siklus Review
        </button>
    </div>

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-primary text-white avatar">
                                
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">{{ $this->stats['total'] }}</div>
                            <div class="text-secondary small">Total Review</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-success text-white avatar">
                                
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">{{ $this->stats['approved'] }}</div>
                            <div class="text-secondary small">Disetujui</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-warning text-white avatar">
                                
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">{{ $this->stats['revision_needed'] }}</div>
                            <div class="text-secondary small">Butuh Revisi</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-danger text-white avatar">
                                
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">{{ $this->stats['rejected'] }}</div>
                            <div class="text-secondary small">Ditolak</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Cari Proposal / Pengusul</label>
                    <input type="text" class="form-control" placeholder="Cari judul proposal atau nama dosen..."
                        wire:model.live.debounce.300ms="search" />
                </div>
                <div class="col-md-2">
                    <label class="form-label">
                        Siklus Review
                        <span class="form-help ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Siklus review menunjukkan putaran review. #1 = review awal, #2+ = review ulang setelah revisi">?</span>
                    </label>
                    <select class="form-select" wire:model.live="roundFilter">
                        <option value="">Semua Siklus</option>
                        @foreach ($this->availableRounds as $round)
                            <option value="{{ $round }}">#{{ $round }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Filter Rekomendasi</label>
                    <select class="form-select" wire:model.live="recommendationFilter">
                        <option value="">Semua Rekomendasi</option>
                        <option value="approved">Disetujui</option>
                        <option value="revision_needed">Butuh Revisi</option>
                        <option value="rejected">Ditolak</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tahun Akademik</label>
                    <select class="form-select" wire:model.live="yearFilter">
                        <option value="">Semua Tahun</option>
                        @foreach ($this->availableYears as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-secondary w-100" wire:click="resetFilters">
                        
                        Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- History by Proposal (Accordion View) --}}
    @if ($this->historyByProposal->isNotEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h3 class="card-title">Riwayat Review per Proposal</h3>
            </div>
            <div class="card-body p-0">
                <div class="accordion" id="proposalHistoryAccordion">
                    @foreach ($this->historyByProposal as $proposalId => $logs)
                        @php
                            $firstLog = $logs->first();
                            $proposal = $firstLog?->proposal;
                        @endphp
                        @if ($proposal)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#proposal{{ $loop->index }}"
                                        aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                                        <div class="d-flex align-items-center justify-content-between w-100 me-3">
                                            <div>
                                                <div class="fw-bold">{{ $proposal->title }}</div>
                                                <div class="small text-muted">
                                                    {{ $proposal->submitter?->name }} &middot;
                                                    @if($proposal->detailable_type === 'App\Models\Research')
                                                        <span class="badge bg-blue-lt">Penelitian</span>
                                                    @else
                                                        <span class="badge bg-green-lt">Pengabdian</span>
                                                    @endif
                                                    &middot; {{ $proposal->focusArea?->name ?? '—' }}
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-secondary-lt">{{ $logs->count() }} review</span>
                                            </div>
                                        </div>
                                    </button>
                                </h2>
                                <div id="proposal{{ $loop->index }}" 
                                    class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                                    data-bs-parent="#proposalHistoryAccordion">
                                    <div class="accordion-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-vcenter mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Siklus</th>
                                                        <th>Rekomendasi</th>
                                                        <th>Tanggal Selesai</th>
                                                        <th>Catatan</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($logs as $log)
                                                        <tr>
                                                            <td>
                                                                <span class="badge bg-purple-lt" data-bs-toggle="tooltip" data-bs-placement="top" title="Siklus review ke-{{ $log->round }}. #1 = review awal, #2+ = review ulang setelah revisi">#{{ $log->round }}</span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-{{ $log->recommendation_color }}-lt">
                                                                    {{ $log->recommendation_label }}
                                                                </span>
                                                            </td>
                                                            <td>{{ $log->completed_at?->format('d M Y H:i') }}</td>
                                                            <td>
                                                                <div class="text-truncate" style="max-width: 300px;" title="{{ $log->review_notes }}">
                                                                    {{ Str::limit($log->review_notes, 80) }}
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="p-2 border-top">
                                            <a href="{{ $proposal->detailable_type === 'App\Models\Research' ? route('research.proposal.show', $proposal) : route('community-service.proposal.show', $proposal) }}" 
                                                class="btn btn-sm btn-ghost-primary" wire:navigate.hover>
                                                
                                                Lihat Proposal
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                
                <h3 class="text-muted">Belum Ada Riwayat Review</h3>
                <p class="text-secondary">Review yang telah Anda selesaikan akan ditampilkan di sini.</p>
            </div>
        </div>
    @endif
</div>
