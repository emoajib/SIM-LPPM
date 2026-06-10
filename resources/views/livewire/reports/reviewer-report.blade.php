{{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
<div>
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="align-items-center row g-2">
                <div class="col">
                    <h2 class="page-title text-primary fw-bold">
                        Laporan Penugasan & Reviewer
                    </h2>
                    <div class="text-muted mt-1">
                        Rekapitulasi beban penugasan, status review, dan rincian komponen penilaian usulan dosen.
                    </div>
                </div>
                <div class="col-auto ms-auto d-flex gap-2">
                    <a href="{{ route('reports.reviewer.pdf', ['period' => $yearFilter, 'type' => $typeFilter, 'search' => $search]) }}" 
                       class="btn btn-danger d-flex align-items-center gap-1 shadow-sm" target="_blank">
                        <i class="ti ti-file-text fs-2"></i>
                        <span>Unduh PDF</span>
                    </a>
                    <a href="{{ route('reports.reviewer.excel', ['period' => $yearFilter, 'type' => $typeFilter, 'search' => $search]) }}" 
                       class="btn btn-success d-flex align-items-center gap-1 shadow-sm">
                        <i class="ti ti-file-spreadsheet fs-2"></i>
                        <span>Ekspor Excel</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Metrics Cards Section -->
    <div class="row row-cards mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm glass-card" style="border-left: 4px solid #206bc4 !important;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-primary text-white avatar shadow-sm border-0">
                                <i class="ti ti-file-description fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="subheader text-muted fw-bold">Total Usulan</div>
                            <div class="h2 mb-0 fw-bold">{{ $this->summaryStats['total_proposals'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm glass-card" style="border-left: 4px solid #f59f00 !important;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-warning text-white avatar shadow-sm border-0">
                                <i class="ti ti-user-plus fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="subheader text-muted fw-bold">Terplot Reviewer</div>
                            <div class="h2 mb-0 fw-bold">{{ $this->summaryStats['assigned'] }} <span class="small text-muted font-weight-normal">/ {{ $this->summaryStats['total_proposals'] }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm glass-card" style="border-left: 4px solid #0ca678 !important;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-teal text-white avatar shadow-sm border-0">
                                <i class="ti ti-chart-pie fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="subheader text-muted fw-bold">Review Selesai</div>
                            <div class="h2 mb-0 fw-bold">{{ $this->summaryStats['progress_percent'] }}%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm glass-card" style="border-left: 4px solid #ae3ec9 !important;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-purple text-white avatar shadow-sm border-0">
                                <i class="ti ti-award fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="subheader text-muted fw-bold">Rata-rata Skor</div>
                            <div class="h2 mb-0 fw-bold">{{ $this->summaryStats['avg_score'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation & Filter Bar -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <!-- Tabs -->
                <ul class="nav nav-tabs border-0 gap-1" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link border-0 px-3 py-2 fw-bold d-flex align-items-center gap-1 {{ $activeTab === 'assignment' ? 'active text-primary bg-light' : 'text-muted' }}" 
                                wire:click="$set('activeTab', 'assignment')">
                            <i class="ti ti-user-plus"></i> Ikhtisar Penugasan
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link border-0 px-3 py-2 fw-bold d-flex align-items-center gap-1 {{ $activeTab === 'workload' ? 'active text-primary bg-light' : 'text-muted' }}" 
                                wire:click="$set('activeTab', 'workload')">
                            <i class="ti ti-chart-bar"></i> Beban Kerja Reviewer
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link border-0 px-3 py-2 fw-bold d-flex align-items-center gap-1 {{ $activeTab === 'scoring' ? 'active text-primary bg-light' : 'text-muted' }}" 
                                wire:click="$set('activeTab', 'scoring')">
                            <i class="ti ti-award"></i> Rekap Nilai Penilaian
                        </button>
                    </li>
                </ul>

                <!-- Filter Controls -->
                <div class="d-flex align-items-center gap-2 flex-grow-1 flex-md-grow-0">
                    <div class="input-icon flex-grow-1">
                        <span class="input-icon-addon">
                            <i class="ti ti-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control" placeholder="Cari dosen / judul..." wire:model.live.debounce.350ms="search">
                    </div>
                    <select class="form-select w-auto" wire:model.live="typeFilter">
                        <option value="all">Semua Jenis</option>
                        <option value="research">Penelitian</option>
                        <option value="community_service">PKM</option>
                    </select>
                    <select class="form-select w-auto" wire:model.live="yearFilter">
                        @foreach ($this->availableYears as $year)
                            <option value="{{ $year }}">Tahun: {{ $year }}</option>
                        @endforeach
                    </select>
                    @if ($search || $typeFilter !== 'all')
                        <button class="btn btn-ghost-danger p-2" wire:click="resetFilters" title="Reset filter">
                            <i class="ti ti-x fs-2"></i>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Data Tables Container -->
    <div class="card border-0 shadow-sm overflow-hidden">
        @if ($activeTab === 'assignment')
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-hover table-borderless">
                    <thead class="bg-light-lt">
                        <tr>
                            <th class="ps-4">Judul & Pengaju</th>
                            <th>Jenis Usulan</th>
                            <th>Skema</th>
                            <th>Reviewer Terpilih</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->proposals as $proposal)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-wrap lh-base">{{ $proposal->title }}</div>
                                    <div class="small text-muted mt-1 d-flex align-items-center">
                                        <span class="avatar avatar-xs me-2 border-0" style="background-image: url({{ $proposal->submitter->profile_picture }})"></span>
                                        {{ $proposal->submitter->name }} ({{ $proposal->submitter->identity->faculty->name ?? '-' }})
                                    </div>
                                </td>
                                <td>
                                    @if ($proposal->detailable_type === 'App\Models\Research')
                                        <span class="badge bg-blue-lt fw-normal">Penelitian</span>
                                    @else
                                        <span class="badge bg-teal-lt fw-normal">PKM</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-muted small">{{ $proposal->researchScheme->name ?? $proposal->communityServiceScheme->name ?? '-' }}</span>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        @forelse ($proposal->reviewers as $rev)
                                            <div class="d-flex align-items-center small">
                                                <i class="ti ti-user-check text-success me-1"></i>
                                                <span class="fw-medium">{{ $rev->user->name }}</span>
                                            </div>
                                        @empty
                                            <span class="text-danger small fw-medium"><i class="ti ti-alert-triangle me-1"></i>Belum ada reviewer</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if ($proposal->reviewers->count() === 0)
                                        <span class="badge bg-danger-lt">Belum Diplot</span>
                                    @elseif ($proposal->reviewers->count() < 2)
                                        <span class="badge bg-warning-lt">Kurang 1 Reviewer</span>
                                    @else
                                        <span class="badge bg-success-lt">Lengkap</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <div class="empty">
                                        <p class="empty-title">Tidak ada data penugasan ditemukan</p>
                                        <p class="empty-subtitle">Coba sesuaikan filter pencarian atau tahun anggaran Anda.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-transparent border-0 py-3">
                {{ $this->proposals->links() }}
            </div>

        @elseif ($activeTab === 'workload')
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-hover table-borderless">
                    <thead class="bg-light-lt">
                        <tr>
                            <th class="ps-4">Nama Reviewer</th>
                            <th>Fakultas / Instansi</th>
                            <th class="text-center">Total Penugasan</th>
                            <th class="text-center">Tinjauan Selesai</th>
                            <th class="text-center">Tinjauan Pending</th>
                            <th>Beban Kerja</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->reviewersWorkload as $rev)
                            <tr>
                                <td class="ps-4 fw-bold">
                                    {{ $rev->name }}
                                </td>
                                <td>
                                    <span class="text-muted">{{ $rev->identity->faculty->name ?? '-' }}</span>
                                </td>
                                <td class="text-center fw-bold">{{ $rev->total_assigned }}</td>
                                <td class="text-center text-success fw-bold">{{ $rev->completed_count }}</td>
                                <td class="text-center text-warning fw-bold">{{ $rev->pending_count }}</td>
                                <td class="pe-4" style="width: 250px;">
                                    @php
                                        $percentage = $rev->total_assigned > 0 ? round(($rev->completed_count / $rev->total_assigned) * 100) : 0;
                                        $barColor = $percentage === 100 ? 'bg-success' : ($percentage >= 50 ? 'bg-warning' : 'bg-danger');
                                    @endphp
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress progress-sm flex-grow-1 shadow-none rounded-pill" style="height: 6px;">
                                            <div class="progress-bar {{ $barColor }}" style="width: {{ $percentage }}%" role="progressbar"></div>
                                        </div>
                                        <span class="small fw-bold text-muted">{{ $percentage }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <p class="empty-title">Tidak ada reviewer terdaftar</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @elseif ($activeTab === 'scoring')
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-hover table-borderless">
                    <thead class="bg-light-lt">
                        <tr>
                            <th class="ps-4">Judul & Dosen Pengaju</th>
                            <th class="text-center">Skor Rev 1</th>
                            <th class="text-center">Skor Rev 2</th>
                            <th class="text-center">Nilai Rata-rata</th>
                            <th class="text-center">Status Review</th>
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->proposals as $proposal)
                            @php
                                // Vetted by AI - Manual Review Required by Senior Engineer/Manager
                                $r1 = $proposal->reviewers[0] ?? null;
                                $r2 = $proposal->reviewers[1] ?? null;
                                $r1Score = $r1 && $r1->isCompleted() ? ($proposal->reviewers[0]->latestLog()->total_score ?? '-') : '-';
                                $r2Score = $r2 && $r2->isCompleted() ? ($proposal->reviewers[1]->latestLog()->total_score ?? '-') : '-';
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-wrap lh-base">{{ $proposal->title }}</div>
                                    <div class="small text-muted mt-1">
                                        <span class="fw-medium text-dark">{{ $proposal->submitter->name }}</span> ({{ $proposal->submitter->identity->faculty->name ?? '-' }})
                                    </div>
                                </td>
                                <td class="text-center font-monospace">{{ $r1Score }}</td>
                                <td class="text-center font-monospace">{{ $r2Score }}</td>
                                <td class="text-center font-monospace fw-bold text-primary">
                                    {{ $proposal->score ?? '-' }}
                                </td>
                                <td class="text-center">
                                    @if ($proposal->status === \App\Enums\ProposalStatus::REVIEWED)
                                        <span class="badge bg-success-lt">Selesai Direview</span>
                                    @elseif ($proposal->status === \App\Enums\ProposalStatus::UNDER_REVIEW)
                                        <span class="badge bg-warning-lt">Proses Review</span>
                                    @else
                                        <span class="badge bg-secondary-lt">{{ $proposal->status->label() }}</span>
                                    @endif
                                </td>
                                <td class="text-center pe-4">
                                    <button class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1 shadow-sm" 
                                            wire:click="selectProposal('{{ $proposal->id }}')" 
                                            data-bs-toggle="modal" data-bs-target="#modal-detail-skor">
                                        <i class="ti ti-eye"></i> Detail
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <div class="empty">
                                        <p class="empty-title">Tidak ada data rekapitulasi nilai</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-transparent border-0 py-3">
                {{ $this->proposals->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Detail Skor Component Kriteria -->
    <div class="modal modal-blur fade" id="modal-detail-skor" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="ti ti-award me-1"></i> Rincian Komponen Nilai Kriteria</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" wire:click="selectProposal(null)"></button>
                </div>
                <div class="modal-body py-3">
                    @if ($selectedProposal)
                        <div class="mb-3 border-bottom pb-2">
                            <h4 class="fw-bold text-dark mb-1">{{ $selectedProposal->title }}</h4>
                            <p class="text-muted small mb-0">
                                Dosen Pengaju: <span class="fw-bold text-dark">{{ $selectedProposal->submitter->name }}</span> | 
                                Fakultas: <span class="fw-bold text-dark">{{ $selectedProposal->submitter->identity->faculty->name ?? '-' }}</span>
                            </p>
                        </div>

                        <!-- Reviewers scoring grid -->
                        @foreach ($selectedProposal->reviewers as $index => $reviewer)
                            <div class="card border border-light shadow-sm mb-3">
                                <div class="card-header bg-light py-2">
                                    <h4 class="card-title fw-bold mb-0 text-primary">
                                        <i class="ti ti-user me-1"></i> Reviewer {{ $index + 1 }}: {{ $reviewer->user->name }}
                                    </h4>
                                    <div class="ms-auto small">
                                        Status: 
                                        @if ($reviewer->isCompleted())
                                            <span class="badge bg-success-lt font-weight-normal">Selesai</span>
                                        @else
                                            <span class="badge bg-warning-lt font-weight-normal">Dalam Proses</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table table-borderless">
                                        <thead>
                                            <tr class="text-muted small">
                                                <th class="ps-3">Kriteria Penilaian</th>
                                                <th class="text-center">Bobot</th>
                                                <th class="text-center">Skor (1-100)</th>
                                                <th class="text-center">Nilai Terbobot</th>
                                                <th class="pe-3">Catatan / Acuan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $calculatedTotal = 0; @endphp
                                            @forelse ($reviewer->scores as $score)
                                                @php
                                                    $weighted = ($score->score * $score->weight_snapshot) / 100;
                                                    $calculatedTotal += $weighted;
                                                @endphp
                                                <tr class="border-bottom-0">
                                                    <td class="ps-3 fw-medium">
                                                        {{ $score->criteria->criteria }}
                                                        <div class="small text-muted text-wrap font-weight-normal mt-0.5">{{ $score->criteria->description }}</div>
                                                    </td>
                                                    <td class="text-center">{{ $score->weight_snapshot }}%</td>
                                                    <td class="text-center font-monospace">{{ $score->score ?? '-' }}</td>
                                                    <td class="text-center font-monospace fw-bold text-dark">{{ number_format($weighted, 2) }}</td>
                                                    <td class="pe-3 text-muted small text-wrap" style="max-width: 200px;">
                                                        {{ $score->acuan ?: '-' }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center py-3 text-muted small">Belum ada skor kriteria diisi.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        @if ($reviewer->scores->count() > 0)
                                            <tfoot class="bg-light-lt fw-bold border-top">
                                                <tr>
                                                    <td class="ps-3">Total Nilai Akhir</td>
                                                    <td class="text-center">100%</td>
                                                    <td colspan="2" class="text-center font-monospace text-primary h3 mb-0">{{ number_format($calculatedTotal, 2) }}</td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        @endif
                                    </table>
                                </div>
                                <div class="card-footer bg-transparent py-2 border-top">
                                    <div class="small text-muted">
                                        <strong>Rekomendasi Reviewer:</strong> 
                                        <span class="text-dark fw-medium">{{ $reviewer->recommendation ?? '-' }}</span>
                                    </div>
                                    @if ($reviewer->review_notes)
                                        <div class="small text-muted mt-1">
                                            <strong>Catatan Reviewer:</strong> 
                                            <p class="mb-0 text-dark italic">{{ $reviewer->review_notes }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-5 text-muted">
                            <span class="spinner-border spinner-border-sm me-2" role="status"></span> Memuat data rincian...
                        </div>
                    @endif
                </div>
                <div class="modal-footer bg-light-lt">
                    <button type="button" class="btn btn-secondary shadow-sm" data-bs-dismiss="modal" wire:click="selectProposal(null)">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>
