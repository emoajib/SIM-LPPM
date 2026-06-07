<x-slot:title>Pengabdian</x-slot:title>
<x-slot:pageTitle>Daftar Pengabdian kepada Masyarakat</x-slot:pageTitle>
<x-slot:pageSubtitle>Kelola proposal pengabdian Anda dengan fitur lengkap.</x-slot:pageSubtitle>
<x-slot:pageActions>
    <div class="btn-list">
        @php
            $startDate = \App\Models\Setting::where('key', 'community_service_proposal_start_date')->value('value');
            $endDate = \App\Models\Setting::where('key', 'community_service_proposal_end_date')->value('value');
            $isWithinSchedule = false;
            if ($startDate && $endDate) {
                $now = now();
                $start = \Carbon\Carbon::parse($startDate)->startOfDay();
                $end = \Carbon\Carbon::parse($endDate)->endOfDay();
                $isWithinSchedule = $now->between($start, $end);
            }
        @endphp

        @php
            $user = auth()->user();
            $eligibility = ['eligible' => true, 'reasons' => []];
            $scheduleInfo = ['pkm_open' => false, 'pkm_schemes' => []];
            if ($user && $user->activeHasRole('dosen')) {
                $svc = app(\App\Services\LecturerEligibilityService::class);
                $eligibility = $svc->checkEligibility($user, 'pkm');
                $scheduleInfo = $svc->getScheduleStatus($user);
            }
            $hasEligibleSchemes = !empty($scheduleInfo['pkm_schemes']);
        @endphp

        @if ($isWithinSchedule && auth()->user()->activeHasRole('dosen'))
            @php
                $canCreate = $eligibility['eligible'] && $hasEligibleSchemes && ($this->canCreateProposal['can_create'] ?? false);
                $isQuotaFull = $eligibility['eligible'] && $hasEligibleSchemes && !($this->canCreateProposal['can_create'] ?? false);
                
                $lockReason = '';
                if (!$hasEligibleSchemes) {
                    $lockReason = 'Anda tidak memenuhi syarat untuk skema pengabdian manapun.';
                } elseif (!empty($eligibility['reasons'])) {
                    $lockReason = implode(' ', $eligibility['reasons']);
                }
            @endphp

            @if ($canCreate)
                <a href="{{ route('community-service.proposal.create') }}" wire:navigate.hover class="btn btn-primary">
                    <x-lucide-plus class="icon" />
                    Usulan Pengabdian Baru
                </a>
            @elseif ($isQuotaFull)
                <button type="button" class="btn btn-secondary" disabled title="{{ $this->quotaTooltip }}">
                    <x-lucide-plus class="icon" />
                    Kuota Terbatas
                </button>
            @else
                <button class="btn btn-secondary" disabled title="{{ $lockReason }}">
                    <x-lucide-lock class="icon" />
                    Usulan Dikunci
                </button>
            @endif
        @endif
        @if (auth()->user()->activeHasRole('dosen'))
            <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#modal-eligibility-info">
                <x-lucide-info class="icon" />
                Info Eligibilitas
            </button>
            <x-lecturer-eligibility-modal />
        @endif
    </div>
</x-slot:pageActions>

