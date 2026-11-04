@extends('components.layouts.app')

@php
    $pageTitle = 'Verifikasi Luaran IKU';
    $pageSubtitle = 'Pemantauan dan verifikasi target output penelitian & pengabdian';
@endphp

@pushOnce('styles')
    <style>
        body.theme-modern #modern-root {
            min-height: calc(100vh - 200px);
        }
    </style>
@endpushOnce

@section('content')
    <div id="modern-root"></div>
@endsection

@pushOnce('scripts')
    @vite('resources/js/modern.jsx')
@endpushOnce
