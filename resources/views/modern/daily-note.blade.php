@extends('components.layouts.app')

@php
    $pageTitle = 'Realisasi Anggaran';
    $pageSubtitle = 'Catatan harian aktivitas dan pengeluaran dana kegiatan';
@endphp


@pushOnce('scripts')
    <script>
        window.__PROPOSAL_ID__ = '{{ $proposal->id }}';
        window.__CAN_MANAGE__ = {{ $canManage ? 'true' : 'false' }};
        window.__IS_EDITABLE__ = {{ $isEditable ? 'true' : 'false' }};
        window.__USER_ID__ = '{{ auth()->id() }}';
    </script>
@endpushOnce