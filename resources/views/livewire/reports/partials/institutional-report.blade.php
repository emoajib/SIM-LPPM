<div class="mt-3">
    <!-- Filter Bar (Support System) -->
    <div class="card mb-3 shadow-sm border-0 glass-card">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <i class="ti ti-search text-primary"></i>
                        </span>
                        <input type="text" wire:model.live.debounce.500ms="search" class="form-control"
                            placeholder="Cari judul atau nama pengusul...">
                    </div>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="selectedScheme" class="form-select">
                        <option value="all">Semua Skema</option>
                        @foreach($allSchemes as $scheme)
                            <option value="{{ $scheme->id }}">{{ $scheme->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="selectedFaculty" class="form-select" @if(active_role_is('dekan'))
                    disabled @endif>
                        @if(active_role() !== 'dekan')
                            <option value="all">Semua Fakultas</option>
                        @endif
                        @foreach($allFaculties as $faculty)
                            <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="selectedSemester" class="form-select">
                        <option value="all">Semua Semester</option>
                        <option value="ganjil">Ganjil</option>
                        <option value="genap">Genap</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="period" class="form-select">
                        @foreach($periods as $p)
                            <option value="{{ $p }}">Periode {{ $p }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto ms-auto">
                    <button class="btn btn-icon btn-white shadow-sm" wire:click="resetFilters" title="Reset Filter">
                        <i class="ti ti-refresh text-secondary"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @if(active_role_is('kepala lppm') || active_role_is('rektor'))
        <div class="card mb-3 border-primary shadow-sm glass-card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="d-flex align-items-center mb-1">
                        <h3 class="card-title h3 mb-0 me-2 text-primary">{{ $config['validasiTitle'] }}</h3>
                        @if($institutionalReport)
                            <span class="badge bg-{{ $institutionalReport->status->color() }}-lt">
                                {{ $institutionalReport->status->label() }}
                            </span>
                        @else
                            <span class="badge bg-secondary-lt">Belum Diajukan</span>
                        @endif
                    </div>
                    <p class="text-secondary mb-0 small">
                        @if(!$institutionalReport || $institutionalReport->status === \App\Enums\InstitutionalReportStatus::DRAFT)
                            {{ $config['rekapitulasiText'] }} periode {{ $period }} belum diajukan ke Rektor.
                        @elseif($institutionalReport->status === \App\Enums\InstitutionalReportStatus::SUBMITTED)
                            Menunggu persetujuan dan tanda tangan digital Rektor.
                        @elseif($institutionalReport->status === \App\Enums\InstitutionalReportStatus::APPROVED)
                            Telah disahkan Rektor pada {{ $institutionalReport->approved_at->format('d M Y H:i') }}.
                        @elseif($institutionalReport->status === \App\Enums\InstitutionalReportStatus::REJECTED)
                            Perbaikan: <strong>{{ $institutionalReport->notes }}</strong>
                        @endif
                    </p>
                </div>
                <div class="btn-list">
                    @php
                        $currentFilters = [
                            'search' => $search,
                            'period' => $period,
                            'semester' => $selectedSemester,
                            'scheme' => $selectedScheme,
                            'faculty' => $selectedFaculty
                        ];
                    @endphp

                    <!-- Draft Preview Icon (Support System) -->
                    <a href="{{ route($config['pdfRoute'], array_merge($currentFilters, ['preview' => 1])) }}"
                        target="_blank" class="btn btn-outline-primary shadow-sm" title="Tinjau Draft PDF">
                        <i class="ti ti-eye me-2"></i> Tinjau PDF
                    </a>

                    @if(active_role_is('kepala lppm') && (!$institutionalReport || in_array($institutionalReport->status, [\App\Enums\InstitutionalReportStatus::DRAFT, \App\Enums\InstitutionalReportStatus::REJECTED])))
                        <button class="btn btn-primary shadow-sm"
                            wire:click="submitInstitutionalReport('{{ $config['reportType'] }}', {{ $period }}, {{ json_encode($currentFilters) }})"
                            wire:loading.attr="disabled">
                            <i class="ti ti-send me-2"></i>
                            Ajukan ke Rektor
                        </button>
                    @endif

                    @if(active_role_is('rektor') && ($institutionalReport?->status === \App\Enums\InstitutionalReportStatus::SUBMITTED))
                        <button class="btn btn-outline-danger shadow-sm" data-bs-toggle="modal"
                            data-bs-target="#modal-reject-institutional">
                            <i class="ti ti-x me-2"></i>
                            Minta Perbaikan
                        </button>
                        <button class="btn btn-success shadow-sm" wire:click="approveInstitutionalReport('{{ $config['reportType'] }}', {{ $period }})"
                            wire:loading.attr="disabled">
                            <i class="ti ti-circle-check me-2"></i>
                            Setujui & Tanda Tangani
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

<div class="mb-4 row row-deck">
    @foreach ($summary as $card)
        <div class="col-sm-6 col-xl-4">
            <div class="card card-stacked shadow-sm border-0">
                <div class="d-flex align-items-center gap-3 card-body">
                    <div class="p-2.5 bg-opacity-10 {{ str_replace('bg-', 'bg-opacity-10 text-', explode(' ', $card['variant'])[0]) }} rounded-3 d-flex align-items-center justify-content-center"
                        style="width: 56px; height: 56px;">
                        {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
                        <i class="ti ti-{{ $card['icon'] }} fs-2"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium mb-1">{{ $card['label'] }}</div>
                        <h2 class="mb-0 fw-bold">{{ $card['value'] }}</h2>
                    </div>
                </div>
                <div class="progress progress-xs card-progress">
                    <div class="progress-bar {{ explode(' ', $card['variant'])[0] }}" style="width: 100%"></div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row row-cards mb-3">
    <!-- Distribution by Scheme -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ $config['skemaTitle'] }}</h3>
            </div>
            <div class="p-0 card-body overflow-auto" style="max-height: 300px;">
                <table class="card-table table table-vcenter">
                    <thead>
                        <tr>
                            <th>{{ __('Skema') }}</th>
                            <th class="text-center">{{ __('Jumlah') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($schemes as $scheme)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $scheme['name'] }}</div>
                                    <div class="text-muted small">Rp {{ number_format($scheme['budget'], 0, ',', '.') }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-blue-lt">{{ $scheme['count'] }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="py-4 text-center text-muted">
                                    {{ __('Tidak ada data skema') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Distribution by Focus Area -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ $config['fokusTitle'] }}</h3>
            </div>
            <div class="p-0 card-body overflow-auto" style="max-height: 300px;">
                <table class="card-table table table-vcenter">
                    <thead>
                        <tr>
                            <th>{{ __('Bidang Fokus') }}</th>
                            <th class="text-center">{{ __('Prosentase') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalItems = $schemes->sum('count'); @endphp
                        @forelse ($focusAreas as $area)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $area['name'] }}</div>
                                    <div class="text-muted small">{{ $area['count'] }} {{ $config['unitLabel'] }}</div>
                                </td>
                                <td class="text-center">
                                    @php $percent = $totalItems > 0 ? round(($area['count'] / $totalItems) * 100) : 0; @endphp
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress progress-xs flex-fill" style="min-width: 50px;">
                                            <div class="progress-bar bg-primary" style="width: {{ $percent }}%"></div>
                                        </div>
                                        <span class="small text-muted">{{ $percent }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="py-4 text-center text-muted">
                                    {{ __('Tidak ada data bidang fokus') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Productivity by Faculty -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ $config['produktivitasTitle'] }}</h3>
            </div>
            <div class="p-0 card-body overflow-auto" style="max-height: 300px;">
                <table class="card-table table table-vcenter">
                    <thead>
                        <tr>
                            <th>{{ __('Fakultas') }}</th>
                            <th class="text-center">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($faculties as $faculty)
                            <tr>
                                <td>
                                    <div class="fw-semibold text-truncate" style="max-width: 180px;">
                                        {{ $faculty['name'] }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-green-lt">{{ $faculty['count'] }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="py-4 text-center text-muted">
                                    {{ __('Tidak ada data fakultas') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row row-cards mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ $config['analitikTitle'] }} — {{ $period }}</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @forelse ($outputStats as $stat)
                        <div class="col-sm-6 col-md-4 col-lg-2">
                            <div class="card card-sm">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="font-weight-medium">
                                                {{ $stat['category'] }}
                                            </div>
                                            <div class="text-secondary small">
                                                {{ $stat['count'] }} {{ __('Total') }}
                                            </div>
                                            <div class="mt-1">
                                                <span class="text-success fw-bold">{{ $stat['published'] }}</span>
                                                <span class="text-muted small"> {{ __('Terbit') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-4 text-muted">
                            {{ __('Belum ada luaran yang dilaporkan pada periode ini.') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row row-cards">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ $config['daftarTitle'] }} — {{ $period }}</h3>
            </div>
            <div class="p-0 card-body">
                <div class="table-responsive">
                    <table class="card-table table table-vcenter text-nowrap">
                        <thead>
                            <tr>
                                <th class="w-1">No</th>
                                <th>{{ __('Judul & Ketua') }}</th>
                                <th>{{ __('Fakultas / Prodi') }}</th>
                                <th>{{ __('Skema & Status') }}</th>
                                <th>{{ __('Anggaran (Rp)') }}</th>
                                <th class="w-1 text-center">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($proposals as $index => $proposal)
                                <tr>
                                    <td>{{ ($proposals->currentPage() - 1) * $proposals->perPage() + $index + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-sm avatar-rounded me-2"
                                                style="background-image: url({{ $proposal->submitter->profile_picture ?: '' }})">
                                                @if(!$proposal->submitter->profile_picture)
                                                    {{ strtoupper(mb_substr($proposal->submitter->name, 0, 1)) }}
                                                @endif
                                            </span>
                                            <div class="flex-fill">
                                                <div class="font-weight-medium text-wrap" style="max-width: 400px;"
                                                    title="{{ $proposal->title }}">
                                                    <a href="{{ route($config['detailRoute'], $proposal) }}"
                                                        class="text-reset" wire:navigate.hover>
                                                        {{ $proposal->title }}
                                                    </a>
                                                </div>
                                                <div class="text-muted small">
                                                    {{ $proposal->submitter->name }}
                                                    ({{ $proposal->submitter->identity?->nidn ?? '-' }})
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small fw-bold">
                                            {{ $proposal->submitter->identity?->faculty->name ?? '-' }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $proposal->submitter->identity?->studyProgram->name ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-muted small mb-1">
                                            {{ $proposal->{$config['schemeRelation']}->name ?? '-' }}
                                        </div>
                                        <x-tabler.badge :color="$proposal->status->color()">
                                            {{ $proposal->status->label() }}
                                        </x-tabler.badge>
                                    </td>
                                    <td class="text-end">
                                        <div class="fw-bold">
                                            @php
                                                $dana = ($proposal->sbk_value && $proposal->sbk_value > 0)
                                                    ? $proposal->sbk_value
                                                    : ($proposal->budgetItems->sum('total_price') ?? 0);
                                            @endphp
                                            {{ number_format($dana, 0, ',', '.') }}
                                        </div>
                                    </td>
                                    <td>
                                        <a href="{{ route($config['detailRoute'], $proposal) }}"
                                            class="btn btn-sm btn-icon btn-outline-info" title="Lihat Detail Detail"
                                            wire:navigate.hover>
                                            <x-lucide-eye class="icon" />
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-5 text-center text-muted">
                                        {{ $config['emptyListText'] }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($proposals->hasPages())
                <div class="card-footer d-flex align-items-center">
                    {{ $proposals->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@if(active_role_is('rektor'))
    <div class="modal modal-blur fade" id="modal-reject-institutional" tabindex="-1" role="dialog" aria-hidden="true"
        wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Alasan Penolakan / Permintaan Perbaikan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div>
                        <label class="form-label">Catatan untuk Kepala LPPM</label>
                        <textarea class="form-control" wire:model="approvalNotes" rows="3"
                            placeholder="Masukkan alasan atau instruksi perbaikan..."></textarea>
                        @error('approvalNotes') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary me-auto"
                        data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger shadow-sm"
                        wire:click="rejectInstitutionalReport('{{ $config['reportType'] }}', '{{ $period }}')">
                        Simpan & Tolak
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
