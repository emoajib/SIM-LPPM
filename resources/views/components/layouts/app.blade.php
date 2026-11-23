<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="theme-color" content="#1e3a6e" />
    <link rel="icon" href="{{ asset('logo.png') }}" type="image/png">
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>{{ $title ?? '' }} - {{ config('app.name') }}</title>
    <!-- BEGIN GLOBAL MANDATORY STYLES -->
    <!-- END GLOBAL MANDATORY STYLES -->
    <!-- END PLUGINS STYLES -->
    <!-- BEGIN DEMO STYLES -->
    {{--
    <link href="/preview/css/demo.css" rel="stylesheet" /> --}}
    <!-- END DEMO STYLES -->
    <!-- BEGIN CUSTOM FONT -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- END CUSTOM FONT -->
</head>

@php
    $colorMode = session('sim_lppm_color_mode', request()->cookie('sim_lppm_color_mode'));
@endphp
<body class="{{ is_modern_theme() ? 'theme-modern' : 'theme-classic' }} {{ $colorMode === 'dark' ? 'theme-dark' : '' }}">
    <!-- Toast Container for notifications -->
    <x-tabler.toast-container />

    <!-- BEGIN GLOBAL THEME SCRIPT -->
    @vite(['resources/js/theme-config.js'])
    <!-- END GLOBAL THEME SCRIPT -->
    @php
        $isModernTheme = is_modern_theme();
        $modernPageMap = config('modern-pages.pages', []);
        $modernPage = $modernPageMap[request()->route()?->getName() ?? ''] ?? null;
        $modernPageEarly = $modernPage; // alias for header hiding
        $hideHeaderRoutes = config('modern-pages.hide_header_routes', []);
        $hideHeaderModern = $isModernTheme && in_array(request()->route()?->getName() ?? '', $hideHeaderRoutes, true);
        // Verifikasi Luaran is rendered with React in BOTH themes so the classic
        // and modern experiences stay aligned (seragam) without duplicated UI.
        $forceReactRoutes = ['research.verifikasi-luaran.page', 'community-service.verifikasi-luaran.page'];
        $forceReact = in_array(request()->route()?->getName() ?? '', $forceReactRoutes, true);
    @endphp
    <a href="#main-content" class="skip-link">Lewati ke konten utama</a>
    <div class="page">
        @include('components.layouts.header')
        <div class="page-wrapper">
            <!-- BEGIN PAGE HEADER -->
            @if (isset($pageTitle) && !$modernPageEarly && !$hideHeaderModern)
                <x-page-header :title="$pageTitle" :subtitle="$pageSubtitle ?? null">
                    {{ $pageHeader ?? '' }}
                    @isset($pageActions)
                        <x-slot:actions>
                            {{ $pageActions }}
                        </x-slot:actions>
                    @endisset
                </x-page-header>
            @endif
            <!-- END PAGE HEADER -->
            <!-- BEGIN PAGE BODY -->
            <div class="page-body">
                <div class="container-xl" id="main-content">
                    @if ($isModernTheme || $forceReact)
                        <script>window.__MODERN_PAGE__ = @json($modernPage);</script>
                        <script>window.__USER_ROLES__ = @json(auth()->user()?->getRoleNames() ?? []);</script>
                        <script>window.__ACTIVE_ROLE__ = @json(session('active_role', ''));</script>
                        @if ($modernPage)
                            {{-- Modern theme: React (migrasi) --}}
                            <div id="modern-root"></div>
                            {{-- Fallback Blade content jika React gagal mount --}}
                            <noscript>{{ $slot ?? "" }}</noscript>
                        @else
                            {{-- Modern theme: Blade content (hybrid) — tanpa banner React --}}
                            {{ $slot ?? "" }}
                        @endif
                    @else
                        {{ $slot ?? "" }}
                    @endif
                </div>
            </div>
            <!-- END PAGE BODY -->
            @include('components.layouts.footer')
        </div>
    </div>
    @if ($isModernTheme || $forceReact)
        @if ($modernPage)
            @viteReactRefresh
            @vite('resources/js/modern.jsx')
        @endif
    @endif

    <noscript><p style="padding:1rem;">Beberapa fitur memerlukan JavaScript untuk berjalan.</p></noscript>
    @stack('scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('download-file', (event) => {
                const url = event[0]?.url || event.url;
                if (url) {
                    window.location.href = url;
                }
            });

            Livewire.on('preview-pdf', (event) => {
                const url = event[0]?.url || event.url;
                if (url) {
                    window.open(url, '_blank');
                }
            });

            Livewire.on('swal', (event) => {
                const data = Array.isArray(event) ? event[0] : event;
                Swal.fire({
                    title: data.title || 'Notification',
                    text: data.text || '',
                    icon: data.icon || 'info',
                    confirmButtonText: 'OK',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    buttonsStyling: false
                });
            });
        });
    </script>

    {{-- @include('components.layouts.app.settings') --}}
    <!-- BEGIN GLOBAL MANDATORY SCRIPTS -->
    {{--
    <script src="/dist/js/tabler.min.js?{{ str()->random(5) }}" data-navigate-track></script> --}}
    {{-- @vite(['resources/js/app.js']) --}}
    <!-- END GLOBAL MANDATORY SCRIPTS -->
    <!-- BEGIN DEMO SCRIPTS -->
    {{--
    <script src="/dist/js/demo.js" defer></script> --}}
    <!-- END DEMO SCRIPTS -->
    <!-- BEGIN PAGE SCRIPTS -->
    <!-- END PAGE SCRIPTS -->
</body>

</html>