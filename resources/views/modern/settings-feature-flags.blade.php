@extends('components.layouts.app')

@php
    $pageTitle = 'Feature Flags';
    $pageSubtitle = 'Aktifkan atau nonaktifkan modul dan fitur opsional';
@endphp

@pushOnce('scripts')
    <script>
        window.__MODERN_PAGE__ = 'feature-flags-index';
    </script>
@endpushOnce
