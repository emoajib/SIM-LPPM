@php
    $type = $type ?? 'research';
    $mode = $mode ?? 'detail';
    $showBasic = $mode !== 'revision';
    $showSummary = true;
    $showKeywords = true;
    $showSdgs = true;
    $showIku = true;
    $showTkt = $type === 'research';
    $showTasks = $mode !== 'detail';
    $showNumbers = $mode === 'detail';
    $scheme = $type === 'research' ? $proposal->researchScheme : $proposal->communityServiceScheme;
    $detail = $proposal->detailable;
    $ketua = $proposal->teamMembers->firstWhere('pivot.role', 'ketua');
    $cardTitle = $showNumbers ? '1.1 Informasi Dasar Proposal' : 'Informasi Dasar Proposal';
@endphp

<div>
@if ($showBasic)
    <div class="mb-3 card">
        <div class="card-header">
            <h3 class="card-title">{{ $cardTitle }}</h3>
        </div>
        <div class="card-body">
            @if ($showBasic)
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label"><x-lucide-file-text class="me-2 icon" />Judul</label>
                        <p class="text-reset">{{ $proposal->title }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><x-lucide-info class="me-2 icon" />Status</label>
                        <p>
                            <x-tabler.badge :color="$proposal->status->color()" class="fw-normal">
                                {{ $proposal->status->label() }}
                            </x-tabler.badge>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><x-lucide-user class="me-2 icon" />Author</label>
                        <p class="text-reset">{{ $proposal->submitter?->name ?? '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><x-lucide-mail class="me-2 icon" />Email</label>
                        <p class="text-reset">{{ $proposal->submitter?->email ?? '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">
                            <x-lucide-clipboard-list class="me-2 icon" />
                            Skema {{ $type === 'research' ? 'Penelitian' : 'Pengabdian' }}
                        </label>
                        <p class="text-reset">{{ $scheme?->name ?? '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><x-lucide-calendar class="me-2 icon" />Tahun Akademik</label>
                        <p class="text-reset">{{ $proposal->academic_year ?? '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><x-lucide-calendar-check class="me-2 icon" />Semester</label>
                        <p class="text-reset">
                            {{ $proposal->semester_period_label ?: (ucfirst($proposal->semester ?? '') ?: '—') }}
                        </p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><x-lucide-hourglass class="me-2 icon" />Durasi</label>
                        <p class="text-reset">
                            {{ $proposal->duration_in_years ? $proposal->duration_in_years . ' Tahun' : '—' }}
                        </p>
                    </div>
                </div>
            @endif

            <hr class="my-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label"><x-lucide-focus class="me-2 icon" />Bidang Fokus</label>
                    <p class="text-reset">{{ $proposal->focusArea?->name ?? '—' }}</p>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><x-lucide-tag class="me-2 icon" />Tema</label>
                    <p class="text-reset">{{ $proposal->theme?->name ?? '—' }}</p>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><x-lucide-hash class="me-2 icon" />Topik</label>
                    <p class="text-reset">{{ $proposal->topic?->name ?? '—' }}</p>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><x-lucide-star class="me-2 icon" />Prioritas Nasional</label>
                    <p class="text-reset">{{ $proposal->nationalPriority?->name ?? '—' }}</p>
                </div>
                @if ($type === 'community-service' && $proposal->sbk_value > 0)
                    <div class="col-md-4">
                        <label class="form-label"><x-lucide-dollar-sign class="me-2 icon" />Nilai SBK</label>
                        <p class="text-reset">Rp {{ number_format($proposal->sbk_value, 0, ',', '.') }}</p>
                    </div>
                @endif
                @if (\App\Models\Setting::get('feature_roadmap_active', false))
                    <div class="col-md-12">
                        <label class="form-label">
                            <x-lucide-git-merge class="me-2 icon" />Peta Jalan / Pohon Penelitian Prodi
                        </label>
                        <p class="text-reset">{{ $proposal->studyProgramRoadmap?->title ?? '—' }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif

    <div class="mb-3 card">
        <div class="card-header">
            <h3 class="card-title">
                {{ $showNumbers ? '1.2 ' : '' }}{{ $type === 'research' ? 'Rumpun Ilmu' : 'Klasifikasi Ilmu' }}
            </h3>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Level 1</label>
                    <p class="text-reset">{{ $proposal->clusterLevel1?->name ?? '—' }}</p>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Level 2</label>
                    <p class="text-reset">{{ $proposal->clusterLevel2?->name ?? '—' }}</p>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Level 3</label>
                    <p class="text-reset">{{ $proposal->clusterLevel3?->name ?? '—' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-3 card">
        <div class="card-header">
            <h3 class="card-title">{{ $showNumbers ? '1.3 ' : '' }}Ringkasan & Kata Kunci</h3>
        </div>
        <div class="card-body">
            @if ($showSummary)
                <div class="mb-3">
                    <label class="form-label fw-bold">Ringkasan</label>
                    <p class="text-reset" style="text-align: justify;">{{ $proposal->summary ?? '—' }}</p>
                </div>
            @endif

            @if ($showKeywords)
                <div class="mb-3">
                    <label class="form-label fw-bold">Kata Kunci</label>
                    <div class="d-flex flex-wrap gap-1">
                        @forelse ($proposal->keywords as $keyword)
                            <span class="badge bg-blue-lt text-dark">{{ $keyword->name }}</span>
                        @empty
                            <span class="text-muted">—</span>
                        @endforelse
                    </div>
                </div>
            @endif

            @if ($proposal->asta_cita)
                <div class="mb-3">
                    <label class="form-label fw-bold">Asta Cita Terkait</label>
                    <p class="text-reset" style="text-align: justify;">{{ $proposal->asta_cita }}</p>
                </div>
            @endif

            @if ($type === 'community-service')
                <div class="mb-3">
                    <label class="form-label fw-bold">Masalah Mitra</label>
                    <p class="text-reset" style="text-align: justify;">{{ $proposal->detailable?->partner_issue_summary ?? '—' }}</p>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-bold">Solusi yang Ditawarkan</label>
                    <p class="text-reset" style="text-align: justify;">{{ $proposal->detailable?->solution_offered ?? '—' }}</p>
                </div>
            @endif
        </div>
    </div>

    @if ($showSdgs)
        <div class="mb-3 card">
            <div class="card-header">
                <h3 class="card-title">{{ $showNumbers ? '1.4 ' : '' }}Sustainable Development Goals (SDGs)</h3>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    @forelse ($proposal->sdgs as $sdg)
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <x-lucide-globe class="icon icon-sm me-2 text-success" />
                                <div>
                                    <div class="fw-bold small text-reset">{{ $sdg->name }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">{{ Str::limit($sdg->description, 80) }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <p class="text-muted mb-0">Belum ada SDGs yang dipilih.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    @if ($showIku)
        <div class="mb-3 card">
            <div class="card-header">
                <h3 class="card-title">{{ $showNumbers ? '1.5 ' : '' }}Pemetaan Target IKU</h3>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    @forelse ($proposal->targetedIkus as $iku)
                        <div class="col-md-6">
                            <div class="p-2 border rounded bg-light-subtle">
                                <div class="fw-bold small text-reset">{{ $iku->code }}</div>
                                <div class="text-muted" style="font-size: 0.75rem;">{{ $iku->name }}</div>
                                @if($iku->description)
                                    <div class="text-muted mt-1" style="font-size: 0.7rem;">{{ Str::limit($iku->description, 60) }}</div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <p class="text-muted mb-0">Belum ada target IKU yang dipilih.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    @if ($showTkt)
        <div class="mb-3 card">
            <div class="card-header">
                <h3 class="card-title">Detail Penelitian (TKT)</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Kategori TKT</label>
                        <p class="text-reset">{{ $detail?->tkt_type ?: '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">TKT Saat Ini (Terukur)</label>
                        @php
                            $currentTkt = 0;
                            if ($detail && $detail->tktLevels->isNotEmpty()) {
                                foreach ($detail->tktLevels as $level) {
                                    if ($level->pivot->percentage >= 80) {
                                        $currentTkt = max($currentTkt, $level->level);
                                    }
                                }
                            }
                        @endphp
                        <p>
                            @if ($currentTkt > 0)
                                <span class="badge bg-green text-white">Level {{ $currentTkt }}</span>
                            @else
                                <span class="text-muted">Belum diukur / Level 0</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Target TKT Final</label>
                        @php
                            $targetTktLabel = null;
                            if ($proposal->researchScheme?->strata) {
                                $range = \App\Livewire\Research\Proposal\Components\TktMeasurement::getTktRangeForScheme(
                                    $proposal->research_scheme_id,
                                    $proposal->researchScheme->strata,
                                );
                                if ($range) {
                                    $targetTktLabel = "Level {$range[0]} - {$range[1]}";
                                }
                            }
                        @endphp
                        @if ($targetTktLabel)
                            <x-tabler.badge color="primary">{{ $targetTktLabel }}</x-tabler.badge>
                        @else
                            <p class="text-reset">—</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($showTasks)
        <div class="mb-3 card">
            <div class="card-header">
                <h3 class="card-title">Tugas {{ $type === 'research' ? 'Peneliti' : 'Pelaksana' }}</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Nama Ketua</label>
                        <p class="text-reset">{{ $ketua?->name ?? $proposal->submitter?->name ?? '—' }}</p>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Uraian Tugas</label>
                        <p class="text-reset" style="white-space: pre-wrap;">{{ $ketua?->pivot->tasks ?: '—' }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>