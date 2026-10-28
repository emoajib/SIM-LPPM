<x-slot:title>Persetujuan Proposal</x-slot:title>
<x-slot:pageTitle>Persetujuan Proposal @if($this->facultyName) - {{ $this->facultyName }} @endif</x-slot:pageTitle>
<x-slot:pageSubtitle>Kelola persetujuan proposal penelitian dan pengabdian yang telah diajukan di fakultas Anda.</x-slot:pageSubtitle>

<div>
    <x-tabler.alert />

    <div class="alert alert-info alert-dismissible fade show border-0 shadow-sm collapse" id="dekanIndexInfo" role="alert">
        <div class="d-flex">
            <div>
                
            </div>
            <div>
                <h4 class="alert-title">Panduan Persetujuan Dekan</h4>
                <div class="text-secondary">
                    Halaman ini menampilkan usulan dari fakultas Anda yang memerlukan validasi awal. 
                    Tugas Anda adalah memastikan kesesuaian tim dan substansi awal sebelum diteruskan ke LPPM. 
                    Klik <strong>Lihat & Proses</strong> untuk meninjau dan memberikan keputusan.
                </div>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-toggle="collapse" data-bs-target="#dekanIndexInfo" aria-label="Close"></button>
    </div>

    <div class="mb-3">
        <button class="btn btn-ghost-info btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#dekanIndexInfo" aria-expanded="false" aria-controls="dekanIndexInfo">
            
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
                                Menunggu persetujuan
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
                        <div class="me-2 mb-0 h1">{{ $this->statusStats['research'] }}</div>
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
                        <div class="me-2 mb-0 h1">{{ $this->statusStats['community_service'] }}</div>
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
                    <div class="row g-3">
                        <!-- Search Input -->
                        <div class="col-md-3">
                            <input type="text" class="form-control"
                                placeholder="Cari berdasarkan judul atau ringkasan..."
                                wire:model.live.debounce.300ms="search" />
                        </div>

                        <!-- Type Filter -->
                        <div class="col-md-2">
                            <select class="form-select" wire:model.live="typeFilter">
                                <option value="all">Semua Jenis</option>
                                <option value="research">Penelitian</option>
                                <option value="community_service">Pengabdian</option>
                            </select>
                        </div>

                        <!-- Study Program Filter (Vetted by AI - Manual Review Required by Senior Engineer/Manager) -->
                        <div class="col-md-3">
                            <select class="form-select" wire:model.live="studyProgramFilter">
                                <option value="all">Semua Program Studi</option>
                                @foreach ($this->availableStudyPrograms as $prodi)
                                    <option value="{{ $prodi->id }}">{{ $prodi->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Year Filter -->
                        <div class="col-md-2">
                            <select class="form-select" wire:model.live="yearFilter">
                                <option value="">Semua Tahun</option>
                                @foreach ($this->availableYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Reset Button -->
                        <div class="col-md-2">
                            <button type="button" class="btn btn-outline-secondary w-100" wire:click="resetFilters">
                                
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
                            <th>Jenis</th>
                            <th>Pengusul</th>
                            <th>Status</th>
                            <th class="w-1">Aksi</th>
                        </tr>
                </thead>
                <tbody>
                    @forelse ($this->proposals as $proposal)
                        <tr wire:key="proposal-{{ $proposal->id }}">
                            <td class="text-wrap">
                                <div class="text-reset fw-bold">{{ $proposal->title }}</div>
                                <div class="mt-1">
                                    <x-tabler.badge variant="outline" class="text-uppercase" style="font-size: 0.65rem;">
                                        {{ $proposal->focusArea?->name ?? '—' }}
                                    </x-tabler.badge>
                                </div>
                            </td>
                            <td>
                                @if ($proposal->detailable_type === 'App\Models\Research')
                                    <x-tabler.badge color="blue" variant="light">
                                        
                                        Penelitian
                                    </x-tabler.badge>
                                @else
                                    <x-tabler.badge color="green" variant="light">
                                        
                                        Pengabdian
                                    </x-tabler.badge>
                                @endif
                            </td>
                            <td>
                                <div>{{ $proposal->submitter->name }}</div>
                                <div class="small text-secondary">
                                    {{ $proposal->submitter->identity?->studyProgram?->name ?? '—' }}
                                </div>
                            </td>
                            <td>
                                <x-tabler.badge :color="$proposal->status->color()" class="fw-normal">
                                    {{ $proposal->status->label() }}
                                </x-tabler.badge>
                                @if(\App\Models\Setting::get('feature_roadmap_active', false) && \App\Models\Setting::get('feature_kaprodi_validation', false))
                                    <div class="mt-1">
                                        @if($proposal->is_roadmap_validated_by_kaprodi)
                                            <span class="badge badge-outline text-green d-inline-flex align-items-center">
                                                <x-lucide-check-circle-2 class="icon icon-sm me-1" />
                                                Roadmap OK
                                            </span>
                                        @else
                                            <span class="badge badge-outline text-warning d-inline-flex align-items-center" title="Menunggu validasi Kaprodi">
                                                
                                                Cek Roadmap
                                            </span>
                                        @endif
                                    </div>
                                @endif
                                <div class="mt-1">
                                    <small class="text-secondary">
                                        {{ $proposal->created_at?->format('d M Y') }}
                                    </small>
                                </div>
                            </td>
                            <td>
                                <div class="flex-nowrap btn-list">
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
                                <p class="text-secondary">Tidak ada proposal yang menunggu persetujuan.</p>
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
