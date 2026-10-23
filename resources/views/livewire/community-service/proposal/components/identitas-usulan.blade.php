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
                    <label class="form-label"><x-lucide-calendar class="me-2 icon" />Periode Pelaksanaan</label>
                    <p class="text-reset">
                        @if ($proposal->academic_year && $proposal->duration_in_years)
                            {{ $proposal->academic_year }} ({{ $proposal->duration_in_years }} Tahun)
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
                @if ($proposal->nationalPriority)
                    <div class="col-md-6">
                        <label class="form-label"><x-lucide-star class="me-2 icon" />Prioritas Nasional</label>
                        <p class="text-reset">{{ $proposal->nationalPriority->name }}</p>
                    </div>
                @endif
            </div>

            @if(\App\Models\Setting::get('feature_roadmap_active', false))
                <div class="mb-3 row">
                    <div class="col-md-12">
                        <label class="form-label"><x-lucide-git-merge class="me-2 icon" />Peta Jalan / Pohon Penelitian Prodi</label>
                        <p class="text-reset">{{ $proposal->studyProgramRoadmap?->title ?? '—' }}</p>
                    </div>
                </div>
            @endif

            @if ($proposal->sbk_value > 0)
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label"><x-lucide-dollar-sign class="me-2 icon" />Nilai SBK</label>
                        <p class="text-reset">Rp {{ number_format($proposal->sbk_value, 0, ',', '.') }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="mb-3 card">
        <div class="card-header">
            <h3 class="card-title">1.3 Klasifikasi Ilmu</h3>
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
            <h3 class="card-title">1.4 Ringkasan & Masalah Mitra</h3>
        </div>
        <div class="card-body">
            @php $communityService = $proposal->detailable; @endphp
            <div class="mb-4">
                <label class="form-label fw-bold">Ringkasan Proposal</label>
                <p class="text-reset" style="text-align: justify;">{{ $proposal->summary ?? '—' }}</p>
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold">Masalah Mitra</label>
                <p class="text-reset" style="text-align: justify;">
                    {{ $communityService?->partner_issue_summary ?? '—' }}</p>
            </div>
            <div class="mb-0">
                <label class="form-label fw-bold">Solusi yang Ditawarkan</label>
                <p class="text-reset" style="text-align: justify;">
                    {{ $communityService?->solution_offered ?? '—' }}</p>
            </div>
        </div>
    </div>
</div>
