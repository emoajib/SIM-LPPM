<x-slot:title>Penelitian</x-slot:title>
<x-slot:pageTitle>Daftar Penelitian</x-slot:pageTitle>
<x-slot:pageSubtitle>Kelola proposal penelitian Anda dengan fitur lengkap.</x-slot:pageSubtitle>
<x-slot:pageActions>
    <div class="btn-list">
        @php
            $startDate = \App\Models\Setting::where('key', 'research_proposal_start_date')->value('value');
            $endDate = \App\Models\Setting::where('key', 'research_proposal_end_date')->value('value');
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
            $scheduleInfo = ['research_open' => false, 'research_schemes' => []];
            if ($user && $user->activeHasRole('dosen')) {
                $svc = app(\App\Services\LecturerEligibilityService::class);
                $eligibility = $svc->checkEligibility($user, 'research');
                $scheduleInfo = $svc->getScheduleStatus($user);
            }
            $hasEligibleSchemes = !empty($scheduleInfo['research_schemes']);
        @endphp

        @if ($isWithinSchedule && auth()->user()->activeHasRole('dosen'))
            @php
                $canCreate = $eligibility['eligible'] && $hasEligibleSchemes && ($this->canCreateProposal['can_create'] ?? false);
                $isQuotaFull = $eligibility['eligible'] && $hasEligibleSchemes && !($this->canCreateProposal['can_create'] ?? false);
                
                $lockReason = '';
                if (!$hasEligibleSchemes) {
                    $lockReason = 'Anda tidak memenuhi syarat untuk skema penelitian manapun.';
                } elseif (!empty($eligibility['reasons'])) {
                    $lockReason = implode(' ', $eligibility['reasons']);
                }
            @endphp

            @if ($canCreate)
                <a href="{{ route('research.proposal.create') }}" wire:navigate.hover class="btn btn-primary">
                    <x-lucide-plus class="icon" />
                    Usulan Penelitian Baru
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
        <x-lecturer-eligibility-modal />
    </div>
</x-slot:pageActions>


