@extends('components.layouts.app')

@php
    $pageTitle = 'Verifikasi Luaran IKU';
    $pageSubtitle = 'Pemantauan dan verifikasi target output penelitian & pengabdian';
@endphp


{{-- #modern-root dirender oleh layout saat modernPage terdeteksi --}}

@pushOnce('scripts')
    <script>
        window.__USER_ID__ = '{{ auth()->id() }}';
    </script>
@endpushOnce
