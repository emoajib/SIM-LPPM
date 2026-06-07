@props(['type' => null])
@php
    $user = auth()->user();
    $eligibility = ['eligible' => true, 'reasons' => [], 'schedule' => []];
    $userSintaScore = null;
    $userFunctionalPosition = null;
    $allResearchSchemeRequirements = [];
    $allPkmSchemeRequirements = [];

    if ($user && $user->activeHasRole('dosen')) {
        $eligibility = app(\App\Services\LecturerEligibilityService::class)->checkEligibility($user);

        $identity = $user->identity;
        $userSintaScore = $identity?->sinta_score_v3_overall ?? 0;
        $userFunctionalPosition = $identity?->functional_position ?? 'Tenaga Pengajar';

        if (!is_numeric($userSintaScore)) {
            $userSintaScore = 0;
        }

        $researchSchemesAll = \App\Models\ResearchScheme::all();
        foreach ($researchSchemesAll as $scheme) {
            $rules = $scheme->eligibility_rules ?? [];
            if (!empty($rules)) {
                $allResearchSchemeRequirements[] = [
                    'name' => $scheme->name,
                    'min_sinta' => $rules['min_sinta_score'] ?? null,
                    'min_scopus' => $rules['min_scopus_score'] ?? null,
                    'allowed_positions' => $rules['allowed_functional_positions'] ?? [],
                ];
            }
        }

        $pkmSchemesAll = \App\Models\CommunityServiceScheme::all();
        foreach ($pkmSchemesAll as $scheme) {
            $rules = $scheme->eligibility_rules ?? [];
            if (!empty($rules)) {
                $allPkmSchemeRequirements[] = [
                    'name' => $scheme->name,
                    'min_sinta' => $rules['min_sinta_score'] ?? null,
                    'min_scopus' => $rules['min_scopus_score'] ?? null,
                    'allowed_positions' => $rules['allowed_functional_positions'] ?? [],
                ];
            }
        }
    }

    $researchSchemes = $eligibility['schedule']['research_schemes'] ?? [];
    $pkmSchemes = $eligibility['schedule']['pkm_schemes'] ?? [];
    $researchOpen = $eligibility['schedule']['research_open'] ?? false;
    $pkmOpen = $eligibility['schedule']['pkm_open'] ?? false;
    $hasNoResearchSchemes = $researchOpen && empty($researchSchemes);
    $hasNoPkmSchemes = $pkmOpen && empty($pkmSchemes);

    // Categorize reasons
    $hasHistoricalObligations = false;
    $hasQuotaIssue = false;
    $hasSintaIssue = false;
    $hasFunctionalPositionIssue = false;
    $eligAlertTitle = $eligibility['eligible'] ? 'Status Kelayakan: Memenuhi Syarat' : 'Status Kelayakan: Tidak Memenuhi Syarat';
    $eligSubtitle = '';
    $eligTindakan = '';

    foreach ($eligibility['reasons'] as $reason) {
        if (str_contains($reason, 'Laporan Akhir') || str_contains($reason, 'luaran wajib')) $hasHistoricalObligations = true;
        if (str_contains($reason, 'batas maksimal')) $hasQuotaIssue = true;
        if (str_contains($reason, 'SINTA')) $hasSintaIssue = true;
        if (str_contains($reason, 'Jabatan fungsional')) $hasFunctionalPositionIssue = true;
    }

    if ($hasHistoricalObligations) {
        $eligSubtitle = 'Sistem mendeteksi kewajiban yang belum terpenuhi dari periode sebelumnya'
            . ' (' . ucfirst($eligibility['period']['checked_semester']) . ' ' . $eligibility['period']['checked_year'] . '):';
        $eligTindakan = 'Penuhi laporan akhir dan komponen luaran wajib sebelum mengajukan proposal baru.';
    } elseif ($hasQuotaIssue) {
        $eligSubtitle = 'Anda telah mencapai batas maksimal pengajuan proposal sebagai Ketua:';
        $eligTindakan = 'Tunggu hingga periode berikutnya atau hubungi Admin LPPM untuk informasi lebih lanjut.';
    } elseif ($hasSintaIssue) {
        $eligSubtitle = 'Skor SINTA Anda belum memenuhi syarat minimal skema:';
        $eligTindakan = 'Tingkatkan skor SINTA Anda melalui publikasi ilmiah, lalu hubungi Admin LPPM.';
    } elseif ($hasFunctionalPositionIssue) {
        $eligSubtitle = 'Jabatan fungsional Anda belum memenuhi ketentuan skema:';
        $eligTindakan = 'Ajukan kenaikan jabatan fungsional melalui prosedur yang berlaku.';
    } elseif (! $eligibility['eligible']) {
        $eligSubtitle = 'Terdapat kendala yang menghalangi pengajuan proposal:';
        $eligTindakan = 'Hubungi Admin LPPM untuk informasi lebih lanjut.';
    }