<div>
    <x-tabler.alert />

    @php
        $user = auth()->user();
        $isKepala = $user->activeHasRole('kepala lppm');
        $isDosen = $user->activeHasRole('dosen');

        $researchEligibility = ['eligible' => true, 'reasons' => [], 'member_reasons' => []];
        $eligAlertTitle = '';
        $eligSubtitle = '';
        $eligTindakan = '';

        if ($isDosen) {
            $svc = app(\App\Services\LecturerEligibilityService::class);
            $researchEligibility = $svc->checkEligibility($user, 'research');
            $scheduleInfo = $svc->getScheduleStatus($user);

            $hasHistoricalObligations = false;
            $hasQuotaIssue = false;
            $hasSintaIssue = false;
            $hasFunctionalPositionIssue = false;

            foreach ($researchEligibility['reasons'] as $reason) {
                if (str_contains($reason, 'Laporan Akhir') || str_contains($reason, 'luaran wajib')) $hasHistoricalObligations = true;
                if (str_contains($reason, 'batas maksimal')) $hasQuotaIssue = true;
                if (str_contains($reason, 'SINTA')) $hasSintaIssue = true;
                if (str_contains($reason, 'Jabatan fungsional')) $hasFunctionalPositionIssue = true;
            }

            $eligAlertTitle = $researchEligibility['eligible'] ? 'Status Kelayakan: Memenuhi Syarat' : 'Status Kelayakan: Tidak Memenuhi Syarat';

            if ($hasHistoricalObligations) {
                $eligSubtitle = 'Sistem mendeteksi kewajiban yang belum terpenuhi dari periode sebelumnya'
                    . ' (' . ucfirst($researchEligibility['period']['checked_semester']) . ' ' . $researchEligibility['period']['checked_year'] . '):';
                $eligTindakan = 'Penuhi laporan akhir dan komponen luaran wajib sebelum mengajukan proposal baru.';
            } elseif ($hasQuotaIssue) {
                $eligSubtitle = 'Anda telah mencapai batas maksimal pengajuan proposal Penelitian sebagai Ketua:';
                $eligTindakan = 'Tunggu hingga periode berikutnya atau hubungi Admin LPPM untuk informasi lebih lanjut.';
            } elseif ($hasSintaIssue) {
                $eligSubtitle = 'Skor SINTA Anda belum memenuhi syarat minimal skema:';
                $eligTindakan = 'Tingkatkan skor SINTA Anda melalui publikasi ilmiah, lalu hubungi Admin LPPM.';
            } elseif ($hasFunctionalPositionIssue) {
                $eligSubtitle = 'Jabatan fungsional Anda belum memenuhi ketentuan skema:';
                $eligTindakan = 'Ajukan kenaikan jabatan fungsional melalui prosedur yang berlaku.';
            } elseif (! $researchEligibility['eligible']) {
                $eligSubtitle = 'Terdapat kendala yang menghalangi pengajuan proposal:';
                $eligTindakan = 'Hubungi Admin LPPM untuk informasi lebih lanjut.';
            }
        }
    @endphp

    <div class="collapse shadow-sm border-0 alert alert-info alert-dismissible fade show" id="researchIndexInfo"
        role="alert">
        <div class="d-flex">
            <div>
                <x-lucide-info class="me-2 alert-icon icon" />
            </div>
            <div>
                @if ($isKepala)
                    <h4 class="alert-title">Panduan Kepala LPPM (Daftar Penelitian)</h4>
                    <div class="text-secondary">
                        Halaman ini menampilkan seluruh usulan penelitian yang ada dalam sistem. Anda dapat memantau
                        distribusi status usulan secara makro dan melihat detail progres masing-masing penelitian.
                        Untuk memberikan keputusan persetujuan, silakan gunakan menu <strong>Persetujuan
                            Awal/Akhir</strong> di Navbar.
                    </div>
                @else
                    <h4 class="alert-title">Panduan Daftar Penelitian</h4>
                    <div class="text-secondary mb-2">
                        Halaman ini menampilkan seluruh usulan penelitian Anda. Anda dapat memantau status usulan,
                        mengedit draft, atau melihat detail usulan yang sedang dalam proses review.
                        Klik tombol <strong>Usulan Penelitian Baru</strong> untuk mulai membuat usulan jika jadwal
                        sedang dibuka.
                    </div>
                    @if ($isDosen)
                        <div class="border-top pt-2 mt-1">
                            <div class="fw-bold {{ $researchEligibility['eligible'] ? 'text-success' : 'text-danger' }} fs-sm mb-1">
                                <i class="ti ti-{{ $researchEligibility['eligible'] ? 'circle-check' : 'alert-triangle' }} me-1"></i>
                                {{ $eligAlertTitle }}
                            </div>
                            @if (! $researchEligibility['eligible'])
                                @if ($eligSubtitle)
                                    <div class="text-secondary small mb-1">{{ $eligSubtitle }}</div>
                                @endif
                                <ul class="mb-1 ps-3 small text-secondary" style="list-style-type: disc;">
                                    @foreach ($researchEligibility['reasons'] as $reason)
                                        <li wire:key="res-elig-reason-{{ $loop->index }}">{{ $reason }}</li>
                                    @endforeach
                                </ul>
                                @if ($eligTindakan)
                                    <div class="small text-secondary">
                                        <strong>Tindakan:</strong> {{ $eligTindakan }}
                                    </div>
                                @endif
                            @endif
                            @if (! empty($researchEligibility['member_reasons']))
                                <div class="border-top pt-1 mt-1 small text-secondary">
                                    <strong>Status Anggota:</strong>
                                    <ul class="mb-0 ps-3 mt-1" style="list-style-type: disc;">
                                        @foreach ($researchEligibility['member_reasons'] as $reason)
                                            <li wire:key="res-member-reason-{{ $loop->index }}">{{ $reason }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endif
                @endif
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-toggle="collapse" data-bs-target="#researchIndexInfo"
            aria-label="Close"></button>
    </div>

    <div class="mb-3">
        <button class="btn btn-ghost-info btn-sm" type="button" data-bs-toggle="collapse"
            data-bs-target="#researchIndexInfo" aria-expanded="false" aria-controls="researchIndexInfo">
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

    <div class="gap-3 mb-3 row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Search Input -->
                        <div class="col-md-4">
                            <input type="text" class="form-control"
                                placeholder="Cari berdasarkan judul atau ringkasan..."
                                wire:model.live.debounce.300ms="search" />
                        </div>

                        <!-- Status Filter -->
                        <div class="col-md-3">
                            <select class="form-select" wire:model.live="statusFilter">
                                <option value="all">Semua Status</option>
                                @foreach (\App\Enums\ProposalStatus::cases() as $status)
                                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
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

        <div class="col-12">
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
                                        <div>{{ $proposal->submitter->name }}</div>
                                        <small
                                            class="text-secondary">{{ $proposal->submitter->identity->identity_id }}</small>
                                    </td>
                                    <td>
                                        <x-tabler.badge :color="$proposal->status->color()" class="fw-normal">
                                            {{ $proposal->status->label() }}
                                        </x-tabler.badge>
                                        <div class="mt-1">
                                            <small class="text-secondary">
                                                {{ $proposal->created_at?->format('d M Y') }}
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex-nowrap btn-list">
                                            <a href="{{ route('research.proposal.show', $proposal) }}"
                                                class="btn btn-icon btn-ghost-primary" title="Lihat" wire:navigate.hover>
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
                                        <p class="text-secondary">Tidak ada data penelitian yang ditemukan.</p>
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

    </div>
</div>
