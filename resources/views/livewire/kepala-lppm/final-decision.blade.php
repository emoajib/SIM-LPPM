<x-slot:title>Persetujuan Akhir</x-slot:title>
<x-slot:pageTitle name="pageTitle">Persetujuan Akhir Kepala LPPM</x-slot:pageTitle>
<x-slot:pageSubtitle>Berikan keputusan akhir untuk proposal yang telah selesai direview.</x-slot:pageSubtitle>

<div>
    <x-tabler.alert />

    <div class="alert alert-info alert-dismissible fade show border-0 shadow-sm collapse" id="finalDecisionInfo"
        role="alert">
        <div class="d-flex">
            <div>
                <x-lucide-info class="alert-icon icon me-2" />
            </div>
            <div>
                <h4 class="alert-title">Panduan Keputusan Final</h4>
                <div class="text-secondary">
                    Halaman ini menampilkan usulan yang telah selesai dinilai oleh seluruh reviewer.
                    Anda dapat melihat ringkasan rekomendasi reviewer sebelum memberikan keputusan akhir (Diterima /
                    Perlu Revisi / Ditolak).
                    Keputusan <strong>Diterima</strong> akan menandai proposal sebagai usulan yang didanai/selesai.
                </div>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-toggle="collapse" data-bs-target="#finalDecisionInfo"
            aria-label="Close"></button>
    </div>

    <div class="mb-3">
        <button class="btn btn-ghost-info btn-sm" type="button" data-bs-toggle="collapse"
            data-bs-target="#finalDecisionInfo" aria-expanded="false" aria-controls="finalDecisionInfo">
            <x-lucide-info class="icon me-1" />
            Informasi Pengambilan Keputusan
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
                                Siap untuk keputusan akhir
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
                                <x-lucide-microscope class="icon icon-sm" />
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
                                <x-lucide-hand-heart class="icon icon-sm" />
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
                    <div class="row g-3">
                        <!-- First Row -->
                        <div class="col-md-4">
                            <label class="form-label">Pencarian</label>
                            <input type="text" class="form-control"
                                placeholder="Cari berdasarkan judul atau ringkasan..."
                                wire:model.live.debounce.300ms="search" />
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Jenis</label>
                            <select class="form-select" wire:model.live="typeFilter">
                                <option value="all">Semua Jenis</option>
                                <option value="research">Penelitian</option>
                                <option value="community_service">Pengabdian</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Skema</label>
                            <select class="form-select" wire:model.live="schemeFilter" @if($typeFilter === 'all') disabled @endif>
                                <option value="">Semua Skema</option>
                                @foreach ($this->availableSchemes as $scheme)
                                    <option value="{{ $scheme->id }}">{{ $scheme->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Tahun</label>
                            <select class="form-select" wire:model.live="yearFilter">
                                <option value="">Semua Tahun</option>
                                @foreach ($this->availableYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Second Row -->
                        <div class="col-md-3">
                            <label class="form-label">Rekomendasi Reviewer</label>
                            <select class="form-select" wire:model.live="recommendationFilter">
                                <option value="all">Semua Rekomendasi</option>
                                <option value="all_approved">Semua Disetujui</option>
                                <option value="needs_revision">Ada Revisi</option>
                                <option value="rejected">Ada Penolakan</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Fakultas</label>
                            <select class="form-select" wire:model.live="facultyFilter">
                                <option value="">Semua Fakultas</option>
                                @foreach ($this->availableFaculties as $faculty)
                                    <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Program Studi</label>
                            <select class="form-select" wire:model.live="studyProgramFilter">
                                <option value="">Semua Prodi</option>
                                @foreach ($this->availableStudyPrograms as $prodi)
                                    <option value="{{ $prodi->id }}">{{ $prodi->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn-outline-secondary w-100 btn" wire:click="resetFilters">
                                <x-lucide-rotate-ccw class="icon me-1" />
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
                        <th>Judul</th>
                        <th>Jenis</th>
                        <th>Pengusul</th>
                        <th>Reviewer & Rekomendasi</th>
                        <th>Tanggal Selesai</th>
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
                                        <x-lucide-microscope class="icon icon-sm me-1" />
                                        Penelitian
                                    </x-tabler.badge>
                                @else
                                    <x-tabler.badge color="green" variant="light">
                                        <x-lucide-hand-heart class="icon icon-sm me-1" />
                                        Pengabdian
                                    </x-tabler.badge>
                                @endif
                            </td>
                            <td>
                                <div>{{ $proposal->submitter?->name }}</div>
                                <small class="text-secondary">{{ $proposal->submitter?->identity?->identity_id }}</small>
                            </td>
                            <td>
                                @foreach ($proposal->reviewers as $reviewer)
                                    <div class="d-flex align-items-center mb-1">
                                        @if($reviewer->recommendation === 'approved')
                                            <x-lucide-check-circle class="icon icon-sm text-success me-1" title="Disetujui" />
                                        @elseif($reviewer->recommendation === 'rejected')
                                            <x-lucide-x-circle class="icon icon-sm text-danger me-1" title="Ditolak" />
                                        @else
                                            <x-lucide-rotate-ccw class="icon icon-sm text-warning me-1" title="Revisi" />
                                        @endif
                                        <small>{{ $reviewer->user?->name }}</small>
                                    </div>
                                @endforeach
                            </td>
                            <td>
                                <small class="text-secondary">
                                    {{ $proposal->updated_at?->format('d M Y') }}
                                </small>
                            </td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    @if ($proposal->detailable_type === 'App\Models\Research')
                                        <a href="{{ route('research.proposal.show', $proposal) }}"
                                            class="btn btn-sm btn-primary" wire:navigate.hover>
                                            <x-lucide-eye class="icon" />
                                            Lihat & Proses
                                        </a>
                                    @else
                                        <a href="{{ route('community-service.proposal.show', $proposal) }}"
                                            class="btn btn-sm btn-primary" wire:navigate.hover>
                                            <x-lucide-eye class="icon" />
                                            Lihat & Proses
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center">
                                <div class="mb-3">
                                    <x-lucide-inbox class="text-secondary icon icon-lg" />
                                </div>
                                <p class="text-secondary">Tidak ada proposal yang siap untuk keputusan akhir.</p>
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