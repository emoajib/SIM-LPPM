<x-slot:title>Persetujuan Kaprodi</x-slot:title>
<x-slot:pageTitle>Persetujuan Proposal @if($this->studyProgramName) - {{ $this->studyProgramName }} @endif</x-slot:pageTitle>
<x-slot:pageSubtitle>Kelola validasi kesesuaian proposal penelitian dan pengabdian dengan pohon penelitian prodi.</x-slot:pageSubtitle>

<div>
    <x-tabler.alert />

    <div class="alert alert-info alert-dismissible fade show border-0 shadow-sm collapse" id="kaprodiIndexInfo" role="alert">
        <div class="d-flex">
            <div>
                
            </div>
            <div>
                <h4 class="alert-title">Panduan Persetujuan Kaprodi</h4>
                <div class="text-secondary">
                    Halaman ini menampilkan usulan dari program studi Anda yang memerlukan validasi kesesuaian roadmap/pohon penelitian. 
                    Tugas Anda adalah memastikan kesesuaian substansi dengan arah riset prodi. 
                    Klik tombol Validasi pada baris tabel untuk memvalidasi.
                </div>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-toggle="collapse" data-bs-target="#kaprodiIndexInfo" aria-label="Close"></button>
    </div>

    <div class="mb-3">
        <button class="btn btn-ghost-info btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#kaprodiIndexInfo" aria-expanded="false" aria-controls="kaprodiIndexInfo">
            
            Panduan Validasi
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="mb-3 row row-deck row-cards">
        <div class="col-sm-6 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Total Proposal</div>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <div class="me-2 mb-0 h1">{{ $this->statusStats['all'] }}</div>
                        <div class="me-auto">
                            <span class="d-inline-flex align-items-center text-secondary lh-1">
                                Diajukan ke Dekan
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
                        <div class="subheader">Menunggu Validasi Prodi</div>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <div class="me-2 mb-0 h1">{{ $this->statusStats['unvalidated'] }}</div>
                        <div class="me-auto">
                            <span class="d-inline-flex align-items-center text-warning lh-1">
                                
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
                        <div class="subheader">Sudah Divalidasi Prodi</div>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <div class="me-2 mb-0 h1">{{ $this->statusStats['validated'] }}</div>
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
    <div class="mb-3 row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-2 align-items-center">
                        <!-- Search Input -->
                        <div class="col-md-5">
                            <input type="text" class="form-control"
                                placeholder="Cari berdasarkan judul, ringkasan, atau nama pengusul..."
                                wire:model.live.debounce.300ms="search" />
                        </div>

                        <!-- Type Filter -->
                        <div class="col-md-3">
                            <select class="form-select" wire:model.live="typeFilter">
                                <option value="all">Semua Jenis</option>
                                <option value="research">Penelitian</option>
                                <option value="community_service">Pengabdian</option>
                            </select>
                        </div>

                        <!-- Year Filter (Vetted by AI - Manual Review Required by Senior Engineer/Manager) -->
                        <div class="col-md-3">
                            <select class="form-select" wire:model.live="yearFilter">
                                <option value="">Semua Tahun</option>
                                @foreach ($this->availableYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Reset Button -->
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
            <table class="card-table table table-vcenter">
                <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Jenis</th>
                            <th>Pengusul</th>
                            <th>Pohon Penelitian</th>
                            <th>Status Validasi Prodi</th>
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
                                <div>{{ $proposal->submitter->name }}</div>
                            </td>
                            <td>
                                @if ($proposal->studyProgramRoadmap)
                                    <div>{{ $proposal->studyProgramRoadmap->title }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($proposal->is_roadmap_validated_by_kaprodi)
                                    <span class="badge bg-success-lt fw-normal">
                                        Sudah Divalidasi
                                    </span>
                                @else
                                    <span class="badge bg-warning-lt fw-normal">
                                        Menunggu Validasi
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="flex-nowrap btn-list">
                                    @if ($proposal->detailable_type === 'App\Models\Research')
                                        <a href="{{ route('research.proposal.show', $proposal) }}"
                                            class="btn btn-sm btn-outline-secondary" wire:navigate.hover>
                                            
                                            Lihat
                                        </a>
                                    @else
                                        <a href="{{ route('community-service.proposal.show', $proposal) }}"
                                            class="btn btn-sm btn-outline-secondary" wire:navigate.hover>
                                            
                                            Lihat
                                        </a>
                                    @endif

                                    @if(!$proposal->is_roadmap_validated_by_kaprodi)
                                        <button wire:click="validateProposal('{{ $proposal->id }}')" class="btn btn-sm btn-success" wire:confirm="Apakah Anda yakin ingin memvalidasi bahwa usulan ini sudah sesuai dengan pohon penelitian prodi?">
                                            
                                            Validasi
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center">
                                <div class="mb-3">
                                    
                                </div>
                                <p class="text-secondary">Tidak ada proposal di program studi Anda yang memerlukan validasi saat ini.</p>
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
