<!doctype html>
<!--
* Tabler - Premium and Open Source dashboard template with responsive and high quality UI.
* @version 1.4.0
* @link https://tabler.io
* Copyright 2018-2025 The Tabler Authors
* Copyright 2018-2025 codecalm.net Paweł Kuna
* Licensed under MIT (https://github.com/tabler/tabler/blob/master/LICENSE)
-->
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <link rel="icon" href="{{ asset('logo.png') }}" type="image/png">
    <title>{{ $title ?? 'Sign in' }}</title>
    
    <link rel="preconnect" href="https://challenges.cloudflare.com">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- END PLUGINS STYLES -->
    <!-- BEGIN DEMO STYLES -->
    {{-- <link href="./preview/css/demo.css" rel="stylesheet" /> --}}
    <!-- END DEMO STYLES -->

    {{-- Cloudflareturnstile --}}
    @if(config('turnstile.site_key'))
        <script
            src="https://challenges.cloudflare.com/turnstile/v0/api.js"
            async
            defer
        ></script>
    @endif
</head>

<body>
    {{ $slot }}
</body>

</html>
