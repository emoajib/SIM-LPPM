@extends('components.layouts.app')

@php
    $pageTitle = 'Konfigurasi Notifikasi';
    $pageSubtitle = 'Kelola SMTP & WhatsApp untuk notifikasi email/WA (admin LPPM & super admin)';
@endphp

@pushOnce('scripts')
    <script>
        window.__MODERN_PAGE__ = 'notification-config-index';
        window.__USER_ROLES__ = @json(auth()->user()?->getRoleNames() ?? []);
        window.__ACTIVE_ROLE__ = '{{ session('active_role') ?? '' }}';
    </script>
@endpushOnce