<x-slot:title>Review Penelitian</x-slot:title>
<x-slot:pageTitle>Review Penelitian</x-slot:pageTitle>
<x-slot:pageSubtitle>Kelola tugas review proposal penelitian yang ditugaskan kepada Anda.</x-slot:pageSubtitle>

<div>
    <x-tabler.alert />

    <div class="alert alert-info alert-dismissible fade show border-0 shadow-sm collapse" id="researchReviewInfo" role="alert">
        <div class="d-flex">
            <div>
                
            </div>
            <div>
                <h4 class="alert-title">Daftar Review Penelitian</h4>
                <div class="text-secondary">
                    Halaman ini menampilkan daftar proposal penelitian yang ditugaskan kepada Anda untuk direview. 
                    Klik tombol <strong>Review</strong> pada proposal yang ingin Anda evaluasi.
                </div>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-toggle="collapse" data-bs-target="#researchReviewInfo" aria-label="Close"></button>
    </div>

    <div class="mb-3">
        <button class="btn btn-ghost-info btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#researchReviewInfo" aria-expanded="false" aria-controls="researchReviewInfo">
            
            Informasi Daftar Review
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="row row-deck row-cards mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Total Tugas</div>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <div class="h1 mb-0 me-2">{{ $this->statusStats['all'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm border-start-3 border-warning">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Menunggu Review</div>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <div class="h1 mb-0 me-2 text-warning">{{ $this->statusStats['pending'] }}</div>
                        <div class="me-auto">
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm border-start-3 border-purple">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Review Ulang</div>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <div class="h1 mb-0 me-2 text-purple">{{ $this->statusStats['re_review'] }}</div>
                        <div class="me-auto">
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm border-start-3 border-success">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Selesai</div>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <div class="h1 mb-0 me-2 text-success">{{ $this->statusStats['completed'] }}</div>
                        <div class="me-auto">
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-3 card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="search">
                        
                        Cari Proposal
                    </label>
                    <input type="text" id="search" class="form-control"
                        placeholder="Cari berdasarkan judul atau nama author..." wire:model.live="search">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="status">
                        
                        Status Review
                    </label>
                    <select id="status" class="form-select" wire:model.live="statusFilter">
                        <option value="all">Semua Status</option>
                        <option value="pending">Menunggu Review</option>
                        <option value="in_progress">Sedang Direview</option>
                        <option value="re_review_requested">Perlu Review Ulang</option>
                        <option value="completed">Review Selesai</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="year">
                        
                        Tahun
                    </label>
                    <select id="year" class="form-select" wire:model.live="selectedYear">
                        <option value="">Semua Tahun</option>
                        @foreach ($this->availableYears as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @if (!empty($search) || !empty($selectedYear) || $statusFilter !== 'all')
                <div class="mt-3">
                    <button type="button" class="btn btn-ghost-secondary btn-sm" wire:click="resetFilters">
                        
                        Reset Filter
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Proposals Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="card-table table table-vcenter table-hover">
                <thead>
                    <tr>
                        <th>Proposal</th>
                        <th>Status Review</th>
                        <th>Deadline</th>
                        <th class="w-1">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->proposals as $proposal)
                        @php
                            $review = $proposal->reviewers->firstWhere('user_id', auth()->id());
                        @endphp
                        <tr wire:key="proposal-{{ $proposal->id }}">
                            <td class="py-3">
                                <div class="d-flex align-items-start">
                                    <div class="flex-fill">
                                        <div class="font-weight-bold mb-1 text-wrap" style="max-width: 500px;">
                                            {{ $proposal->title }}
                                            @if($review && $review->round > 1)
                                                <span class="badge bg-purple-lt ms-1">Putaran {{ $review->round }}</span>
                                            @endif
                                        </div>
                                        <div class="text-muted small d-flex align-items-center gap-3">
                                            <span>{{ $proposal->submitter?->name }}</span>
                                            <span>{{ $proposal->focusArea?->name ?? '—' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($review)
                                    <div class="mb-1">
                                        <x-tabler.badge :color="$review->status->color()">
                                            <x-dynamic-component :component="'lucide-' . $review->status->icon()" class="icon icon-inline me-1" />
                                            {{ $review->status->label() }}
                                        </x-tabler.badge>
                                    </div>
                                    @if ($review->isCompleted())
                                        <div class="small">
                                            @if ($review->recommendation === 'approved')
                                                <span class="text-success fw-medium">✓ Disetujui</span>
                                            @elseif ($review->recommendation === 'rejected')
                                                <span class="text-danger fw-medium">✗ Ditolak</span>
                                            @else
                                                <span class="text-warning fw-medium">↺ Revisi</span>
                                            @endif
                                        </div>
                                    @endif
                                @else
                                    <span class="text-secondary">—</span>
                                @endif
                            </td>
                            <td>
                                @if($review && $review->deadline_at)
                                    <div class="small {{ $review->isOverdue() ? 'text-danger fw-bold' : 'text-secondary' }}">
                                        
                                        {{ $review->deadline_at->format('d M Y') }}
                                        @if($review->isOverdue())
                                            <div class="text-danger small mt-1">Terlambat!</div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('research.proposal.show', $proposal) }}"
                                    class="btn btn-primary btn-sm px-3 shadow-sm" wire:navigate.hover>
                                    <x-lucide-edit-3 class="icon me-1" />
                                    Review
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center bg-surface-secondary">
                                <div class="mb-3">
                                    
                                </div>
                                <h3 class="text-secondary">Tidak ada proposal</h3>
                                <p class="text-muted">Tidak ada proposal penelitian yang perlu direview saat ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $this->proposals->links() }}
        </div>
    </div>
</div>
