<div>
    <div class="mb-3 card">
        <div class="card-header">
            <h3 class="card-title">1.1 Informasi Dasar</h3>
        </div>
        <div class="card-body">
            <div class="mb-3 row">
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
            </div>

            <div class="mb-3 row">
                <div class="col-md-6">
                    <label class="form-label"><x-lucide-user class="me-2 icon" />Author</label>
                    <p class="text-reset">{{ $proposal->submitter?->name }}</p>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><x-lucide-mail class="me-2 icon" />Email</label>
                    <p class="text-reset">{{ $proposal->submitter?->email }}</p>
                </div>
            </div>

            <div class="mb-3 row">
                <div class="col-md-6">
                    <label class="form-label"><x-lucide-clipboard-list class="me-2 icon" />Skema
                        Penelitian</label>
                    <p class="text-reset">{{ $proposal->researchScheme?->name ?? '—' }}</p>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><x-lucide-calendar class="me-2 icon" />Periode Pelaksanaan</label>
                    <p class="text-reset">
                        @if ($proposal->start_year && $proposal->duration_in_years)
                            {{ $proposal->start_year }} -
                            {{ (int) $proposal->start_year + (int) $proposal->duration_in_years - 1 }}
                            ({{ $proposal->duration_in_years }} Tahun)
                        @else
                            {{ $proposal->duration_in_years ?? '—' }} Tahun
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-3 card">
        <div class="card-header">
            <h3 class="card-title">1.2 Informasi Dasar Proposal</h3>
        </div>
        <div class="card-body">
            <div class="mb-3 row">
                <div class="col-md-6">
                    <label class="form-label"><x-lucide-focus class="me-2 icon" />Bidang Fokus</label>
                    <p class="text-reset">{{ $proposal->focusArea?->name ?? '—' }}</p>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><x-lucide-tag class="me-2 icon" />Tema</label>
                    <p class="text-reset">{{ $proposal->theme?->name ?? '—' }}</p>
                </div>
            </div>

            <div class="mb-3 row">
                <div class="col-md-6">
                    <label class="form-label"><x-lucide-hash class="me-2 icon" />Topik</label>
                    <p class="text-reset">{{ $proposal->topic?->name ?? '—' }}</p>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><x-lucide-star class="me-2 icon" />Prioritas Nasional</label>
                    <p class="text-reset">{{ $proposal->nationalPriority?->name ?? '—' }}</p>
                </div>
            </div>

            @if(\App\Models\Setting::get('feature_roadmap_active', false))
                <div class="mb-3 row">
                    <div class="col-md-12">
                        <label class="form-label"><x-lucide-git-merge class="me-2 icon" />Peta Jalan / Pohon Penelitian Prodi</label>
                        <p class="text-reset">{{ $proposal->studyProgramRoadmap?->title ?? '—' }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="mb-3 card">
        <div class="card-header">
            <h3 class="card-title">1.3 Rumpun Ilmu</h3>
        </div>
        <div class="card-body">
            <div class="mb-3 row">
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
            <h3 class="card-title">1.4 Ringkasan</h3>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <p class="text-reset">{{ $proposal->summary ?? '—' }}</p>
            </div>
        </div>
    </div>

    <div class="mb-3 card">
        <div class="card-header">
            <h3 class="card-title">1.5 Detail Penelitian</h3>
        </div>
        <div class="card-body">
            @php $research = $proposal->detailable; @endphp
            <div class="mb-3">
                <label class="form-label">TKT Saat Ini (Terukur)</label>
                @php
                    $currentTkt = 0;
                    if ($research && $research->tktLevels->isNotEmpty()) {
                        foreach ($research->tktLevels as $level) {
                            if ($level->pivot->percentage >= 80) {
                                $currentTkt = max($currentTkt, $level->level);
                            }
                        }
                    }
                @endphp
                <p>
                    @if ($currentTkt > 0)
                        <span class="bg-green text-white badge">Level {{ $currentTkt }}</span>
                    @else
                        <span class="text-muted">Belum diukur / Level 0</span>
                    @endif
                </p>
            </div>
            <div class="mb-3">
                <label class="form-label">Target TKT Final</label>
                @php
                    $targetTktLabel = null;
                    if ($proposal->researchScheme && $proposal->researchScheme->strata) {
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
                    <div>
                        <x-tabler.badge color="primary">{{ $targetTktLabel }}</x-tabler.badge>
                    </div>
                @else
                    <p class="text-reset">—</p>
                @endif
            </div>
        </div>
    </div>
</div>
