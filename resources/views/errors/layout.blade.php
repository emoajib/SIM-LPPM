<!DOCTYPE html>
<html lang="id">

    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
        <meta http-equiv="X-UA-Compatible" content="ie=edge" />
        <link rel="icon" href="{{ asset('logo.png') }}" type="image/png">
        <title>@yield('title') - {{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            @import url("https://rsms.me/inter/inter.css");
        </style>
    </head>

    <body class="d-flex flex-column border-primary border-top-wide">
        <div class="page page-center">
            <div class="py-4 container-tight">
                <div class="empty">
                    <div class="empty-header">@yield('code')</div>
                    <p class="empty-title">@yield('message')</p>
                    <p class="text-secondary empty-subtitle">
                        @yield('description')
                    </p>
                    <div class="empty-action">
                        <a href="{{ url('/') }}" class="btn btn-primary d-flex align-items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="icon icon-1">
                                <path d="M5 12h14" />
                                <path d="M5 12l6 6" />
                                <path d="M5 12l6 -6" />
                            </svg>
                            Kembali ke Beranda
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </body>

</html>
