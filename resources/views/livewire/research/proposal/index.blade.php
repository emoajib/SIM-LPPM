<x-slot:title>Penelitian</x-slot:title>
<x-slot:pageTitle>Daftar Penelitian</x-slot:pageTitle>
<x-slot:pageSubtitle>Kelola proposal penelitian Anda dengan fitur lengkap.</x-slot:pageSubtitle>

@php
    // Define schedule dates at top level so they're available in BOTH the slot AND main template
    // (Blade slots are compiled as separate closures - variables inside slots don't leak out)
    $startDate = \App\Models\Setting::where('key', 'research_proposal_start_date')->value('value');
    $endDate = \App\Models\Setting::where('key', 'research_proposal_end_date')->value('value');
    $isWithinSchedule = \App\Services\LecturerEligibilityService::isWithinSchedule($startDate, $endDate);
@endphp

<x-slot:pageActions>
    <div class="btn-list">

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

        // Build countdown timer data
        $timerData = null;
        if ($startDate && $endDate) {
            $endParsed = \App\Services\LecturerEligibilityService::parseScheduleDate($endDate, 'end');
            $startParsed = \App\Services\LecturerEligibilityService::parseScheduleDate($startDate, 'start');
            if ($endParsed && $startParsed) {
                $graceEnd = (clone $endParsed)->addMinutes(\App\Services\LecturerEligibilityService::GRACE_PERIOD_MINUTES);
                $timerData = [
                    'serverNow' => \Carbon\Carbon::now(\App\Services\LecturerEligibilityService::SCHEDULE_TIMEZONE)->toIso8601String(),
                    'startDate' => $startParsed->toIso8601String(),
                    'endDate' => $endParsed->toIso8601String(),
                    'graceEnd' => $graceEnd->toIso8601String(),
                    'type' => 'research',
                ];
            }
        }
    @endphp

    @if ($timerData)
        <div x-data="countdownTimer(@json($timerData))" x-cloak class="mb-4">
            <div class="alert border-start border-4 border-0 shadow-sm mb-0"
                :class="{
                    'alert-info': state === 'waiting',
                    'alert-success': state === 'active' && !isUrgent,
                    'alert-warning': state === 'active' && isUrgent && !isCritical,
                    'alert-danger': (state === 'active' && isCritical) || state === 'grace',
                    'alert-secondary': state === 'closed'
                }"
                x-show="state !== 'hidden'">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ti"
                            :class="{
                                'ti-clock': state === 'waiting',
                                'ti-clock-hour-4': state === 'active' && !isUrgent,
                                'ti-alert-triangle': state === 'active' && isUrgent,
                                'ti-clock-off': state === 'grace',
                                'ti-circle-x': state === 'closed'
                            }"></i>
                        <span class="fw-semibold">
                            <span x-show="state === 'waiting'">Pendaftaran dibuka dalam:</span>
                            <span x-show="state === 'active' && !isUrgent">Sisa waktu pengajuan:</span>
                            <span x-show="state === 'active' && isUrgent && !isCritical">Segera berakhir:</span>
                            <span x-show="state === 'active' && isCritical">⚠️ Waktu tersisa:</span>
                            <span x-show="state === 'grace'">⚠️ Masa tenggang berakhir dalam:</span>
                            <span x-show="state === 'closed'">Sistem telah ditutup</span>
                        </span>
                    </div>
                    <div class="h4 mb-0 fw-bold font-monospace" x-text="displayTime"
                        :class="{
                            'text-success': state === 'active' && !isUrgent,
                            'text-warning': state === 'active' && isUrgent && !isCritical,
                            'text-danger pulse-animation': (state === 'active' && isCritical) || state === 'grace',
                            'text-secondary': state === 'closed'
                        }">
                    </div>
                </div>
            </div>
        </div>
    @endif

    @php
        $user = auth()->user();
        $isKepala = $user->activeHasRole('kepala lppm');
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
                    <div class="text-secondary">
                        Halaman ini menampilkan seluruh usulan penelitian Anda. Anda dapat memantau status usulan,
                        mengedit draft, atau melihat detail usulan yang sedang dalam proses review.
                        Klik tombol <strong>Usulan Penelitian Baru</strong> untuk mulai membuat usulan jika jadwal
                        sedang dibuka.
                    </div>
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
