@extends('components.layouts.app')

@php
    $pageTitle = 'Pengaturan';
    $pageSubtitle = 'Kelola akun, sistem, dan master data aplikasi';
@endphp

@pushOnce('scripts')
    <script>
        window.__MODERN_PAGE__ = 'settings-index';
    </script>
@endpushOnce
