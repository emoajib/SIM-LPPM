<div>
    <x-slot:title>Notifikasi</x-slot:title>
    <x-slot:pageTitle>Daftar Notifikasi</x-slot:pageTitle>
    <x-slot:pageSubtitle>Kelola semua notifikasi Anda di sini.</x-slot:pageSubtitle>

    <x-tabler.alert />

    @if($pendingInvitations->count() > 0)
        <!-- Premium Invitation Alert -->
        <div class="card bg-warning-lt border-warning shadow-sm mb-4 overflow-hidden border-0 border-start border-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="avatar bg-warning text-warning-fg shadow-sm">
                            
                        </div>
                    </div>
                    <div class="col">
                        <h4 class="fw-bold mb-1">Undangan Kolaborasi Baru!</h4>
                        <div class="text-muted">
                            Anda memiliki {{ count($pendingInvitations) }} undangan anggota tim yang perlu dikonfirmasi.
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="list-group list-group-flush border rounded-3 bg-white bg-opacity-50">
                        @foreach($pendingInvitations as $invitation)
                            @php
                                $type = $invitation->detailable_type === 'App\Models\Research' ? 'Penelitian' : 'Pengabdian';
                                $route = $invitation->detailable_type === 'App\Models\Research' ? 'research.proposal.show' : 'community-service.proposal.show';
                                $variant = $invitation->detailable_type === 'App\Models\Research' ? 'primary' : 'azure';
                            @endphp
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <span class="badge bg-{{ $variant }}-lt mb-1">{{ $type }}</span>
                                    <div class="fw-bold text-dark">{{ Str::limit($invitation->title, 100) }}</div>
                                    <div class="small text-muted mt-1">
                                         Ketua: {{ $invitation->submitter->name }}
                                    </div>
                                </div>
                                <a href="{{ route($route, $invitation) }}" class="btn btn-primary btn-sm rounded-pill px-3"
                                    wire:navigate.hover>
                                    Konfirmasi 
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center justify-content-between">
                <h3 class="card-title">Notifikasi</h3>
                <div class="d-flex gap-2">
                    @if ($unreadCount > 0)
                        <button wire:click="markAllAsRead" class="btn-outline-primary btn btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="me-1">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0a9 9 0 0 1 18 0z" />
                            </svg>
                            Tandai Semua Dibaca
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Filter Select -->
            <div class="mb-3">
                <div class="align-items-center row">
                    <div class="col-auto">
                        <label class="mb-0 form-label">Filter Status:</label>
                    </div>
                    <div class="col-md-4">
                        <select wire:model.live="filter" class="form-select">
                            <option value="all">Semua Notifikasi</option>
                            <option value="unread">Belum Dibaca ({{ $unreadCount }})</option>
                            <option value="read">Sudah Dibaca</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Notifications List -->
            @if ($notifications->count() > 0)
                <div class="list-group list-group-flush list-group-hoverable">
                    @foreach ($notifications as $notification)
                        @php
                            $data = is_array($notification->data)
                                ? $notification->data
                                : json_decode($notification->data, true);
                            $isUnread = is_null($notification->read_at);
                            $icon = $this->getIconAttribute($notification->type);
                            $typeLabel = $this->getTypeLabelAttribute($notification->type);
                            $timeAgo = $notification->created_at->diffForHumans();
                        @endphp

                        <div
                            class="list-group-item list-group-item-action position-relative {{ $isUnread ? 'bg-azure-lt' : '' }}">
                            <div class="align-items-center row">
                                <div class="col-auto">
                                    <span class="avatar avatar-sm"
                                        style="background-color: {{ $isUnread ? '#3b82f6' : '#6b7280' }}20; color: {{ $isUnread ? '#3b82f6' : '#6b7280' }};">
                                        @switch($icon)
                                            @case('file-text')
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                    stroke-linecap="round" stroke-linejoin="round">
                                                    <path
                                                        d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z" />
                                                    <polyline points="14 2 14 8 20 8" />
                                                </svg>
                                            @break

                                            @case('check-circle')
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                    stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                                    <polyline points="22 4 12 14.01 9 11.01" />
                                                </svg>
                                            @break

                                            @case('user-check')
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                    stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                                    <circle cx="8.5" cy="7" r="4" />
                                                    <path d="M20 8v6M23 11l-3 3-3-3" />
                                                </svg>
                                            @break

                                            @case('check-square')
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                    stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="9 11 12 14 22 4" />
                                                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                                                </svg>
                                            @break

                                            @case('award')
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                    stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="8" r="7" />
                                                    <path d="M8.21 13.89L7 23l5-3 5 3-1.21-9.11" />
                                                </svg>
                                            @break

                                            @default
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                    stroke-linecap="round" stroke-linejoin="round">
                                                    <path
                                                        d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6" />
                                                    <path d="M9 17v1a3 3 0 0 0 6 0v-1" />
                                                </svg>
                                        @endswitch
                                    </span>
                                </div>

                                <div class="text-truncate col">
                                    <a href="{{ $data['link'] ?? '#' }}"
                                        class="text-body-emphasis text-decoration-none d-block fw-semibold stretched-link {{ $isUnread ? 'fw-bold' : '' }}">
                                        {{ $data['title'] ?? $typeLabel }}
                                    </a>
                                    <div
                                        class="d-block text-secondary text-truncate mt-n1 {{ $isUnread ? 'fw-semibold' : '' }}">
                                        {{ $data['message'] ?? '' }}
                                    </div>
                                    <div class="mt-1">
                                        <x-tabler.badge color="secondary" variant="light">
                                            {{ $typeLabel }}
                                        </x-tabler.badge>
                                        <span class="ms-2 text-secondary small">{{ $timeAgo }}</span>
                                    </div>
                                </div>

                                <div class="position-relative col-auto" style="z-index: 2;">
                                    @if ($isUnread)
                                        <button wire:click="markAsRead('{{ $notification->id }}')"
                                            class="btn-outline-primary btn btn-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M20 6L9 17l-5-5" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $notifications->links() }}
                </div>
            @else
                <div class="py-5 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="mb-3 text-muted">
                        <path
                            d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6" />
                        <path d="M9 17v1a3 3 0 0 0 6 0v-1" />
                    </svg>
                    <h3 class="text-muted">Tidak ada notifikasi</h3>
                    <p class="text-secondary">Belum ada notifikasi{{ $filter !== 'all' ? ' dengan filter ini' : '' }}.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
