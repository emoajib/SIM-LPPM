<!DOCTYPE html>
<html lang="en">

<head>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <!-- Toast Container for notifications -->
    <x-tabler.toast-container />

    <!-- BEGIN GLOBAL THEME SCRIPT -->
    {{-- @vite(['resources/js/theme-config.js']) --}}
    <!-- END GLOBAL THEME SCRIPT -->
    <div class="page">
        @persist('header')
        @include('components.layouts.header')
        @endpersist
        <div class="page-wrapper">
            <!-- BEGIN PAGE HEADER -->
            @if (isset($pageTitle))
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
                    {{ $slot }}
                </div>
            </div>
            <!-- END PAGE BODY -->
            @include('components.layouts.footer')
        </div>
    </div>

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