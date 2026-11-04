@extends('components.layouts.app')

@php
    $pageTitle = 'Detail Proposal';
    $pageSubtitle = $proposal->title;
@endphp

@pushOnce('styles')
    <style>
        body.theme-modern #modern-root {
            min-height: calc(100vh - 200px);
        }
    </style>
@endpushOnce

@pushOnce('scripts')
    <script>
        window.__PROPOSAL_ID__ = '{{ $proposal->id }}';
        window.__USER_ID__ = '{{ auth()->id() }}';
    </script>
    @vite('resources/js/modern.jsx')
@endpushOnce