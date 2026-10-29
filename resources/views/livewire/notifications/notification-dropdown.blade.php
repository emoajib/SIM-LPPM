<div>
    <a href="#" class="px-0 nav-link" data-bs-toggle="dropdown" tabindex="-1" aria-label="Show notifications"
        data-bs-auto-close="outside" aria-expanded="false">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
            <path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6" />
            <path d="M9 17v1a3 3 0 0 0 6 0v-1" />
        </svg>
        @if ($totalBadgeCount > 0)
            <x-tabler.badge color="red" variant="light" size="sm" class="badge-blink">
                {{ $totalBadgeCount }}
            </x-tabler.badge>
        @endif
    </a>
    <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Notifikasi Terbaru</h3>
                <div class="card-actions">
                    <a href="{{ route('notifications') }}" class="text-muted text-decoration-none">
                        Lihat semua
                    </a>
                </div>

                @if(isset($pendingInvitationsCount) && $pendingInvitationsCount > 0)
                    <div class="list-group-item bg-warning-lt">
                        <div class="align-items-center row">
                            <div class="col-auto">
                                
                            </div>
                            <div class="text-truncate col">
                                <a href="{{ route('notifications') }}"
                                    class="d-block text-warning fw-bold text-decoration-none">
                                    {{ $pendingInvitationsCount }} Undangan Kolaborasi Baru!
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($unreadNotifications->count() > 0)
                    <div class="list-group list-group-flush list-group-hoverable">
                        @foreach ($unreadNotifications as $notification)
                            @php
                                $data = is_array($notification->data)
                                    ? $notification->data
                                    : json_decode($notification->data, true);
                                $icon = $this->getIconAttribute($notification->type);
                                $timeAgo = $notification->created_at->diffForHumans();
                            @endphp

                            <div class="list-group-item">
                                <div class="align-items-center row">
                                    <div class="col-auto">
                                        <span class="d-block bg-primary status-dot status-dot-animated"></span>
                                    </div>
                                    <div class="text-truncate col">
                                        <a href="{{ $data['link'] ?? '#' }}" class="d-block text-body text-decoration-none">
                                            {{ $data['title'] ?? 'Notifikasi Baru' }}
                                        </a>
                                        <div class="d-block mt-n1 text-secondary text-truncate">
                                            {{ $data['message'] ?? '' }}
                                        </div>
                                        <div class="mt-1 text-muted small">
                                            {{ $timeAgo }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <button wire:click="markAsRead('{{ $notification->id }}')"
                                            class="btn btn-ghost-light btn-icon btn-sm" title="Tandai dibaca">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" class="text-muted">
                                                <path d="M18 6L6 18M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="text-center card-footer">
                        <div class="row g-2">
                            <div class="col-12">
                                <button wire:click="markAllAsRead" class="w-100 btn btn-ghost-primary">
                                    Tandai Semua Dibaca
                                </button>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="card-body">
                        <div class="empty">
                            <div class="text-muted empty-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path
                                        d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6" />
                                    <path d="M9 17v1a3 3 0 0 0 6 0v-1" />
                                </svg>
                            </div>
                            <p class="empty-title">Tidak ada notifikasi baru</p>
                            <p class="empty-subtitle">Semua pekerjaan Anda sudah up to date!</p>
                            <div class="empty-action">
                                <a href="{{ route('notifications') }}" class="btn-outline-primary btn">
                                    Semua Notifikasi
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>