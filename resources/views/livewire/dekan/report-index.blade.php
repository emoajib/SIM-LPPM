<x-slot:title>Persetujuan Laporan Akhir</x-slot:title>
<x-slot:pageTitle>Persetujuan Laporan Akhir @if($this->facultyName) - {{ $this->facultyName }} @endif</x-slot:pageTitle>
<x-slot:pageSubtitle>Tinjau dan setujui laporan akhir penelitian dan pengabdian dari fakultas
    Anda.</x-slot:pageSubtitle>

<div>
    <x-tabler.alert />

    <!-- Filter Section -->
    <div class="mb-3 row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" placeholder="Cari berdasarkan judul proposal..."
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
                            <select class="form-select" wire:model.live="statusFilter">
                                <option value="all">Semua Status</option>
                                <option value="submitted">Diajukan</option>
                                <option value="approved_by_dekan">Disetujui Dekan</option>
                                <option value="approved">Disetujui LPPM</option>
                                <option value="rejected">Ditolak</option>
                            </select>
                        </div>

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

    <!-- Reports Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="card-table table table-vcenter">
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
                                    <x-tabler.badge variant="outline" class="text-uppercase" style="font-size: 0.65rem;">
                                        Laporan Akhir
                                    </x-tabler.badge>
                                </div>
                            </td>
                            <td>
                                @if ($report->proposal->detailable_type === 'App\Models\Research')
                                    <x-tabler.badge color="blue" variant="light">Penelitian</x-tabler.badge>
                                @else
                                    <x-tabler.badge color="green" variant="light">Pengabdian</x-tabler.badge>
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
                                <x-tabler.badge :color="$statusColor" variant="light">
                                    {{ $report->status?->label() ?? '—' }}
                                </x-tabler.badge>
                            </td>
                            <td>
                                @php
                                    $showRoute = $report->proposal->detailable_type === 'App\Models\Research'
                                        ? route('research.final-report.show', $report->proposal)
                                        : route('community-service.final-report.show', $report->proposal);
                                @endphp
                                <a href="{{ $showRoute }}" class="btn btn-sm btn-primary" wire:navigate.hover>
                                    <x-lucide-eye class="icon me-1" />
                                    Tinjau
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center">
                                <div class="mb-3">
                                    <x-lucide-inbox class="text-secondary icon icon-lg" />
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