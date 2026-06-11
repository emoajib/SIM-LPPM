{{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
@php
    $appName = \App\Models\Setting::where('key', 'dashboard_name')->value('value') ?? config('app.name');
    $logoMedia = \App\Models\Setting::where('key', 'app_logo')->first()?->getFirstMedia('logo');
    $customLogo = ($logoMedia && file_exists($logoMedia->getPath())) 
        ? route('media.download', $logoMedia) 
        : null;
    $logoUrl = $customLogo ?: asset('logo.png');
@endphp

<!-- TOPBAR BIMA -->
<div class="topbar-bima d-print-none">
    <a class="logo" href="/">
        <div class="logo-icon">SIM<br>LPPM</div>
        <span>{{ $appName }}</span>
    </a>
    
    <div class="topbar-right">
        <!-- Theme Toggle -->
        <div class="d-flex align-items-center me-2">
            <a href="?theme=dark" class="px-2 nav-link hide-theme-dark text-muted" title="Enable dark mode" data-bs-toggle="tooltip" data-bs-placement="bottom">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icon-tabler-moon">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z" />
                </svg>
            </a>
            <a href="?theme=light" class="px-2 nav-link hide-theme-light text-muted" title="Enable light mode" data-bs-toggle="tooltip" data-bs-placement="bottom">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icon-tabler-sun">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                    <path d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7" />
                </svg>
            </a>
        </div>

        <!-- Notifications Dropdown -->
        <div class="me-2 d-flex align-items-center">
            @livewire('notifications.notification-dropdown')
        </div>

        <!-- Role Selector -->
        @php
            $user = Auth::user();
            $roles = $user?->getRoleNames() ?? collect();
            $activeRole = active_role();
        @endphp
        @if ($roles->count() > 0)
            <div class="dropdown">
                <button class="btn-role dropdown-toggle d-flex align-items-center gap-1" data-bs-toggle="dropdown" aria-expanded="false">
                    👤 {{ strtoupper(format_role_name($activeRole)) }}
                </button>
                @if ($roles->count() > 1)
                    <div class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <div class="dropdown-header text-secondary small py-2 px-3">Pilih Peran:</div>
                        @foreach ($roles as $role)
                            @if ($role !== $activeRole)
                                <form method="POST" action="{{ route('role.switch') }}" class="d-inline" wire:key="role-switch-{{ $role }}">
                                    @csrf
                                    <input type="hidden" name="role" value="{{ $role }}">
                                    <button type="submit" class="dropdown-item py-2 px-3 w-100 text-start">
                                        {{ format_role_name($role) }}
                                    </button>
                                </form>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <!-- User Profile Dropdown -->
        <div class="dropdown">
            <span class="topbar-user dropdown-toggle" style="cursor: pointer;" data-bs-toggle="dropdown">
                🔔 &nbsp; {{ strtoupper(Auth::user()->name) }} ▾
            </span>
            <div class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <a href="{{ route('settings') }}" class="dropdown-item">
                    @include('components.layouts.partials.menu.icon', [
                        'name' => 'settings',
                        'class' => 'icon icon-2 icon-inline me-1',
                    ])
                    Settings
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-100 text-start dropdown-item text-danger">
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

<!-- NAVBAR BIMA -->
<div class="navbar-bima d-print-none">
    <div class="container-xl d-flex gap-2 flex-wrap align-items-center">
        @if (!empty($headerMenuItems))
            @foreach ($headerMenuItems as $menuItem)
                @php
                    $isDropdown = ($menuItem['type'] ?? 'link') === 'dropdown';
                    $isActive = !empty($menuItem['active']);
                @endphp

                @if ($isDropdown)
                    <div class="dropdown py-1" wire:key="menu-bima-{{ $menuItem['title'] ?? $loop->index }}">
                        <a class="nav-item-bima dropdown-toggle {{ $isActive ? 'active' : '' }}" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                            @if (!empty($menuItem['icon']))
                                @include('components.layouts.partials.menu.icon', [
                                    'name' => $menuItem['icon'],
                                    'class' => 'icon icon-2 me-1',
                                ])
                            @endif
                            {{ $menuItem['title'] }} ▾
                        </a>
                        <div class="dropdown-menu shadow-sm border-0">
                            @include('components.layouts.partials.menu.dropdown-content', [
                                'dropdown' => $menuItem['dropdown'] ?? [],
                            ])
                        </div>
                    </div>
                @else
                    <a class="nav-item-bima py-2 {{ $isActive ? 'active' : '' }}" href="{{ $menuItem['href'] ?? '#' }}" 
                       @unless (($menuItem['navigate'] ?? true) === false) wire:navigate.hover @endunless 
                       wire:key="menu-bima-simple-{{ $menuItem['title'] ?? $loop->index }}">
                        @if (!empty($menuItem['icon']))
                            @include('components.layouts.partials.menu.icon', [
                                'name' => $menuItem['icon'],
                                'class' => 'icon icon-2 me-1',
                            ])
                        @endif
                        {{ $menuItem['title'] }}
                    </a>
                @endif
            @endforeach
        @endif
    </div>
</div>
