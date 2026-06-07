@php
    $user = auth()->user();
    $eligibility = ['eligible' => true, 'reasons' => []];
    $scheduleInfo = ['research_open' => false, 'pkm_open' => false, 'research_schemes' => [], 'pkm_schemes' => []];
    $schemeEligible = true;
    $schemeReasons = [];
    $hasSubmittableProposals = false;
    $alertTitle = 'Status Kelayakan Pengajuan: Tidak Memenuhi Syarat';
    $alertSubtitle = '';
    $tindakan = '';

    // Hanya cek jika yang login adalah dosen
    if ($user && $user->activeHasRole('dosen')) {
        $eligibility = app(\App\Services\LecturerEligibilityService::class)->checkEligibility($user);
        $scheduleInfo = app(\App\Services\LecturerEligibilityService::class)->getScheduleStatus($user);

        // Check if user has submittable proposals (drafts, need assignment, revision needed)
        $submittableStatuses = [
            \App\Enums\ProposalStatus::DRAFT,
            \App\Enums\ProposalStatus::NEED_ASSIGNMENT,
            \App\Enums\ProposalStatus::REVISION_NEEDED,
        ];

        $hasSubmittableProposals = \App\Models\Proposal::where('submitter_id', $user->id)
            ->whereIn('status', $submittableStatuses)
            ->exists();

        // Cek eligibilitas skema
        $hasResearchSchemes = !empty($scheduleInfo['research_schemes']);
        $hasPkmSchemes = !empty($scheduleInfo['pkm_schemes']);

        if ($scheduleInfo['research_open'] && !$hasResearchSchemes) {
            $schemeEligible = false;
            $schemeReasons[] = 'Anda tidak memenuhi syarat untuk skema penelitian manapun.';
        }
        if ($scheduleInfo['pkm_open'] && !$hasPkmSchemes) {
            $schemeEligible = false;
            $schemeReasons[] = 'Anda tidak memenuhi syarat untuk skema pengabdian manapun.';
        }

        // Determine dynamic title and tindakan based on reason types
        $hasHistoricalObligations = false;
        $hasQuotaIssue = false;
        $hasSintaIssue = false;
        $hasFunctionalPositionIssue = false;

        foreach ($eligibility['reasons'] as $reason) {
            if (str_contains($reason, 'Laporan Akhir') || str_contains($reason, 'luaran wajib')) {
                $hasHistoricalObligations = true;
            }
            if (str_contains($reason, 'batas maksimal')) {
                $hasQuotaIssue = true;
            }
            if (str_contains($reason, 'SINTA')) {
                $hasSintaIssue = true;
            }
            if (str_contains($reason, 'Jabatan fungsional')) {
                $hasFunctionalPositionIssue = true;
            }
        }

        if ($hasHistoricalObligations) {
            $alertSubtitle = 'Sistem mendeteksi kewajiban yang belum terpenuhi dari periode sebelumnya'
                . ' (' . ucfirst($eligibility['period']['checked_semester']) . ' ' . $eligibility['period']['checked_year'] . '):';
            $tindakan = 'Penuhi laporan akhir dan komponen luaran wajib sebelum mengajukan proposal baru.';
        } elseif ($hasQuotaIssue) {
            $alertSubtitle = 'Anda telah mencapai batas maksimal pengajuan proposal sebagai Ketua:';
            $tindakan = 'Tunggu hingga periode berikutnya atau hubungi Admin LPPM untuk informasi lebih lanjut.';
        } elseif ($hasSintaIssue) {
            $alertSubtitle = 'Skor SINTA Anda belum memenuhi syarat minimal skema:';
            $tindakan = 'Tingkatkan skor SINTA Anda melalui publikasi ilmiah, lalu hubungi Admin LPPM.';
        } elseif ($hasFunctionalPositionIssue) {
            $alertSubtitle = 'Jabatan fungsional Anda belum memenuhi ketentuan skema:';
            $tindakan = 'Ajukan kenaikan jabatan fungsional melalui prosedur yang berlaku.';
        } else {
            $alertSubtitle = 'Terdapat kendala yang menghalangi pengajuan proposal:';
            $tindakan = 'Hubungi Admin LPPM untuk informasi lebih lanjut.';
        }
    }
@endphp

@if (!$eligibility['eligible'])
    <!-- Premium Eligibility Alert -->
    <div class="card bg-danger-lt border-danger shadow-sm mb-4 overflow-hidden border-0 border-start border-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="avatar bg-danger text-danger-fg shadow-sm">
                        <i class="ti ti-alert-triangle fs-2"></i>
                    </div>
                </div>
                <div class="col">
                    <h4 class="fw-bold text-danger mb-1">{{ $alertTitle }}</h4>
                    <div class="text-danger">
                        {{ $alertSubtitle }}
                        <ul class="mb-0 mt-2 p-0 ms-3" style="list-style-type: disc;">
                            @foreach ($eligibility['reasons'] as $reason)
                                <li wire:key="eligibility-reason-{{ $loop->index }}">{{ $reason }}</li>
                            @endforeach
                        </ul>
                        @if ($tindakan)
                            <div class="mt-2 small">
                                <strong>Tindakan:</strong> {{ $tindakan }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@elseif (!$schemeEligible && !$hasSubmittableProposals && !empty($eligibility['reasons']))
    <!-- Scheme Eligibility Alert with Specific Reasons -->
    <div class="card bg-warning-lt border-warning shadow-sm mb-4 overflow-hidden border-0 border-start border-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="avatar bg-warning text-warning-fg shadow-sm">
                        <i class="ti ti-school fs-2"></i>
                    </div>
                </div>
                <div class="col">
                    <h4 class="fw-bold text-warning mb-1">Status Eligibilitas Skema: Tidak Memenuhi Syarat</h4>
                    <div class="text-warning">
                        Meskipun jadwal pengajuan dibuka, profil akademik Anda belum memenuhi syarat untuk skema yang tersedia:
                        <ul class="mb-0 mt-2 p-0 ms-3" style="list-style-type: disc;">
                            @foreach ($eligibility['reasons'] as $reason)
                                <li wire:key="scheme-reason-{{ $loop->index }}">{{ $reason }}</li>
                            @endforeach
                        </ul>
                        @if ($tindakan)
                            <div class="mt-2 small">
                                <strong>Tindakan:</strong> {{ $tindakan }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@elseif (!$schemeEligible && !$hasSubmittableProposals)
    <!-- Scheme Eligibility Alert - No schemes at all -->
    <div class="card bg-warning-lt border-warning shadow-sm mb-4 overflow-hidden border-0 border-start border-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="avatar bg-warning text-warning-fg shadow-sm">
                        <i class="ti ti-school fs-2"></i>
                    </div>
                </div>
                <div class="col">
                    <h4 class="fw-bold text-warning mb-1">Status Eligibilitas Skema: Tidak Memenuhi Syarat</h4>
                    <div class="text-warning">
                        Tidak ada skema yang tersedia untuk diajukan saat ini. Silakan hubungi Admin LPPM untuk informasi lebih lanjut.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif