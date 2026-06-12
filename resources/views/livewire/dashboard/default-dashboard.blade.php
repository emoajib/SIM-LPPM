<div>
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="align-items-center row g-2">
                <div class="col">
                    <h2 class="page-title">
                        Dashboard
                    </h2>
                    <div class="mt-1 text-muted">
                        Selamat datang, {{ auth()->user()->name }} ({{ $roleName }})
                    </div>
                </div>
                <div class="col-auto">
                    <div class="dropdown">
                        <a href="#" class="btn-outline-primary btn dropdown-toggle" data-bs-toggle="dropdown">
                            <svg xmlns="http://www.w3.org/2000/svg" class="me-2 icon" width="24" height="24"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z" />
                                <path d="M16 6v6l4 2" />
                            </svg>
                            Tahun: {{ $selectedYear }}
                        </a>
                        <div class="dropdown-menu">
                            @foreach ($availableYears as $year)
                                <a href="#" class="dropdown-item {{ $selectedYear == $year ? 'active' : '' }}"
                                    wire:click.preserve-scroll="$set('selectedYear', {{ $year }})">
                                    {{ $year }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-sm-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">Total Penelitian</div>
                            </div>
                            <div class="mb-3 h1">{{ $stats['total_research'] ?? 0 }}</div>
                            <div class="text-muted">Proposal Penelitian</div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">Total PKM</div>
                            </div>
                            <div class="mb-3 h1">{{ $stats['total_community_service'] ?? 0 }}</div>
                            <div class="text-muted">Proposal PKM</div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">Total Proposal</div>
                            </div>
                            <div class="mb-3 h1">
                                {{ ($stats['total_research'] ?? 0) + ($stats['total_community_service'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3 row row-cards">
                <!-- Penelitian Terbaru -->
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                        <div class="card-header bg-transparent border-0 py-3 d-flex align-items-center">
                            <div class="avatar bg-primary-lt text-primary shadow-sm avatar-sm me-3 border-0">
                                <i class="ti ti-flask-2"></i>
                            </div>
                            <h3 class="card-title fw-bold mb-0">Penelitian Terbaru</h3>
                        </div>
                        <div class="table-responsive">
                            {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
                            <table class="table table-vcenter card-table table-hover table-borderless">
                                <thead class="bg-transparent text-muted">
                                    <tr>
                                        <th class="ps-4">Judul & Peneliti</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-end pe-4">Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentResearch as $research)
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-wrap lh-base" title="{{ $research->title }}">
                                                    {{ $research->title }}
                                                </div>
                                                <div class="small text-muted d-flex align-items-center mt-1">
                                                    <div class="avatar avatar-xs me-2 border-0 shadow-sm bg-primary-lt">
                                                        {{ $research->submitter?->initials() }}
                                                    </div>
                                                    {{ $research->submitter?->name }}
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $research->status->color() }}-lt fw-bold px-2 py-1">
                                                    <span class="badge bg-{{ $research->status->color() }} me-1"></span>
                                                    {{ $research->status->label() }}
                                                </span>
                                            </td>
                                            <td class="text-end pe-4 text-muted small">
                                                {{ $research->updated_at->format('d/m/Y H:i') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="py-5 text-muted text-center">
                                                Belum ada penelitian
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- PKM Terbaru -->
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                        <div class="card-header bg-transparent border-0 py-3 d-flex align-items-center">
                            <div class="avatar bg-azure-lt text-azure shadow-sm avatar-sm me-3 border-0">
                                <i class="ti ti-users-group"></i>
                            </div>
                            <h3 class="card-title fw-bold mb-0">PKM Terbaru</h3>
                        </div>
                        <div class="table-responsive">
                            {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
                            <table class="table table-vcenter card-table table-hover table-borderless">
                                <thead class="bg-transparent text-muted">
                                    <tr>
                                        <th class="ps-4">Judul & Pengaju</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-end pe-4">Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentCommunityService as $communityService)
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-wrap lh-base" title="{{ $communityService->title }}">
                                                    {{ $communityService->title }}
                                                </div>
                                                <div class="small text-muted d-flex align-items-center mt-1">
                                                    <div class="avatar avatar-xs me-2 border-0 shadow-sm bg-azure-lt">
                                                        {{ $communityService->submitter?->initials() }}
                                                    </div>
                                                    {{ $communityService->submitter?->name }}
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $communityService->status->color() }}-lt fw-bold px-2 py-1">
                                                    <span class="badge bg-{{ $communityService->status->color() }} me-1"></span>
                                                    {{ $communityService->status->label() }}
                                                </span>
                                            </td>
                                            <td class="text-end pe-4 text-muted small">
                                                {{ $communityService->updated_at->format('d/m/Y H:i') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="py-5 text-muted text-center">
                                                Belum ada PKM
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
