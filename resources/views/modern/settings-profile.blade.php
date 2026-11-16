@extends('components.layouts.app')

@php
    $pageTitle = 'Profil Saya';
    $pageSubtitle = 'Kelola data profil dan identitas akademik Anda';
@endphp

@pushOnce('scripts')
    <script>
        window.__MODERN_PAGE__ = 'profile-index';
        window.__USER_ID__ = '{{ auth()->id() }}';
    </script>
@endpushOnce