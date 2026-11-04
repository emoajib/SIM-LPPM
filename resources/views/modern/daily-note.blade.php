@extends('components.layouts.app')

@php
    $pageTitle = 'Realisasi Anggaran';
    $pageSubtitle = 'Catatan harian aktivitas dan pengeluaran dana kegiatan';
@endphp

@pushOnce('styles')
    <style>
        body.theme-modern #modern-root {
            min-height: calc(100vh - 200px);
        }
    </style>
@endpushOnce

@section('content')
    <div id="modern-root"
         data-proposal-id="{{ $proposal->id }}"
         data-can-manage="{{ $canManage ? 'true' : 'false' }}"
         data-is-editable="{{ $isEditable ? 'true' : 'false' }}"
         data-user-id="{{ auth()->id() }}"></div>
@endsection

@pushOnce('scripts')
    <script>
        window.__PROPOSAL_ID__ = '{{ $proposal->id }}';
        window.__CAN_MANAGE__ = {{ $canManage ? 'true' : 'false' }};
        window.__IS_EDITABLE__ = {{ $isEditable ? 'true' : 'false' }};
        window.__USER_ID__ = '{{ auth()->id() }}';
    </script>
    @vite('resources/js/modern.jsx')
@endpushOnce
