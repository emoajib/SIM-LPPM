<x-slot:title>Persetujuan Laporan Akhir</x-slot:title>
<x-slot:pageTitle>Persetujuan Laporan Akhir @if($this->facultyName) - {{ $this->facultyName }} @endif</x-slot:pageTitle>
<x-slot:pageSubtitle>Tinjau dan setujui laporan akhir penelitian dan pengabdian dari fakultas
    Anda.</x-slot:pageSubtitle>

<div>
    <x-tabler.alert />

    <!-- Filter Section -->
    <div class="mb-3 row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-3">
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

                        <div class="col-md-2">
                            <select class="form-select" wire:model.live="statusFilter">
                                <option value="all">Semua Status</option>
                                <option value="submitted">Diajukan</option>
                                <option value="approved_by_dekan">Disetujui Dekan</option>
                                <option value="approved">Disetujui LPPM</option>
                                <option value="rejected">Ditolak</option>
                            </select>
                        </div>

                        <!-- Study Program Filter (Vetted by AI - Manual Review Required by Senior Engineer/Manager) -->
                        <div class="col-md-2">
                            <select class="form-select" wire:model.live="studyProgramFilter">
                                <option value="all">Semua Prodi</option>
                                @foreach ($this->availableStudyPrograms as $prodi)
                                    <option value="{{ $prodi->id }}">{{ $prodi->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Year Filter (Vetted by AI - Manual Review Required by Senior Engineer/Manager) -->
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
                                
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reports Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        <th>Judul Proposal</th>
                        <th>Jenis</th>
                        <th>Pengusul</th>
                        <th>Tgl Diajukan</th>
                        <th>Status</th>
                        <th class="w-1">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->reports as $report)
                        <tr wire:key="report-{{ $report->id }}">
                            <td class="text-wrap">
                                <div class="text-reset fw-bold">{{ $report->proposal->title }}</div>
                                <div class="mt-1">
                                    <span class="badge bg-secondary-lt text-uppercase" style="font-size: 0.75rem;">
                                        Laporan Akhir
                                    </span>
                                </div>
                            </td>
                            <td>
                                @if ($report->proposal->detailable_type === 'App\Models\Research')
                                    <span class="badge bg-blue-lt">Penelitian</span>
                                @else
                                    <span class="badge bg-green-lt">Pengabdian</span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $report->proposal->submitter->name }}</div>
                                <div class="small text-secondary">
                                    {{ $report->proposal->submitter->identity?->studyProgram?->name ?? '—' }}
                                </div>
                            </td>
                            <td>
                                <div class="text-secondary">
                                    {{ $report->submitted_at?->format('d M Y') ?? '—' }}
                                </div>
                            </td>
                            <td>
                                @php
                                    $statusColor = match($report->status?->value) {
                                        'submitted'        => 'info',
                                        'approved_by_dekan'=> 'primary',
                                        'approved'         => 'success',
                                        'rejected'         => 'danger',
                                        default            => 'secondary',
                                    };
                                @endphp
                                <span class="badge bg-{{ $statusColor }}-lt">
                                    {{ $report->status?->label() ?? '—' }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $showRoute = $report->proposal->detailable_type === 'App\Models\Research'
                                        ? route('research.final-report.show', $report->proposal)
                                        : route('community-service.final-report.show', $report->proposal);
                                @endphp
                                <a href="{{ $showRoute }}" class="btn btn-sm btn-primary" wire:navigate.hover>
                                    
                                    Tinjau
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center">
                                <div class="mb-3">
                                    
                                </div>
                                <p class="text-secondary">Tidak ada laporan akhir yang ditemukan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->reports->hasPages())
            <div class="d-flex align-items-center card-footer">
                {{ $this->reports->links() }}
            </div>
        @endif
    </div>
</div>