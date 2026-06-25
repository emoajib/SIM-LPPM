<x-slot:title>Monitoring Progres Review</x-slot:title>
<x-slot:pageTitle>Monitoring Progres Review</x-slot:pageTitle>
<x-slot:pageSubtitle>Pantau status penyelesaian review untuk setiap usulan yang sedang dalam tahap evaluasi.</x-slot:pageSubtitle>

<div>
    <div class="mb-3 card">
        <div class="card-body">
            <div class="row g-3">
                <!-- Baris 1: Pencarian -->
                <div class="col-md-6">
                    <label class="form-label">Cari Judul Proposal</label>
                    <input type="text" class="form-control" placeholder="Cari judul proposal..."
                        wire:model.live.debounce.300ms="search">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Cari Nama Reviewer</label>
                    <input type="text" class="form-control" placeholder="Cari nama reviewer..."
                        wire:model.live.debounce.300ms="reviewerSearch">
                </div>

                <!-- Baris 2: Kategori Usulan & Progres -->
                <div class="col-md-4">
                    <label class="form-label">Jenis Usulan</label>
                    <select class="form-select" wire:model.live="typeFilter">
                        <option value="all">Semua Jenis</option>
                        <option value="research">Penelitian</option>
                        <option value="community_service">Pengabdian</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Skema Usulan</label>
                    <select class="form-select" wire:model.live="schemeFilter" @if($typeFilter === 'all') disabled title="Pilih Jenis Usulan terlebih dahulu" @endif>
                        <option value="all">Semua Skema</option>
                        @foreach($this->schemes as $scheme)
                            <option value="{{ $scheme->id }}">{{ $scheme->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status Progres</label>
                    <select class="form-select" wire:model.live="progressFilter">
                        <option value="all">Semua Progres</option>
                        <option value="unassigned">Belum Ditugaskan / Kurang Reviewer</option>
                        <option value="in_progress">Sedang Direview</option>
                        <option value="completed">Selesai Direview (100%)</option>
                    </select>
                </div>

                <!-- Baris 3: Institusi -->
                <div class="col-md-5">
                    <label class="form-label">Fakultas Pengusul</label>
                    <select class="form-select" wire:model.live="facultyFilter">
                        <option value="all">Semua Fakultas</option>
                        @foreach($this->faculties as $faculty)
                            <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Program Studi Pengusul</label>
                    <select class="form-select" wire:model.live="prodiFilter" @if($facultyFilter === 'all') disabled title="Pilih Fakultas terlebih dahulu" @endif>
                        <option value="all">Semua Prodi</option>
                        @foreach($this->studyPrograms as $prodi)
                            <option value="{{ $prodi->id }}">{{ $prodi->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn-outline-secondary w-100 btn" wire:click="resetFilters">Reset Filter</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="card-table table table-vcenter">
                <thead>
                    <tr>
                        <th>Proposal</th>
                        <th>Reviewer & Status</th>
                        <th>Progres</th>
                        <th class="w-1">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->proposals as $proposal)
                        <tr wire:key="prop-{{ $proposal->id }}">
                            <td class="text-wrap">
                                <div class="fw-bold">{{ $proposal->title }}</div>
                                <div class="text-secondary small">{{ $proposal->submitter?->name }}</div>
                            </td>
                            <td>
                                {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
                                @php
                                    $requiredCount = (int) \App\Models\Setting::get('reviewer_count_required', 1);
                                    $assignedCount = $proposal->reviewers->count();
                                @endphp
                                @if ($proposal->reviewers->isEmpty())
                                    <span class="text-danger small">Belum ada reviewer ditugaskan</span>
                                    <div class="mt-1">
                                        <span class="badge bg-danger-lt">Butuh {{ $requiredCount }} Reviewer</span>
                                    </div>
                                @else
                                    <div class="mb-2 avatar-list-stacked avatar-list">
                                        @foreach ($proposal->reviewers as $reviewer)
                                            <span class="rounded avatar avatar-xs"
                                                title="{{ $reviewer->user?->name }}: {{ $reviewer->status->label() }}"
                                                style="background-image: url({{ $reviewer->user?->profile_picture }})">
                                            </span>
                                        @endforeach
                                    </div>
                                    <div class="small mb-1">
                                        @foreach ($proposal->reviewers as $reviewer)
                                            <div class="d-flex align-items-center mb-1">
                                                @if ($reviewer->isCompleted())
                                                    <x-lucide-check-circle class="me-1 text-success icon icon-sm" />
                                                @else
                                                    <x-lucide-clock class="me-1 text-warning icon icon-sm" />
                                                @endif
                                                <span>{{ $reviewer->user?->name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                    @if ($assignedCount < $requiredCount)
                                        <div>
                                            <span class="badge bg-warning-lt">Kurang {{ $requiredCount - $assignedCount }} Reviewer</span>
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td>
                                {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
                                @php
                                    $totalToCompare = max($assignedCount, $requiredCount);
                                    $doneRev = $proposal->reviewers->filter(fn($r) => $r->isCompleted())->count();
                                    $percentage = $totalToCompare > 0 ? round(($doneRev / $totalToCompare) * 100) : 0;
                                @endphp
                                <div class="d-flex align-items-center">
                                    <div class="me-2 w-100 progress progress-xs">
                                        <div class="progress-bar bg-{{ $percentage == 100 && $assignedCount >= $requiredCount ? 'success' : 'primary' }}"
                                            x-data :style="'width: ' + {{ $percentage }} + '%'"></div>
                                    </div>
                                    <span class="small">{{ $doneRev }}/{{ $totalToCompare }}</span>
                                </div>
                            </td>
                            <td>
                                <a href="{{ $proposal->detailable_type === 'App\Models\Research' ? route('research.proposal.show', $proposal) : route('community-service.proposal.show', $proposal) }}"
                                    class="btn-outline-primary btn btn-sm" wire:navigate.hover>
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-4 text-center">Tidak ada proposal dalam tahap review.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($this->proposals->hasPages())
            <div class="card-footer">
                {{ $this->proposals->links() }}
            </div>
        @endif
    </div>
</div>
