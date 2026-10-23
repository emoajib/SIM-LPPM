<div class="container-xl">
    <!-- Page title -->
    <div class="mb-5 page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    {{ __('Daftar Pengabdian Masyarakat') }}
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    @if ($this->canCreateProposal()['can_create'])
                        <a href="{{ route('community-service.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                            <x-lucide-plus class="icon" />
                            {{ __('Ajukan Pengabdian Baru') }}
                        </a>
                    @else
                        <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" title="{{ $this->quotaTooltip }}">
                            <button class="btn btn-primary d-none d-sm-inline-block" disabled>
                                <x-lucide-plus class="icon" />
                                {{ __('Ajukan Pengabdian Baru') }}
                            </button>
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Statistics -->
    <div class="mb-3 card">
        <div class="card-body">
            <div class="row g-3">
                <!-- Search -->
                <div class="col-md-3">
                    <input type="text" class="form-control"
                        placeholder="{{ __('Cari berdasarkan judul atau ringkasan...') }}"
                        wire:model.live.debounce.300ms="search" />
                </div>

                <!-- Status Filter -->
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="statusFilter">
                        <option value="all">{{ __('Semua Status') }}</option>
                        @foreach (\App\Enums\ProposalStatus::cases() as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Reset Button -->
                <div class="col-md-3">
                    <button type="button" class="btn-outline-secondary w-100 btn" wire:click="resetFilters">
                        <x-lucide-rotate-ccw class="icon" />
                        {{ __('Reset') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Stats -->
    <div class="mb-3 row row-deck row-cards">
        <div class="col-sm-6 col-lg-2">
            <div class="card">
                <div class="card-body">
                    <div class="text-truncate">
                        <h3 class="card-title">
                            {{ $statusStats['total'] }}
                        </h3>
                        <div class="text-secondary">{{ __('Total') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card">
                <div class="card-body">
                    <div class="text-truncate">
                        <h3 class="card-title">
                            {{ $statusStats['by_status']['draft'] ?? 0 }}
                        </h3>
                        <div class="text-secondary">{{ __('Draft') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card">
                <div class="card-body">
                    <div class="text-truncate">
                        <h3 class="card-title">
                            {{ $statusStats['by_status']['submitted'] ?? 0 }}
                        </h3>
                        <div class="text-secondary">{{ __('Diajukan') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card">
                <div class="card-body">
                    <div class="text-truncate">
                        <h3 class="card-title">
                            {{ $statusStats['by_status']['approved'] ?? 0 }}
                        </h3>
                        <div class="text-secondary">{{ __('Disetujui') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card">
                <div class="card-body">
                    <div class="text-truncate">
                        <h3 class="card-title">
                            {{ $statusStats['by_status']['rejected'] ?? 0 }}
                        </h3>
                        <div class="text-secondary">{{ __('Ditolak') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card">
                <div class="card-body">
                    <div class="text-truncate">
                        <h3 class="card-title">
                            {{ $statusStats['by_status']['completed'] ?? 0 }}
                        </h3>
                        <div class="text-secondary">{{ __('Selesai') }}</div>
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
                        <th>{{ __('Judul') }}</th>
                        <th>{{ __('Pelaksana') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Bidang Fokus') }}</th>
                        <th>{{ __('Tahun') }}</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->proposals as $proposal)
                        <tr>
                            <td>
                                <div class="font-weight-medium">{{ $proposal->title }}</div>
                                <div class="small text-secondary">{{ $proposal->researchScheme?->name }}</div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <span class="avatar avatar-xs rounded-circle">{{ substr($proposal->submitter->name, 0, 1) }}</span>
                                    </div>
                                    <div class="text-truncate" style="max-width: 150px;">
                                        {{ $proposal->submitter->name }}
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $proposal->status->color() }} text-{{ $proposal->status->color() }}-fg">
                                    {{ $proposal->status->label() }}
                                </span>
                            </td>
                            <td>
                                {{ $proposal->focusArea?->name }}
                            </td>
                            <td>
                                {{ $proposal->academic_year ?? $proposal->start_year }}
                            </td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <a href="{{ route('community-service.show', $proposal->id) }}" class="btn btn-white btn-sm">
                                        {{ __('Detail') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="empty">
                                    <div class="empty-icon">
                                        <x-lucide-search class="icon" />
                                    </div>
                                    <p class="empty-title">{{ __('Tidak ada data ditemukan') }}</p>
                                    <p class="empty-subtitle text-secondary">
                                        {{ __('Coba ubah filter atau kata kunci pencarian Anda.') }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($this->proposals->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $this->proposals->links() }}
            </div>
        @endif
    </div>
</div>