<div>
    <x-tabler.alert />

    @php
        $user = auth()->user();
        $isKepala = $user->activeHasRole('kepala lppm');
        $isDosen = $user->activeHasRole('dosen');

        $pkmEligibility = ['eligible' => true, 'reasons' => [], 'member_reasons' => []];
        $eligAlertTitle = '';
        $eligSubtitle = '';
        $eligTindakan = '';

        if ($isDosen) {
            $svc = app(\App\Services\LecturerEligibilityService::class);
            $pkmEligibility = $svc->checkEligibility($user, 'pkm');
            $scheduleInfo = $svc->getScheduleStatus($user);

            $hasHistoricalObligations = false;
            $hasQuotaIssue = false;
            $hasSintaIssue = false;
            $hasFunctionalPositionIssue = false;

            foreach ($pkmEligibility['reasons'] as $reason) {
                if (str_contains($reason, 'Laporan Akhir') || str_contains($reason, 'luaran wajib')) $hasHistoricalObligations = true;
                if (str_contains($reason, 'batas maksimal')) $hasQuotaIssue = true;
                if (str_contains($reason, 'SINTA')) $hasSintaIssue = true;
                if (str_contains($reason, 'Jabatan fungsional')) $hasFunctionalPositionIssue = true;
            }

            $eligAlertTitle = $pkmEligibility['eligible'] ? 'Status Kelayakan: Memenuhi Syarat' : 'Status Kelayakan: Tidak Memenuhi Syarat';

            if ($hasHistoricalObligations) {
                $eligSubtitle = 'Sistem mendeteksi kewajiban yang belum terpenuhi dari periode sebelumnya'
                    . ' (' . ucfirst($pkmEligibility['period']['checked_semester']) . ' ' . $pkmEligibility['period']['checked_year'] . '):';
                $eligTindakan = 'Penuhi laporan akhir dan komponen luaran wajib sebelum mengajukan proposal baru.';
            } elseif ($hasQuotaIssue) {
                $eligSubtitle = 'Anda telah mencapai batas maksimal pengajuan proposal Pengabdian sebagai Ketua:';
                $eligTindakan = 'Tunggu hingga periode berikutnya atau hubungi Admin LPPM untuk informasi lebih lanjut.';
            } elseif ($hasSintaIssue) {
                $eligSubtitle = 'Skor SINTA Anda belum memenuhi syarat minimal skema:';
                $eligTindakan = 'Tingkatkan skor SINTA Anda melalui publikasi ilmiah, lalu hubungi Admin LPPM.';
            } elseif ($hasFunctionalPositionIssue) {
                $eligSubtitle = 'Jabatan fungsional Anda belum memenuhi ketentuan skema:';
                $eligTindakan = 'Ajukan kenaikan jabatan fungsional melalui prosedur yang berlaku.';
            } elseif (! $pkmEligibility['eligible']) {
                $eligSubtitle = 'Terdapat kendala yang menghalangi pengajuan proposal:';
                $eligTindakan = 'Hubungi Admin LPPM untuk informasi lebih lanjut.';
            }

            // User profile for scheme requirements
            $identity = $user->identity;
            $userSintaScore = $identity?->sinta_score_v3_overall ?? 0;
            $userFunctionalPosition = $identity?->functional_position ?? 'Tenaga Pengajar';

            // Per-scheme requirements
            $schemeRequirements = [];
            $pkmSchemesAll = \App\Models\CommunityServiceScheme::all();
            foreach ($pkmSchemesAll as $scheme) {
                $rules = $scheme->eligibility_rules ?? [];
                $issues = [];
                if (isset($rules['min_sinta_score']) && $rules['min_sinta_score'] !== null && is_numeric($userSintaScore) && $userSintaScore < $rules['min_sinta_score']) {
                    $issues[] = 'SINTA minimal ' . $rules['min_sinta_score'] . ' (anda: ' . $userSintaScore . ')';
                }
                if (isset($rules['allowed_functional_positions']) && !empty($rules['allowed_functional_positions']) && !in_array($userFunctionalPosition, $rules['allowed_functional_positions'])) {
                    $positions = implode(', ', $rules['allowed_functional_positions']);
                    $issues[] = 'Jabatan: ' . $positions . ' (anda: ' . $userFunctionalPosition . ')';
                }
                $schemeRequirements[] = [
                    'name' => $scheme->name,
                    'meets' => empty($issues),
                    'issues' => $issues,
                ];
            }
        }
    @endphp

    <div class="collapse shadow-sm border-0 alert alert-info alert-dismissible fade show" id="pkmIndexInfo"
        role="alert">
        <div class="d-flex">
            <div>
                <x-lucide-info class="me-2 alert-icon icon" />
            </div>
            <div>
                @if ($isKepala)
                    <h4 class="alert-title">Panduan Kepala LPPM (Daftar PKM)</h4>
                    <div class="text-secondary">
                        Halaman ini menampilkan seluruh usulan pengabdian masyarakat (PKM) yang ada dalam sistem. Anda
                        dapat memantau
                        distribusi status usulan secara makro dan melihat detail progres masing-masing PKM.
                        Untuk memberikan keputusan persetujuan, silakan gunakan menu <strong>Persetujuan
                            Awal/Akhir</strong> di Navbar.
                    </div>
                @else
                    <h4 class="alert-title">Panduan Daftar Pengabdian (PKM)</h4>
                    <div class="text-secondary mb-2">
                        Halaman ini menampilkan seluruh usulan pengabdian masyarakat (PKM) Anda. Anda dapat memantau
                        status usulan,
                        mengedit draft, atau melihat detail usulan yang sedang dalam proses review.
                        Klik tombol <strong>Usulan Pengabdian Baru</strong> untuk mulai membuat usulan jika jadwal
                        sedang dibuka.
                    </div>
                    @if ($isDosen)
                        <div class="border-top pt-2 mt-1">
                            <div class="fw-bold {{ $pkmEligibility['eligible'] ? 'text-success' : 'text-danger' }} fs-sm mb-1">
                                <i class="ti ti-{{ $pkmEligibility['eligible'] ? 'circle-check' : 'alert-triangle' }} me-1"></i>
                                {{ $eligAlertTitle }}
                            </div>
                            @if (! $pkmEligibility['eligible'])
                                @if ($eligSubtitle)
                                    <div class="text-secondary small mb-1">{{ $eligSubtitle }}</div>
                                @endif
                                <ul class="mb-1 ps-3 small text-secondary" style="list-style-type: disc;">
                                    @foreach ($pkmEligibility['reasons'] as $reason)
                                        <li wire:key="pkm-elig-reason-{{ $loop->index }}">{{ $reason }}</li>
                                    @endforeach
                                </ul>
                                @if ($eligTindakan)
                                    <div class="small text-secondary">
                                        <strong>Tindakan:</strong> {{ $eligTindakan }}
                                    </div>
                                @endif
                            @endif
                            @if (! empty($pkmEligibility['member_reasons']))
                                <div class="border-top pt-1 mt-1 small text-secondary">
                                    <strong>Status Anggota:</strong>
                                    <ul class="mb-0 ps-3 mt-1" style="list-style-type: disc;">
                                        @foreach ($pkmEligibility['member_reasons'] as $reason)
                                            <li wire:key="pkm-member-reason-{{ $loop->index }}">{{ $reason }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            {{-- Schedule Status --}}
                            <div class="border-top pt-2 mt-2 small">
                                <div class="fw-bold text-info mb-1">
                                    <i class="ti ti-calendar-event me-1"></i> Jadwal Pengajuan Pengabdian
                                </div>
                                <div class="p-2 rounded border {{ $scheduleInfo['pkm_open'] ? 'bg-green-lt border-green' : 'bg-secondary-lt border-secondary' }}">
                                    <div class="d-flex align-items-center">
                                        <span class="fw-bold small">Pengabdian</span>
                                        <span class="ms-auto badge {{ $scheduleInfo['pkm_open'] ? 'bg-green' : 'bg-secondary' }} text-white">
                                            {{ $scheduleInfo['pkm_open'] ? 'DIBUKA' : 'DITUTUP' }}
                                        </span>
                                    </div>
                                    <div class="text-muted small">
                                        @if($scheduleInfo['pkm_dates']['start'])
                                            {{ \Carbon\Carbon::parse($scheduleInfo['pkm_dates']['start'])->format('d M') }}
                                            -
                                            {{ \Carbon\Carbon::parse($scheduleInfo['pkm_dates']['end'])->format('d M Y') }}
                                        @else
                                            Jadwal belum dikonfigurasi
                                        @endif
                                    </div>
                                    @if($scheduleInfo['pkm_open'] && !empty($scheduleInfo['pkm_schemes']))
                                        <div class="mt-1 pt-1">
                                            <div class="fw-bold small text-green">Skema Tersedia:</div>
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($scheduleInfo['pkm_schemes'] as $scheme)
                                                    <span class="badge bg-green-lt px-2 py-1" style="font-size: 10px;">{{ $scheme }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @elseif($scheduleInfo['pkm_open'] && empty($scheduleInfo['pkm_schemes']))
                                        <div class="mt-1 pt-1">
                                            <span class="text-warning small">
                                                <i class="ti ti-alert-triangle me-1"></i> Tidak ada skema yang memenuhi syarat
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Scheme Requirements --}}
                            <div class="border-top pt-2 mt-2 small">
                                <div class="fw-bold text-info mb-1">
                                    <i class="ti ti-clipboard-check me-1"></i> Persyaratan Skema
                                </div>
                                <div class="p-2 rounded bg-info-lt mb-2">
                                    <div class="text-muted">Skor SINTA V3:</div>
                                    <div class="fw-bold">{{ is_numeric($userSintaScore) ? $userSintaScore : '0' }}</div>
                                    <div class="text-muted mt-1">Jabatan Fungsional:</div>
                                    <div class="fw-bold">{{ $userFunctionalPosition }}</div>
                                </div>
                                <div class="fw-bold text-info mb-1" style="font-size: 11px;">🤝 Pengabdian Masyarakat:</div>
                                @forelse($schemeRequirements as $req)
                                    <div class="mb-1 ms-2" wire:key="pkm-scheme-req-{{ $loop->index }}">
                                        <strong>{{ $req['name'] }}:</strong>
                                        @if($req['meets'])
                                            <span class="text-success">✅ Memenuhi syarat</span>
                                        @else
                                            <span class="text-danger">❌ {{ implode(', ', $req['issues']) }}</span>
                                        @endif
                                    </div>
                                @empty
                                    <div class="ms-2 text-muted">Tidak ada skema pengabdian yang dikonfigurasi.</div>
                                @endforelse
                                <div class="mt-2 text-muted">
                                    <i class="ti ti-info-circle me-1"></i> Hubungi Admin LPPM jika ada ketidaksesuaian data.
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-toggle="collapse" data-bs-target="#pkmIndexInfo"
            aria-label="Close"></button>
    </div>

    <div class="mb-3">
        <button class="btn btn-ghost-info btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#pkmIndexInfo"
            aria-expanded="false" aria-controls="pkmIndexInfo">
            <x-lucide-info class="me-1 icon" />
            Bantuan Penggunaan
        </button>
    </div>

    <!-- Status Stats -->
    <div class="mb-3 row row-cards">
        <div class="col-sm-6 col-lg-2">
            <div class="shadow-sm border-0 card card-sm">
                <div class="card-body">
                    <div class="align-items-center row">
                        <div class="col-auto">
                            <span class="bg-primary text-white avatar">
                                <x-lucide-list class="icon" />
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">{{ $this->statusStats['total'] }}</div>
                            <div class="text-secondary small">Total</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="shadow-sm border-0 card card-sm">
                <div class="card-body">
                    <div class="align-items-center row">
                        <div class="col-auto">
                            <span class="bg-secondary text-white avatar">
                                <x-lucide-file-text class="icon" />
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">{{ $this->statusStats['by_status']['draft'] ?? 0 }}</div>
                            <div class="text-secondary small">Draft</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="shadow-sm border-0 card card-sm">
                <div class="card-body">
                    <div class="align-items-center row">
                        <div class="col-auto">
                            <span class="bg-info text-white avatar">
                                <x-lucide-send class="icon" />
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">{{ $this->statusStats['by_status']['submitted'] ?? 0 }}
                            </div>
                            <div class="text-secondary small">Diajukan</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="shadow-sm border-0 card card-sm">
                <div class="card-body">
                    <div class="align-items-center row">
                        <div class="col-auto">
                            <span class="bg-success text-white avatar">
                                <x-lucide-check-circle class="icon" />
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">{{ $this->statusStats['by_status']['approved'] ?? 0 }}</div>
                            <div class="text-secondary small">Disetujui</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="shadow-sm border-0 card card-sm">
                <div class="card-body">
                    <div class="align-items-center row">
                        <div class="col-auto">
                            <span class="bg-danger text-white avatar">
                                <x-lucide-x-circle class="icon" />
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">{{ $this->statusStats['by_status']['rejected'] ?? 0 }}</div>
                            <div class="text-secondary small">Ditolak</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="shadow-sm border-0 card card-sm">
                <div class="card-body">
                    <div class="align-items-center row">
                        <div class="col-auto">
                            <span class="bg-azure text-white avatar">
                                <x-lucide-award class="icon" />
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">{{ $this->statusStats['by_status']['completed'] ?? 0 }}
                            </div>
                            <div class="text-secondary small">Selesai</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Role-based Tabs (only for regular dosen users) -->
    @unless (auth()->user()->activeHasAnyRole(['admin lppm', 'kepala lppm', 'rektor']))
        <div class="mb-3">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if ($roleFilter === 'ketua') active @endif"
                        wire:click="$set('roleFilter', 'ketua')" role="tab"
                        aria-selected="@if ($roleFilter === 'ketua') true @else false @endif">
                        <x-lucide-crown class="me-2 icon" />
                        Sebagai Ketua
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if ($roleFilter === 'anggota') active @endif"
                        wire:click="$set('roleFilter', 'anggota')" role="tab"
                        aria-selected="@if ($roleFilter === 'anggota') true @else false @endif">
                        <x-lucide-users class="me-2 icon" />
                        Sebagai Anggota
                        @if($this->pendingInvitationsCount > 0)
                            <span class="badge bg-red ms-2">{{ $this->pendingInvitationsCount }}</span>
                        @endif
                    </button>
                </li>
            </ul>
        </div>
    @endunless

    <div class="mb-3 row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Search Input -->
                        <div class="col-md-6">
                            <input type="text" class="form-control"
                                placeholder="Cari berdasarkan judul atau ringkasan..."
                                wire:model.live.debounce.300ms="search" />
                        </div>

                        <!-- Status Filter -->
                        <div class="col-md-3">
                            <select class="form-select" wire:model.live="statusFilter">
                                @php
                                    $statusOptions = [
                                        'all' => 'Semua Status',
                                        'draft' => 'Draft',
                                        'submitted' => 'Diajukan',
                                        'need_assignment' => 'Perlu Persetujuan Anggota',
                                        'approved' => 'Disetujui Dekan',
                                        'waiting_reviewer' => 'Menunggu Penugasan Reviewer',
                                        'under_review' => 'Sedang Direview',
                                        'reviewed' => 'Review Selesai',
                                        'revision_needed' => 'Perlu Revisi',
                                        'completed' => 'Selesai',
                                        'rejected' => 'Ditolak',
                                    ];
                                @endphp
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Reset Button -->
                        <div class="col-md-3">
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
                        <th>Author</th>
                        <th>Status</th>
                        <th class="w-1">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->proposals as $proposal)
                        <tr wire:key="proposal-{{ $proposal->id }}">
                            <td style="max-width: 250px;">
                                <div class="text-reset fw-bold">{{ $proposal->title }}</div>
                                <div class="mt-1">
                                    <x-tabler.badge variant="outline" class="text-uppercase" style="font-size: 0.65rem;">
                                        {{ $proposal->focusArea?->name ?? '—' }}
                                    </x-tabler.badge>
                                </div>
                            </td>
                            <td>
                                <div>{{ $proposal->submitter->name }}</div>
                                <small class="text-secondary">{{ $proposal->submitter->email }}</small>
                            </td>
                            <td>
                                <x-tabler.badge :color="$proposal->status->color()" class="fw-normal">
                                    {{ $proposal->status->label() }}
                                </x-tabler.badge>
                                <div class="mt-1">
                                    <small class="text-secondary">
                                        {{ $proposal->created_at->format('d M Y') }}
                                    </small>
                                </div>
                            </td>
                            <td>
                                <div class="flex-nowrap btn-list">
                                    <a href="{{ route('community-service.proposal.show', $proposal) }}"
                                        class="btn btn-icon btn-ghost-primary" wire:navigate.hover title="Lihat">
                                        <x-lucide-eye class="icon" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center">
                                <div class="mb-3">
                                    <x-lucide-inbox class="text-secondary icon icon-lg" />
                                </div>
                                <p class="text-secondary">Tidak ada data pengabdian yang ditemukan.</p>
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