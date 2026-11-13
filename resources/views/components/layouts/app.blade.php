<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <link rel="icon" href="{{ asset('logo.png') }}" type="image/png">
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

<body class="{{ (session('sim_lppm_app_theme', request()->cookie('sim_lppm_app_theme', 'modern')) === 'modern') ? 'theme-modern' : 'theme-classic' }} {{ session('sim_lppm_color_mode') === 'dark' ? 'theme-dark' : '' }}">
    <!-- Toast Container for notifications -->
    <x-tabler.toast-container />

    <!-- BEGIN GLOBAL THEME SCRIPT -->
    @vite(['resources/js/theme-config.js'])
    <!-- END GLOBAL THEME SCRIPT -->
    @php
        $isModernTheme = session('sim_lppm_app_theme', request()->cookie('sim_lppm_app_theme', 'modern')) === 'modern';
    @endphp
    <div class="page">
        @include('components.layouts.header')
        <div class="page-wrapper">
            @php
                $modernPageMap = [
                    'research.proposal.index' => 'proposals-index',
                    'research.proposal-revision.index' => 'research-revision-index',
                    'research.final-report.index' => 'research-final-report-index',
                    'research.daily-note.index' => 'research-daily-note-index',
                    'community-service.proposal.index' => 'pengabdian-index',
                    'community-service.proposal-revision.index' => 'pengabdian-revision-index',
                    'community-service.final-report.index' => 'pengabdian-final-report-index',
                    'community-service.daily-note.index' => 'pengabdian-daily-note-index',
                    'research.verifikasi-luaran.page' => 'verifikasi-luaran-index',
                    'community-service.verifikasi-luaran.page' => 'verifikasi-luaran-index',
                    'research.daily-note.modern' => 'daily-note-index',
                    'research.proposal.show.modern' => 'proposal-show-index',
                ];
                $modernPage = $modernPageMap[request()->route()?->getName() ?? ''] ?? null;
                $modernPageEarly = $modernPage; // alias for header hiding
                $hideHeaderRoutes = [
                    'research.proposal-revision.index',
                    'research.final-report.index',
                    'research.daily-note.index',
                    'community-service.proposal-revision.index',
                    'community-service.final-report.index',
                    'community-service.daily-note.index',
                ];
                $hideHeaderModern = $isModernTheme && in_array(request()->route()?->getName() ?? '', $hideHeaderRoutes, true);
            @endphp
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
                <div class="container-xl">
                    @if ($isModernTheme)
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
    @if ($isModernTheme)
        @if ($modernPage)
            @viteReactRefresh
            @vite('resources/js/modern.jsx')
        @endif
    @endif

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