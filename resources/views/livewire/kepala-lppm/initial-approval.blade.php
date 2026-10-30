<x-slot:title>Persetujuan Awal</x-slot:title>
<x-slot:pageTitle>Persetujuan Awal Kepala LPPM</x-slot:pageTitle>
<x-slot:pageSubtitle>Berikan persetujuan awal untuk proposal yang telah disetujui Dekan agar dapat ditugaskan ke
    reviewer.</x-slot:pageSubtitle>

<div>
    <x-tabler.alert />

    <div class="alert alert-info alert-dismissible fade show border-0 shadow-sm collapse" id="initialApprovalInfo"
        role="alert">
        <div class="d-flex">
            <div>
                
            </div>
            <div>
                <h4 class="alert-title">Panduan Persetujuan Awal</h4>
                <div class="text-secondary">
                    Halaman ini berisi usulan yang telah divalidasi oleh Dekan.
                    Tugas Anda adalah memberikan persetujuan administratif awal agar Admin LPPM dapat melanjutkan proses
                    penugasan reviewer.
                    Persetujuan awal ini memastikan usulan layak untuk masuk ke tahap penilaian substansi.
                </div>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-toggle="collapse" data-bs-target="#initialApprovalInfo"
            aria-label="Close"></button>
    </div>

    <div class="mb-3">
        <button class="btn btn-ghost-info btn-sm" type="button" data-bs-toggle="collapse"
            data-bs-target="#initialApprovalInfo" aria-expanded="false" aria-controls="initialApprovalInfo">
            
            Informasi Alur
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="row row-deck row-cards mb-3">
        <div class="col-sm-6 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Total Proposal</div>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <div class="h1 mb-0 me-2">{{ $this->statusStats['all'] }}</div>
                        <div class="me-auto">
                            <span class="d-inline-flex align-items-center text-secondary lh-1">
                                Menunggu persetujuan awal
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Proposal Penelitian</div>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <div class="h1 mb-0 me-2">{{ $this->statusStats['research'] }}</div>
                        <div class="me-auto">
                            <span class="d-inline-flex align-items-center text-blue lh-1">
                                
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Proposal Pengabdian</div>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <div class="h1 mb-0 me-2">{{ $this->statusStats['community_service'] }}</div>
                        <div class="me-auto">
                            <span class="d-inline-flex align-items-center text-green lh-1">
                                
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-2 align-items-center">
                        <!-- Baris 1: Pencarian, Jenis, dan Tahun -->
                        <div class="col-md-6">
                            <input type="text" class="form-control"
                                placeholder="Cari berdasarkan judul, ringkasan, atau nama pengusul..."
                                wire:model.live.debounce.300ms="search" />
                        </div>

                        <div class="col-md-3">
                            <select class="form-select" wire:model.live="typeFilter">
                                <option value="all">Semua Jenis</option>
                                <option value="research">Penelitian</option>
                                <option value="community_service">Pengabdian</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <select class="form-select" wire:model.live="yearFilter">
                                <option value="">Semua Tahun</option>
                                @foreach ($this->availableYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Baris 2: Fakultas, Skema, dan Reset (Vetted by AI - Manual Review Required by Senior Engineer/Manager) -->
                        <div class="col-md-6">
                            <select class="form-select" wire:model.live="facultyFilter">
                                <option value="all">Semua Fakultas</option>
                                @foreach ($this->faculties as $faculty)
                                    <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if ($typeFilter !== 'all')
                            <div class="col-md-5">
                                <select class="form-select" wire:model.live="schemeFilter">
                                    <option value="all">Semua Skema</option>
                                    @foreach ($this->schemes as $scheme)
                                        <option value="{{ $scheme->id }}">{{ $scheme->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-secondary w-100" wire:click="resetFilters" title="Reset Filter">
                                
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
                        <th>Judul</th>
                        <th>Jenis</th>
                        <th>Pengusul</th>
                        <th class="w-1">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->proposals as $proposal)
                        <tr wire:key="proposal-{{ $proposal->id }}">
                            <td class="text-wrap">
                                <div class="text-reset fw-bold">{{ $proposal->title }}</div>
                                <div class="mt-1">
                                    <span class="badge bg-secondary-lt text-uppercase" style="font-size: 0.75rem;">
                                        {{ $proposal->focusArea?->name ?? '—' }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                @if ($proposal->detailable_type === 'App\Models\Research')
                                    <span class="badge bg-blue-lt">
                                        
                                        Penelitian
                                    </span>
                                @else
                                    <span class="badge bg-green-lt">
                                        
                                        Pengabdian
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $proposal->submitter?->name }}</div>
                                <small class="text-secondary">
                                    {{ $proposal->submitter?->identity?->identity_id }} &middot;
                                    {{ $proposal->updated_at?->format('d M Y H:i') }}
                                </small>
                            </td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    @if ($proposal->detailable_type === 'App\Models\Research')
                                        <a href="{{ route('research.proposal.show', $proposal) }}"
                                            class="btn btn-sm btn-primary" wire:navigate.hover>
                                            
                                            Lihat & Proses
                                        </a>
                                    @else
                                        <a href="{{ route('community-service.proposal.show', $proposal) }}"
                                            class="btn btn-sm btn-primary" wire:navigate.hover>
                                            
                                            Lihat & Proses
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center">
                                <div class="mb-3">
                                    
                                </div>
                                <p class="text-secondary">Tidak ada proposal yang menunggu persetujuan awal.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->proposals->hasPages())
            <div class="d-flex align-items-center card-footer">
                {{ $this->proposals->links() }}
            </div>
        @endif
    </div>
</div>