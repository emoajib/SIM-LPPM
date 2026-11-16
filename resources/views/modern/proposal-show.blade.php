@extends('components.layouts.app')

@php
    $pageTitle = 'Detail Proposal';
    $pageSubtitle = $proposal->title;
@endphp


@pushOnce('scripts')
    <script>
        window.__PROPOSAL_ID__ = '{{ $proposal->id }}';
        window.__USER_ID__ = '{{ auth()->id() }}';
    </script>
@endpushOnce