@extends('components.layouts.app')

@php
    $pageTitle = 'Verifikasi Luaran IKU';
    $pageSubtitle = 'Pemantauan dan verifikasi target output penelitian & pengabdian';
@endphp


{{-- #modern-root dirender oleh layout saat modernPage terdeteksi --}}

@pushOnce('scripts')
    <script>
        window.__IS_MODERN_THEME__ = @json(is_modern_theme());
        window.__USER_ID__ = '{{ auth()->id() }}';
    </script>
@endpushOnce
