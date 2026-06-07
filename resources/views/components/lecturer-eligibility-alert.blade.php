{{-- DEPRECATED: Eligibility info is now rendered inline in the info banners on index pages. --}}
{{-- Kept as a stub for reference; all logic moved to index blade @php blocks. --}}

@props(['type' => null])
@php
    $user = auth()->user();
    $eligibility = ['eligible' => true, 'reasons' => [], 'member_reasons' => []];
    if ($user && $user->activeHasRole('dosen')) {
        $service = app(\App\Services\LecturerEligibilityService::class);
        $eligibility = $service->checkEligibility($user, $type);
    }
@endphp

@if (!$eligibility['eligible'] || !empty($eligibility['member_reasons']))
    <div class="alert alert-info mb-4">
        <p>Status kelayakan telah dipindahkan ke banner Info Eligibilitas di halaman index masing-masing.</p>
    </div>
@endif