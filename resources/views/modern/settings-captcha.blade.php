@extends('components.layouts.app')

@php
    $pageTitle = 'Keamanan CAPTCHA';
    $pageSubtitle = 'Atur ketersediaan dan jenis suara audio CAPTCHA';
@endphp

@pushOnce('scripts')
    <script>
        window.__MODERN_PAGE__ = 'captcha-index';
    </script>
@endpushOnce
