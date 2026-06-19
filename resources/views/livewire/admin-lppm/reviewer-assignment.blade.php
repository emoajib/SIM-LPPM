<x-slot:title>Penugasan Reviewer</x-slot:title>
<x-slot:pageTitle>Penugasan Reviewer</x-slot:pageTitle>
<x-slot:pageSubtitle>Tugaskan reviewer untuk mengevaluasi proposal yang telah mendapat persetujuan awal Kepala
    LPPM.</x-slot:pageSubtitle>

<div>
    <x-tabler.alert />

    <div class="alert alert-info alert-dismissible fade show border-0 shadow-sm collapse" id="reviewerAssignmentInfo" role="alert">
        <div class="d-flex">
            <div>
                <x-lucide-info class="alert-icon icon me-2" />
            </div>
            <div>
                <h4 class="alert-title">Panduan Penugasan Reviewer</h4>
                <div class="text-secondary">
                    Halaman ini menampilkan usulan yang telah disetujui awal oleh Kepala LPPM. 
                    Tugas Anda adalah menunjuk reviewer yang kompeten untuk setiap usulan. 
                    Setelah reviewer ditugaskan, status usulan akan berubah menjadi <strong>Sedang Direview</strong> secara otomatis.
                </div>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-toggle="collapse" data-bs-target="#reviewerAssignmentInfo" aria-label="Close"></button>
    </div>

    <div class="mb-3">
        <button class="btn btn-ghost-info btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#reviewerAssignmentInfo" aria-expanded="false" aria-controls="reviewerAssignmentInfo">
            <x-lucide-info class="icon me-1" />
            Panduan Penugasan
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="mb-3 row row-deck row-cards">
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Total Proposal</div>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <div class="me-2 mb-0 h1">{{ $this->statusStats['all'] }}</div>
                        <div class="me-auto">
                            <span class="d-inline-flex align-items-center text-secondary lh-1">
                                Siap ditugaskan
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Penelitian</div>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <div class="me-2 mb-0 h1">{{ $this->statusStats['research'] }}</div>
                        <div class="me-auto">
                            <span class="d-inline-flex align-items-center text-blue lh-1">
                                <x-lucide-microscope class="icon icon-sm" />
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Pengabdian</div>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <div class="me-2 mb-0 h1">{{ $this->statusStats['community_service'] }}</div>
                        <div class="me-auto">
                            <span class="d-inline-flex align-items-center text-green lh-1">
                                <x-lucide-hand-heart class="icon icon-sm" />
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Belum Ditugaskan</div>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <div class="me-2 mb-0 h1">{{ $this->statusStats['unassigned'] }}</div>
                        <div class="me-auto">
                            <span class="d-inline-flex align-items-center text-yellow lh-1">
                                <x-lucide-alert-circle class="icon icon-sm" />
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
                            <input type="text" class="form-control" placeholder="Cari berdasarkan judul..."
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

                        <!-- Assignment Filter -->
                        <div class="col-md-2">
                            <select class="form-select" wire:model.live="assignmentFilter">
                                <option value="all">Semua Status</option>
                                <option value="unassigned">Belum Ditugaskan</option>
                                <option value="assigned">Sudah Ditugaskan</option>
                            </select>
                        </div>

                        <!-- Faculty Filter -->
                        <div class="col-md-3">
                            <select class="form-select" wire:model.live="facultyFilter">
                                <option value="">Semua Fakultas</option>
                                @foreach($this->faculties as $faculty)
                                    <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Study Program Filter -->
                        <div class="col-md-2">
                            <select class="form-select" wire:model.live="studyProgramFilter" @if(!$facultyFilter) disabled @endif>
                                <option value="">Semua Prodi</option>
                                @foreach($this->studyPrograms as $prodi)
                                    <option value="{{ $prodi->id }}">{{ $prodi->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Scheme Filter -->
                        <div class="col-md-3">
                            <select class="form-select" wire:model.live="schemeFilter" @if($typeFilter === 'all') disabled @endif>
                                <option value="">Semua Skema</option>
                                @foreach($this->schemes as $scheme)
                                    <option value="{{ $scheme->id }}">{{ $scheme->name }}</option>
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
                        <th>Jenis</th>
                        <th>Pengusul</th>
                        <th>Reviewer</th>
                        <th class="w-1">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->proposals as $proposal)
                        <tr wire:key="proposal-{{ $proposal->id }}">
                            <td class="text-wrap">
                                <div class="text-reset fw-bold">{{ $proposal->title }}</div>
                                <div class="mt-1">
                                    <x-tabler.badge variant="outline" class="text-uppercase"
                                        style="font-size: 0.65rem;">
                                        {{ $proposal->focusArea?->name ?? '—' }}
                                    </x-tabler.badge>
                                </div>
                            </td>
                            <td>
                                @if ($proposal->detailable_type === 'App\Models\Research')
                                    <x-tabler.badge color="blue" variant="light">
                                        <x-lucide-microscope class="me-1 icon icon-sm" />
                                        Penelitian
                                    </x-tabler.badge>
                                @else
                                    <x-tabler.badge color="green" variant="light">
                                        <x-lucide-hand-heart class="me-1 icon icon-sm" />
                                        Pengabdian
                                    </x-tabler.badge>
                                @endif
                            </td>
                            <td>
                                <div>{{ $proposal->submitter?->name }}</div>
                                <small class="text-secondary">
                                    {{ $proposal->submitter?->identity?->identity_id ?? '-' }} &middot;
                                    {{ $proposal->updated_at?->format('d M Y') }}
                                </small>
                            </td>
                            <td>
                                @if ($proposal->reviewers->isEmpty())
                                    <span class="text-danger small">Belum ditugaskan</span>
                                @else
                                    <div class="mb-2 avatar-list-stacked avatar-list">
                                        @foreach ($proposal->reviewers as $reviewer)
                                            <span class="rounded avatar avatar-xs" title="{{ $reviewer->user?->name }}"
                                                style="background-image: url({{ $reviewer->user?->profile_picture }})"
                                                wire:key="rev-{{ $reviewer->id }}">
                                            </span>
                                        @endforeach
                                    </div>
                                    <div class="text-secondary small">
                                        @foreach ($proposal->reviewers as $reviewer)
                                            <div class="mb-1 lh-1">{{ $reviewer->user?->name }}</div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="flex-nowrap btn-list">
                                    <button type="button"
                                        wire:click="openAssignModal('{{ $proposal->id }}')"
                                        class="btn btn-sm btn-primary"
                                        data-bs-toggle="modal" data-bs-target="#modal-assign-reviewer">
                                        <x-lucide-user-plus class="icon" />
                                        Tugaskan
                                    </button>
                                    <a href="{{ $proposal->detailable_type === 'App\Models\Research' ? route('research.proposal.show', $proposal) : route('community-service.proposal.show', $proposal) }}"
                                        class="btn btn-sm btn-outline-secondary" wire:navigate.hover>
                                        <x-lucide-eye class="icon" />
                                        Detail
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center">
                                <div class="mb-3">
                                    <x-lucide-inbox class="text-secondary icon icon-lg" />
                                </div>
                                <p class="text-secondary">Tidak ada proposal yang perlu ditugaskan ke reviewer.</p>
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

    <!-- Assign Reviewer Modal -->
    @teleport('body')
        <x-tabler.modal id="modal-assign-reviewer" title="Tugaskan Reviewer" on-show="resetReviewerForm">
            <x-slot:body>
                <form wire:submit.prevent="assignReviewers" id="admin-reviewer-assignment-form">
                    <div class="mb-3">
                        <label class="form-label" for="selectedReviewer">Pilih Reviewer <span
                                class="text-danger">*</span></label>
                        <div wire:ignore>
                            <select wire:model="selectedReviewer" class="form-select tom-select" id="adminSelectedReviewer"
                                x-data="tomSelect" placeholder="Pilih reviewer..." required>
                                <option value="" selected disabled>Pilih reviewer...</option>
                                @foreach ($this->availableReviewers as $reviewer)
                                    <option wire:key="admin-reviewer-{{ $reviewer->id }}" value="{{ $reviewer->id }}">
                                        {{ $reviewer->name }}
                                        ({{ $reviewer->identity?->identity_id ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('selectedReviewer')
                            <div class="d-block mt-2 text-danger">{{ $message }}</div>
                        @enderror
                        <small class="text-muted form-text">Pilih satu reviewer untuk ditugaskan pada proposal ini.</small>
                    </div>
                </form>
            </x-slot:body>

            <x-slot:footer>
                <button type="button" class="btn-outline-secondary btn" data-bs-dismiss="modal">
                    Batal
                </button>
                <button type="submit" form="admin-reviewer-assignment-form" class="btn btn-primary" wire:loading.attr="disabled"
                    data-bs-dismiss="modal">
                    <x-lucide-send class="icon" />
                    <span wire:loading.remove>Tugaskan</span>
                    <span wire:loading>Menyimpan...</span>
                </button>
            </x-slot:footer>
        </x-tabler.modal>
    @endteleport
</div>