@endphp

@if ($user && $user->activeHasRole('dosen'))
    <div class="modal modal-blur fade" id="modal-eligibility-info" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center">
                        <x-lucide-info class="icon me-2" />
                        Info Eligibilitas
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light">
                    <!-- Status Kelayakan -->
                    <div class="accordion mb-3" id="accordion-eligibility">
                        <div class="accordion-item {{ $eligibility['eligible'] ? 'border-success' : 'border-danger' }} shadow-sm rounded">
                            <h2 class="accordion-header" id="heading-eligibility">
                                <button class="accordion-button {{ $eligibility['eligible'] ? 'text-success' : 'text-danger' }} fw-bold" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#collapse-eligibility" aria-expanded="true">
                                    <i class="ti ti-{{ $eligibility['eligible'] ? 'circle-check' : 'alert-triangle' }} me-2"></i>
                                    {{ $eligAlertTitle }}
                                </button>
                            </h2>
                            <div id="collapse-eligibility" class="accordion-collapse collapse show">
                                <div class="accordion-body bg-white py-3">
                                    @if (! $eligibility['eligible'])
                                        @if ($eligSubtitle)
                                            <div class="text-secondary small mb-1">{{ $eligSubtitle }}</div>
                                        @endif
                                        <ul class="mb-1 ps-3 small text-secondary" style="list-style-type: disc;">
                                            @foreach ($eligibility['reasons'] as $reason)
                                                <li wire:key="modal-reason-{{ $loop->index }}">{{ $reason }}</li>
                                            @endforeach
                                        </ul>
                                        @if ($eligTindakan)
                                            <div class="small text-secondary">
                                                <strong>Tindakan:</strong> {{ $eligTindakan }}
                                            </div>
                                        @endif
                                    @else
                                        <p class="mb-0 text-secondary">
                                            <i class="ti ti-check text-success me-1"></i>
                                            Anda memenuhi syarat untuk mengajukan proposal baru.
                                        </p>
                                    @endif
                                    @if (! empty($eligibility['member_reasons']))
                                        <div class="border-top pt-2 mt-2 small text-secondary">
                                            <strong>Status Anggota:</strong>
                                            <ul class="mb-0 ps-3 mt-1" style="list-style-type: disc;">
                                                @foreach ($eligibility['member_reasons'] as $reason)
                                                    <li wire:key="modal-member-reason-{{ $loop->index }}">{{ $reason }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Jadwal Pengajuan -->
                    <div class="accordion mb-3" id="accordion-schedule">
                        <div class="accordion-item border-primary shadow-sm rounded">
                            <h2 class="accordion-header" id="heading-schedule">
                                <button class="accordion-button text-primary fw-bold" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#collapse-schedule" aria-expanded="true">
                                    <i class="ti ti-calendar-event me-2"></i>
                                    Status Jadwal Pengajuan LPPM
                                </button>
                            </h2>
                            <div id="collapse-schedule" class="accordion-collapse collapse show">
                                <div class="accordion-body bg-white py-3">
                                    <div class="row g-3">
                                        <!-- Penelitian -->
                                        <div class="col-md-6">
                                            <div
                                                class="p-3 rounded-3 border {{ $eligibility['schedule']['research_open'] ? 'bg-blue-lt border-blue' : 'bg-secondary-lt border-secondary' }}">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div
                                                        class="avatar avatar-xs {{ $eligibility['schedule']['research_open'] ? 'bg-blue text-white' : 'bg-secondary text-white' }} rounded me-2">
                                                        <i class="ti ti-flask"></i>
                                                    </div>
                                                    <div
                                                        class="fw-bold {{ $eligibility['schedule']['research_open'] ? 'text-blue' : 'text-secondary' }}">
                                                        Penelitian</div>
                                                    <span
                                                        class="ms-auto badge {{ $eligibility['schedule']['research_open'] ? 'bg-blue' : 'bg-secondary' }} text-white">
                                                        {{ $eligibility['schedule']['research_open'] ? 'DIBUKA' : 'DITUTUP' }}
                                                    </span>
                                                </div>
                                                <div class="small text-muted mb-2">
                                                    @if($eligibility['schedule']['research_dates']['start'])
                                                        {{ $eligibility['schedule']['research_dates']['start'] ? \Carbon\Carbon::parse($eligibility['schedule']['research_dates']['start'])->format('d M') : '-' }}
                                                        -
                                                        {{ $eligibility['schedule']['research_dates']['end'] ? \Carbon\Carbon::parse($eligibility['schedule']['research_dates']['end'])->format('d M Y') : '-' }}
                                                    @else
                                                        Jadwal belum dikonfigurasi
                                                    @endif
                                                </div>
                                                @if($eligibility['schedule']['research_open'] && !empty($eligibility['schedule']['research_schemes']))
                                                    <div class="mt-2 pt-2 border-top border-blue">
                                                        <div class="fw-bold small text-blue mb-1">Skema Tersedia:</div>
                                                        <div class="d-flex flex-wrap gap-1">
                                                            @foreach($eligibility['schedule']['research_schemes'] as $scheme)
                                                                <span wire:key="research-scheme-{{ $loop->index }}" class="badge bg-blue-lt px-2 py-1"
                                                                    style="font-size: 10px;">{{ $scheme }}</span>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @elseif($eligibility['schedule']['research_open'] && empty($eligibility['schedule']['research_schemes']))
                                                    <div class="mt-2 pt-2 border-top border-warning">
                                                        <div class="text-warning small">
                                                            <i class="ti ti-alert-triangle me-1"></i>
                                                            Tidak ada skema yang memenuhi syarat
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <!-- PKM -->
                                        <div class="col-md-6">
                                            <div
                                                class="p-3 rounded-3 border {{ $eligibility['schedule']['pkm_open'] ? 'bg-green-lt border-green' : 'bg-secondary-lt border-secondary' }}">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div
                                                        class="avatar avatar-xs {{ $eligibility['schedule']['pkm_open'] ? 'bg-green text-white' : 'bg-secondary text-white' }} rounded me-2">
                                                        <i class="ti ti-users"></i>
                                                    </div>
                                                    <div
                                                        class="fw-bold {{ $eligibility['schedule']['pkm_open'] ? 'text-green' : 'text-secondary' }}">
                                                        Pengabdian</div>
                                                    <span
                                                        class="ms-auto badge {{ $eligibility['schedule']['pkm_open'] ? 'bg-green' : 'bg-secondary' }} text-white">
                                                        {{ $eligibility['schedule']['pkm_open'] ? 'DIBUKA' : 'DITUTUP' }}
                                                    </span>
                                                </div>
                                                <div class="small text-muted mb-2">
                                                    @if($eligibility['schedule']['pkm_dates']['start'])
                                                        {{ \Carbon\Carbon::parse($eligibility['schedule']['pkm_dates']['start'])->format('d M') }}
                                                        -
                                                        {{ \Carbon\Carbon::parse($eligibility['schedule']['pkm_dates']['end'])->format('d M Y') }}
                                                    @else
                                                        Jadwal belum dikonfigurasi
                                                    @endif
                                                </div>
                                                @if($eligibility['schedule']['pkm_open'] && !empty($eligibility['schedule']['pkm_schemes']))
                                                    <div class="mt-2 pt-2 border-top border-green">
                                                        <div class="fw-bold small text-green mb-1">Skema Tersedia:</div>
                                                        <div class="d-flex flex-wrap gap-1">
                                                            @foreach($eligibility['schedule']['pkm_schemes'] as $scheme)
                                                                <span wire:key="pkm-scheme-{{ $loop->index }}" class="badge bg-green-lt px-2 py-1"
                                                                    style="font-size: 10px;">{{ $scheme }}</span>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @elseif($eligibility['schedule']['pkm_open'] && empty($eligibility['schedule']['pkm_schemes']))
                                                    <div class="mt-2 pt-2 border-top border-warning">
                                                        <div class="text-warning small">
                                                            <i class="ti ti-alert-triangle me-1"></i>
                                                            Tidak ada skema yang memenuhi syarat
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Scheme Eligibility -->
                            <div class="accordion" id="accordion-scheme-eligible">
                                <div class="accordion-item bg-warning-lt border-warning mb-3 rounded shadow-sm">
                                    <h2 class="accordion-header" id="heading-scheme-eligible">
                                        <button class="accordion-button text-warning fw-bold bg-transparent" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#collapse-scheme-eligible"
                                            aria-expanded="true">
                                            <x-lucide-alert-triangle class="icon me-2" />
                                            Status Eligibilitas Skema
                                            <span class="badge bg-warning ms-auto text-white rounded-pill">Informasi</span>
                                        </button>
                                    </h2>
                                    <div id="collapse-scheme-eligible" class="accordion-collapse collapse show">
                                        <div class="accordion-body pt-0 bg-white rounded-bottom">
                                            <!-- User Current Profile -->
                                            <div class="p-3 bg-info-lt rounded mb-3">
                                                <div class="fw-bold text-info mb-2"><i class="ti ti-user me-1"></i> Profil
                                                    Anda:</div>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="small text-muted">Skor SINTA V3:</div>
                                                        <div class="fw-bold">{{ $userSintaScore ?? '0' }}</div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="small text-muted">Jabatan Fungsional:</div>
                                                        <div class="fw-bold">
                                                            {{ $userFunctionalPosition ?? 'Tenaga Pengajar' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <p class="mb-2 pt-1 text-info fw-bold">Persyaratan Skema Berdasarkan Profil Anda:</p>

                                            @if(!empty($allResearchSchemeRequirements))
                                                 <div class="mb-3">
                                                     <div class="fw-bold text-info mb-1">📚 Penelitian:</div>
                                                     @foreach($allResearchSchemeRequirements as $req)
                                                          <div wire:key="research-req-{{ $loop->index }}" class="small ms-2 mb-1">
                                                              <strong>{{ $req['name'] ?? 'Unknown' }}:</strong>
                                                             @php
                                                                 $issues = [];
                                                                 if (isset($req['min_sinta']) && $req['min_sinta'] !== null && is_numeric($userSintaScore) && $userSintaScore < $req['min_sinta']) {
                                                                     $issues[] = "SINTA minimal " . $req['min_sinta'] . " (anda: " . $userSintaScore . ")";
                                                                 }
                                                                 if (isset($req['allowed_positions']) && !empty($req['allowed_positions']) && !in_array($userFunctionalPosition, $req['allowed_positions'])) {
                                                                     $positions = is_array($req['allowed_positions']) ? implode(', ', $req['allowed_positions']) : $req['allowed_positions'];
                                                                     $issues[] = "Jabatan: " . $positions . " (anda: " . $userFunctionalPosition . ")";
                                                                 }
                                                                 $meetsReq = empty($issues);
                                                             @endphp
                                                             @if($meetsReq)
                                                                 <span class="text-success">✅ Memenuhi syarat</span>
                                                             @else
                                                                 <span class="text-danger">❌ {{ !empty($issues) ? implode(', ', $issues) : 'Tidak memenuhi syarat' }}</span>
                                                             @endif
                                                         </div>
                                                     @endforeach
                                                 </div>
                                            @endif
                                            @if(!empty($allPkmSchemeRequirements))
                                                 <div class="mb-3">
                                                     <div class="fw-bold text-info mb-1">🤝 Pengabdian Masyarakat:</div>
                                                      @foreach($allPkmSchemeRequirements as $req)
                                                          <div wire:key="pkm-req-{{ $loop->index }}" class="small ms-2 mb-1">
                                                              <strong>{{ $req['name'] ?? 'Unknown' }}:</strong>
                                                             @php
                                                                 $issues = [];
                                                                 if (isset($req['min_sinta']) && $req['min_sinta'] !== null && is_numeric($userSintaScore) && $userSintaScore < $req['min_sinta']) {
                                                                     $issues[] = "SINTA minimal " . $req['min_sinta'] . " (anda: " . $userSintaScore . ")";
                                                                 }
                                                                 if (isset($req['allowed_positions']) && !empty($req['allowed_positions']) && !in_array($userFunctionalPosition, $req['allowed_positions'])) {
                                                                     $positions = is_array($req['allowed_positions']) ? implode(', ', $req['allowed_positions']) : $req['allowed_positions'];
                                                                     $issues[] = "Jabatan: " . $positions . " (anda: " . $userFunctionalPosition . ")";
                                                                 }
                                                                 $meetsReq = empty($issues);
                                                             @endphp
                                                             @if($meetsReq)
                                                                 <span class="text-success">✅ Memenuhi syarat</span>
                                                             @else
                                                                 <span class="text-danger">❌ {{ !empty($issues) ? implode(', ', $issues) : 'Tidak memenuhi syarat' }}</span>
                                                             @endif
                                                         </div>
                                                     @endforeach
                                                 </div>
                                             @endif

                                            <p class="mt-2 mb-0 text-secondary small">
                                                <i class="ti ti-info-circle me-1"></i>
                                                Hubungi Admin LPPM jika ada ketidaksesuaian data.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif