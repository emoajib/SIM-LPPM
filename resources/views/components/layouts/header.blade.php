<!-- BEGIN NAVBAR  -->
<div>
    <header class="navbar navbar-expand-md d-print-none">
        <div class="container-xl">
            <!-- BEGIN NAVBAR TOGGLER -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu"
                aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <!-- END NAVBAR TOGGLER -->
            <!-- BEGIN NAVBAR LOGO -->
            <div class="pe-0 pe-md-3 navbar-brand navbar-brand-autodark d-none-navbar-horizontal">
                @php
                    $appName = \App\Models\Setting::where('key', 'dashboard_name')->value('value') ?? config('app.name');
                @endphp
                <a href="/" aria-label="{{ $appName }}">
                    @php
                        // Check file physically exists before using custom logo (avoids 403 from stale media records)
                        $logoMedia = \App\Models\Setting::where('key', 'app_logo')->first()?->getFirstMedia('logo');
                        $customLogo = ($logoMedia && file_exists($logoMedia->getPath()))
                            ? route('media.download', $logoMedia)
                            : null;
                        $logoUrl = $customLogo ?: asset('logo.png');
                    @endphp
                    <img src="{{ $logoUrl }}?v={{ filemtime(public_path('logo.png')) }}" alt="" width="45" height="45" onerror="this.src='{{ asset('logo.png') }}'">
                    <span>{{ $appName }}</span>
                </a>
            </div>
            <!-- END NAVBAR LOGO -->
            <div class="flex-row order-md-last navbar-nav">
                <div class="d-md-flex d-none">
                    <div class="nav-item">
                        <a href="?theme=dark" class="px-0 nav-link hide-theme-dark" title="Enable dark mode"
                            data-bs-toggle="tooltip" data-bs-placement="bottom">
                            <!-- Download SVG icon from http://tabler.io/icons/icon/moon -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="icon icon-1">
                                <path
                                    d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z" />
                            </svg>
                        </a>
                        <a href="?theme=light" class="px-0 nav-link hide-theme-light" title="Enable light mode"
                            data-bs-toggle="tooltip" data-bs-placement="bottom">
                            <!-- Download SVG icon from http://tabler.io/icons/icon/sun -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="icon icon-1">
                                <path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                                <path
                                    d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7" />
                            </svg>
                        </a>
                    </div>

                    <!-- Role Selector -->
                    @php
                        $user = Auth::user();
                        $roles = $user?->getRoleNames() ?? collect();
                        $activeRole = active_role();
                        $activeIconPath = match ($activeRole) {
                            'superadmin' => 'M9 3L5 7l4 4 4-4-4-4-4 4z',
                            'admin lppm'
                                => 'M12 15V3m0 12l-4-4m4 4l4-4M5 21h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2z',
                            'kepala lppm'
                                => 'M12 15V3m0 12l-4-4m4 4l4-4M5 21h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2z',
                            'dosen'
                                => 'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4s-4 1.79-4 4s1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z',
                            'reviewer' => 'M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0a9 9 0 0 1 18 0z',
                            'rektor'
                                => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
                            'dekan' => 'M12 2L2 7l10 5 10-5l-10-5zM2 17l10 5 10-5M2 12l10 5 10-5',
                            default => 'M16 7a4 4 0 1 0-8 0a4 4 0 0 0 8 0zM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7z',
                        };
                    @endphp
                    @if ($roles->count() > 0)
                        <div class="nav-item dropdown">
                            <a href="#" class="d-flex align-items-center px-2 nav-link" data-bs-toggle="dropdown"
                                aria-label="Switch role" data-bs-auto-close="outside" aria-expanded="false">
                                @if ($activeRole)
                                    <div class="d-flex align-items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="me-1">
                                            <path d="{{ $activeIconPath }}" />
                                        </svg>
                                        <span
                                            class="bg-primary text-primary-fg badge-sm small badge">{{ format_role_name($activeRole) }}</span>
                                    </div>
                                @endif
                            </a>
                            @if ($roles->count() > 1)
                                <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end">
                                    <div class="dropdown-item-text">
                                        <div class="text-secondary small">Pilih Peran:</div>
                                    </div>
                                    @foreach ($roles as $role)
                                        @if ($role !== $activeRole)
                                            @php
                                                $iconPath = match ($role) {
                                                    'superadmin' => 'M9 3L5 7l4 4 4-4-4-4-4 4z',
                                                    'admin lppm'
                                                        => 'M12 15V3m0 12l-4-4m4 4l4-4M5 21h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2z',
                                                    'kepala lppm'
                                                        => 'M12 15V3m0 12l-4-4m4 4l4-4M5 21h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2z',
                                                    'dosen'
                                                        => 'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4s-4 1.79-4 4s1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z',
                                                    'reviewer' => 'M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0a9 9 0 0 1 18 0z',
                                                    'rektor'
                                                        => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
                                                    'dekan'
                                                        => 'M12 2L2 7l10 5 10-5l-10-5zM2 17l10 5 10-5M2 12l10 5 10-5',
                                                    default
                                                        => 'M16 7a4 4 0 1 0-8 0a4 4 0 0 0 8 0zM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7z',
                                                };
                                            @endphp
                                                <form method="POST" action="{{ route('role.switch') }}" class="d-inline" wire:key="role-{{ $role }}">
                                                 @csrf
                                                 <input type="hidden" name="role" value="{{ $role }}">
                                                 <button type="submit" class="w-100 text-start dropdown-item">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="d-flex align-items-center">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="18"
                                                                height="18" viewBox="0 0 24 24" fill="none"
                                                                stroke="currentColor" stroke-width="2"
                                                                stroke-linecap="round" stroke-linejoin="round"
                                                                class="me-2 text-muted">
                                                                <path d="{{ $iconPath }}" />
                                                            </svg>
                                                            <span>{{ format_role_name($role) }}</span>
                                                        </div>
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14"
                                                            height="14" viewBox="0 0 24 24" fill="none"
                                                            stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round"
                                                            class="text-muted">
                                                            <path d="M5 12h14M12 5l7 7-7 7" />
                                                        </svg>
                                                    </div>
                                                </button>
                                            </form>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end">
                                    <div class="text-muted dropdown-item-text small">
                                        Hanya memiliki satu peran
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    @livewire('notifications.notification-dropdown')
                </div>
                <div class="nav-item dropdown">
                    <a href="#" class="d-flex p-0 px-2 nav-link lh-1" data-bs-toggle="dropdown"
                        aria-label="Open user menu">
                        <span class="avatar avatar-sm"
                            style="background-image: url({{ auth()->user()->profile_picture }})">
                            @if (!auth()->user()->getFirstMedia('avatar') && !auth()->user()->identity?->profile_picture)
                                {{ auth()->user()->initials() }}
                            @endif
                        </span>
                        <div class="d-xl-block ps-2 d-none">
                            <div>{{ Auth::user()->name }}</div>
                            <div class="mt-1 text-secondary small">{{ Auth::user()->email }}</div>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                        {{-- <a href="#" class="dropdown-item">Status</a>
                        <a href="{{ route('settings.profile') }}" class="dropdown-item">Profile</a>
                        <a href="#" class="dropdown-item">Feedback</a>
                        <div class="dropdown-divider"></div> --}}
                        <a href="{{ route('settings') }}" class="dropdown-item">
                            @include('components.layouts.partials.menu.icon', [
                                'name' => 'settings',
                                'class' => 'icon icon-2 icon-inline me-1',
                            ])
                            Settings
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-100 text-start dropdown-item">
                                @include('components.layouts.partials.menu.icon', [
                                    'name' => 'logout',
                                    'class' => 'icon icon-2 icon-inline me-1',
                                ])
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <header class="navbar-expand-md">
        <div class="collapse navbar-collapse" id="navbar-menu">
            <div class="navbar">
                <div class="container-xl">
                    <div class="flex-column flex-fill flex-md-row align-items-center row">
                        <div class="col">
                            <!-- BEGIN NAVBAR MENU -->
                            @if (!empty($headerMenuItems))
                                <ul class="navbar-nav">
                                    @foreach ($headerMenuItems as $menuItem)
                                        @php
                                            $isDropdown = ($menuItem['type'] ?? 'link') === 'dropdown';
                                            $isActive = !empty($menuItem['active']);
                                        @endphp

                                        @if ($isDropdown)
                                            <li wire:key="menu-{{ $menuItem['title'] ?? $loop->index }}" class="nav-item dropdown{{ $isActive ? ' active' : '' }}">
                                                <a class="nav-link dropdown-toggle{{ $isActive ? ' active' : '' }}"
                                                    href="#" data-bs-toggle="dropdown"
                                                    data-bs-auto-close="{{ $menuItem['dropdown']['auto_close'] ?? 'outside' }}"
                                                    role="button" aria-expanded="false">
                                                    <span class="d-lg-inline-block nav-link-icon d-md-none">
                                                        @if (!empty($menuItem['icon']))
                                                            @include(
                                                                'components.layouts.partials.menu.icon',
                                                                [
                                                                    'name' => $menuItem['icon'],
                                                                    'class' => 'icon icon-1',
                                                                ]
                                                            )
                                                        @endif
                                                    </span>
                                                    <span class="nav-link-title"> {{ $menuItem['title'] }} </span>
                                                </a>
                                                <div class="dropdown-menu">
                                                    @include(
                                                        'components.layouts.partials.menu.dropdown-content',
                                                        [
                                                            'dropdown' => $menuItem['dropdown'] ?? [],
                                                        ]
                                                    )
                                                </div>
                                            </li>
                                        @else
                                            <li wire:key="menu-simple-{{ $menuItem['title'] ?? $loop->index }}" class="nav-item{{ $isActive ? ' active' : '' }}">
                                                <a class="nav-link{{ $isActive ? ' active' : '' }}"
                                                    href="{{ $menuItem['href'] ?? '#' }}"
                                                    @unless (($menuItem['navigate'] ?? true) === false) wire:navigate.hover @endunless>
                                                    <span class="d-lg-inline-block nav-link-icon d-md-none">
                                                        @if (!empty($menuItem['icon']))
                                                            @include(
                                                                'components.layouts.partials.menu.icon',
                                                                [
                                                                    'name' => $menuItem['icon'],
                                                                    'class' => 'icon icon-1',
                                                                ]
                                                            )
                                                        @endif
                                                    </span>
                                                    <span class="nav-link-title"> {{ $menuItem['title'] }} </span>
                                                </a>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            @endif
                            <!-- END NAVBAR MENU -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
</div>
<!-- END NAVBAR  -->