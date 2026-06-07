<x-slot:title>{{ $proposal->title }}</x-slot:title>
<x-slot:pageTitle>{{ $proposal->title }}</x-slot:pageTitle>
<x-slot:pageSubtitle>Detail Revisi Proposal Penelitian</x-slot:pageSubtitle>
<x-slot:pageActions>
    <div class="btn-list">
        <a href="{{ route('research.proposal-revision.index') }}" class="btn-outline-secondary btn" wire:navigate.hover>
            <x-lucide-arrow-left class="icon" />
            Kembali
        </a>
    </div>
</x-slot:pageActions>

<div class="row" x-data="{
    currentStep: 1,
}">
    <div class="col-md-12">
        <x-tabler.alert />
    </div>

    <!-- Steps Indicator -->
    <div class="mb-3 col-md-12">
        <div class="card">
            <div class="card-body">
                <ul class="my-4 steps steps-green steps-counter">
                    <li class="step-item" :class="{ 'active': currentStep === 1 }">
                        <a href="#" @click.prevent="currentStep = 1" class="text-decoration-none">Substansi
                            Usulan</a>
                    </li>
                    <li class="step-item" :class="{ 'active': currentStep === 2 }">
                        <a href="#" @click.prevent="currentStep = 2" class="text-decoration-none">RAB</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Content Sections -->
    <div class="col-md-12">
        <!-- Section 1: Substansi Usulan -->
        <div id="section-substansi" x-show="currentStep === 1">
            <!-- Basic Info Card -->
            <div class="mb-3 card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Dasar</h3>
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
                        <div class="col-md-3">
                            <label class="form-label"><x-lucide-user class="me-2 icon" />Author</label>
                            <p class="text-reset">{{ $proposal->submitter?->name }}</p>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><x-lucide-clipboard-list class="me-2 icon" />Skema
                                Penelitian</label>
                            <p class="text-reset">{{ $proposal->researchScheme?->name ?? '—' }}</p>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><x-lucide-calendar class="me-2 icon" />Tanggal
                                Pengajuan</label>
                            <p class="text-reset">
                                {{ $proposal->created_at?->format('d M Y H:i') ?? '—' }}
                            </p>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><x-lucide-calendar-check class="me-2 icon" />Tanggal
                                update</label>
                            <p class="text-reset">
                                {{ $proposal->detailable->updated_at?->format('d M Y H:i') ?? '—' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Macro Research Group Card -->
            <div class="mb-3 card" data-field="Kelompok Makro Riset">
                <div class="card-header">
                    <h3 class="card-title">1.1 Kelompok Makro Riset</h3>
                    @if ($this->canEdit())
                        <div class="card-actions">
                            <x-tabler.badge color="info">
                                <x-lucide-pencil class="icon icon-sm" />
                                Dapat Diedit
                            </x-tabler.badge>
                        </div>
                    @endif
                </div>
                <div class="card-body">
                    @php $research = $proposal->detailable; @endphp

                    @if ($this->canEdit())
                        <div class="mb-3">
                            <label class="form-label required">
                                <x-lucide-layers class="me-2 icon" />
                                Kelompok Makro Riset
                            </label>
                            <select wire:model="macroResearchGroupId"
                                class="form-select @error('macroResearchGroupId') is-invalid @enderror">
                                <option value="">Pilih Kelompok Makro Riset</option>
                                @foreach ($this->macroResearchGroups as $group)
                                    <option value="{{ $group->id }}" wire:key="group-{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                            @error('macroResearchGroupId')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @if ($macroResearchGroupId)
                                @php
                                    $selectedGroup = $this->macroResearchGroups->firstWhere(
                                        'id',
                                        $macroResearchGroupId,
                                    );
                                @endphp
                                @if ($selectedGroup?->description)
                                    <small class="text-muted form-hint">{{ $selectedGroup->description }}</small>
                                @endif
                            @endif
                        </div>
                    @else
                        <div class="mb-3">
                            <label class="form-label"><x-lucide-layers class="me-2 icon" />Kelompok Makro
                                Riset</label>
                            <p class="text-reset">{{ $research?->macroResearchGroup?->name ?? '—' }}</p>
                            @if ($research?->macroResearchGroup?->description)
                                <small class="text-muted">{{ $research->macroResearchGroup->description }}</small>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- File Substansi Card -->
            <div class="mb-3 card" data-field="File Substansi">
                <div class="card-header">
                    <h3 class="card-title">1.2 File Substansi</h3>
                    @if ($this->canEdit())
                        <div class="card-actions">
                            <x-tabler.badge color="info">
                                <x-lucide-pencil class="icon icon-sm" />
                                Dapat Diedit
                            </x-tabler.badge>
                        </div>
                    @endif
                </div>
                <div class="card-body">
                    @php $research = $proposal->detailable; @endphp

                    <!-- Current File -->
                    <div class="mb-3">
                        <label class="form-label"><x-lucide-file class="me-2 icon" />File Substansi Saat Ini</label>
                        @if ($research?->substance_file)
                            <div class="d-flex align-items-center gap-2">
                                <a href="{{ Storage::url($research->substance_file) }}" target="_blank"
                                    class="btn-outline-primary btn btn-sm">
                                    <x-lucide-download class="icon" />
                                    Download File Substansi
                                </a>
                                <small class="text-muted">
                                    <x-lucide-check class="text-success icon icon-sm" />
                                    File tersedia
                                </small>
                            </div>
                        @else
                            <p class="mb-0 text-muted text-reset">Tidak ada file substansi</p>
                        @endif
                    </div>

                    <!-- Upload New File (Only for submitter) -->
                    @if ($this->canEdit())
                        <div class="mb-3">
                            <label class="form-label">
                                <x-lucide-upload class="me-2 icon" />
                                Upload File Substansi Baru
                            </label>
                            <input type="file" wire:model="substanceFile"
                                class="form-control @error('substanceFile') is-invalid @enderror"
                                accept=".pdf" />
                            @error('substanceFile')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-hint">
                                Format: PDF. Maksimal: 10MB.
                                @if ($research?->substance_file)
                                    File lama akan diganti dengan file baru.
                                @endif
                            </small>

                            <!-- Loading indicator -->
                            <div wire:loading wire:target="substanceFile" class="mt-2">
                                <div class="d-flex align-items-center text-primary">
                                    <span class="me-2 spinner-border spinner-border-sm"></span>
                                    <small>Mengunggah file...</small>
                                </div>
                            </div>

                            <!-- Success indicator -->
                            @if ($substanceFile && !$errors->has('substanceFile'))
                                <div class="mt-2">
                                    <small class="text-success">
                                        <x-lucide-check class="icon icon-sm" />
                                        File siap diunggah: {{ $substanceFile->getClientOriginalName() }}
                                    </small>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Scoring Summary & Reviewer Notes Card -->
            <div class="mb-3 card">
                <div class="card-header">
                    <h3 class="card-title">1.3 Penilaian dan Catatan Reviewer</h3>
                </div>
                @php
                    $completedReviewers = $proposal->reviewers->where('status', 'completed');
                @endphp
                @if ($completedReviewers->isEmpty())
                    <div class="card-body">
                        <p class="text-muted">Belum ada penilaian dari reviewer</p>
                    </div>
                @else
                    <div class="divide-y">
                        @foreach ($completedReviewers as $reviewer)
                            <div wire:key="comp-rev-{{ $reviewer->id }}" class="p-3">
                                <div class="mb-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center">
                                            <x-lucide-user-circle class="me-2 text-primary icon" />
                                            <div class="fw-bold">{{ $reviewer->user?->name ?? 'Reviewer' }}</div>
                                        </div>
                                        <div class="text-end">
                                            <x-tabler.badge :color="$reviewer->recommendation === 'approved' ? 'success' : ($reviewer->recommendation === 'rejected' ? 'danger' : 'warning')">
                                                {{ $reviewer->recommendation === 'approved' ? 'Disetujui' : ($reviewer->recommendation === 'rejected' ? 'Ditolak' : 'Perlu Revisi') }}
                                            </x-tabler.badge>
                                            <div class="mt-1 text-muted small">
                                                {{ $reviewer->completed_at?->format('d M Y H:i') }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Scoring Details Table -->
                                    <div class="table-responsive mb-3 border rounded-2">
                                        <table class="table table-vcenter mb-0">
                                            <thead class="bg-body-tertiary">
                                                <tr>
                                                    <th class="py-2">Kriteria</th>
                                                    <th class="py-2">Catatan Reviewer</th>
                                                    <th class="py-2 w-1 text-center">Skor</th>
                                                    <th class="py-2 w-1 text-center">Bobot</th>
                                                    <th class="py-2 w-1 text-end">Nilai</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($reviewer->scores->where('round', $reviewer->round) as $s)
                                                    <tr wire:key="score-{{ $s->id }}">
                                                        <td class="py-2 fw-bold" style="font-size: 10pt;">
                                                            {{ $s->criteria->criteria }}</td>
                                                        <td class="py-2 text-secondary italic"
                                                            style="font-size: 10pt;">{{ $s->acuan }}</td>
                                                        <td class="py-2 text-center" style="font-size: 10pt;">
                                                            {{ $s->score }}</td>
                                                        <td class="py-2 text-muted text-center"
                                                            style="font-size: 10pt;">
                                                            {{ number_format($s->weight_snapshot, 0) }}%</td>
                                                        <td class="py-2 text-end fw-bold" style="font-size: 10pt;">
                                                            {{ number_format($s->value, 0) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot class="bg-body-tertiary fw-bold">
                                                <tr>
                                                    <td colspan="2" class="py-2 text-end">TOTAL:</td>
                                                    <td class="py-2 text-center">
                                                        {{ $reviewer->scores->where('round', $reviewer->round)->sum('score') }}
                                                    </td>
                                                    <td class="py-2 text-center">
                                                        {{ number_format($reviewer->scores->where('round', $reviewer->round)->sum('weight_snapshot'), 0) }}%
                                                    </td>
                                                    <td class="py-2 text-primary text-end">
                                                        {{ number_format($reviewer->scores->where('round', $reviewer->round)->sum('value'), 0) }}
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>

                                    <div class="mb-3 text-secondary small">
                                        <x-lucide-info class="icon-inline me-1 icon" />
                                        Total nilai dihitung otomatis: (Skor × Bobot). Passing Grade: 300.
                                    </div>

                                    @if ($reviewer->review_notes)
                                        <div class="mb-0">
                                            <label class="mb-1 form-label fw-bold small">
                                                <x-lucide-message-square class="icon icon-sm" />
                                                Catatan Review Keseluruhan:
                                            </label>
                                            <div class="bg-body-tertiary p-3 border rounded">
                                                <p class="mb-0 text-reset small" style="white-space: pre-wrap;">
                                                    {{ $reviewer->review_notes }}</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Review History Logs -->
            @if ($proposal->reviewLogs->isNotEmpty())
                <div class="mb-3 card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <x-lucide-history class="me-2 icon" />
                            Riwayat Review Sebelumnya
                        </h3>
                    </div>
                    <div class="p-0 card-body">
                        <div class="accordion" id="reviewHistoryAccordion">
                            @foreach ($proposal->reviewLogs->groupBy('round') as $round => $logs)
                                <div wire:key="round-{{ $round }}" class="border-0 border-bottom accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="py-2 accordion-button collapsed" type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#historyRound{{ $round }}">
                                            <x-lucide-layers class="me-2 icon" />
                                            Round #{{ $round }}
                                            <span class="bg-secondary-lt ms-2 badge">{{ $logs->count() }}
                                                review</span>
                                        </button>
                                    </h2>
                                    <div id="historyRound{{ $round }}" class="accordion-collapse collapse"
                                        data-bs-parent="#reviewHistoryAccordion">
                                        <div class="p-0 accordion-body">
                                            <div class="divide-y">
                                                @foreach ($logs as $log)
                                                    <div wire:key="log-{{ $log->id }}" class="bg-body-tertiary p-3">
                                                        <div
                                                            class="d-flex align-items-center justify-content-between mb-2">
                                                            <div class="fw-bold small">
                                                                {{ $log->user?->name ?? 'Reviewer' }}</div>
                                                            <x-tabler.badge :color="$log->recommendation_color" class="small">
                                                                {{ $log->recommendation_label }}
                                                            </x-tabler.badge>
                                                        </div>
                                                        @if ($log->total_score)
                                                            <div class="mb-2 text-dark small fw-bold">Total Skor:
                                                                {{ number_format($log->total_score, 0) }}</div>
                                                        @endif

                                                        <!-- History Scores Small Table -->
                                                        <div class="table-responsive mb-2">
                                                            <table
                                                                class="table table-borderless table-vcenter mb-0 border rounded"
                                                                style="font-size: 9pt;">
                                                                <thead class="bg-body-tertiary">
                                                                    <tr>
                                                                        <th class="px-2 py-1">Kriteria</th>
                                                                        <th class="px-2 py-1 text-center">Skor</th>
                                                                        <th class="px-2 py-1 text-end">Nilai</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach ($log->scores->where('round', $log->round) as $ls)
                                                                        <tr>
                                                                            <td class="px-2 py-1">
                                                                                {{ $ls->criteria->criteria }}</td>
                                                                            <td class="px-2 py-1 text-center">
                                                                                {{ $ls->score }}</td>
                                                                            <td class="px-2 py-1 text-end fw-bold">
                                                                                {{ number_format($ls->value, 0) }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>

                                                        <div class="p-2 border rounded text-muted italic small">
                                                            {{ $log->review_notes }}
                                                        </div>
                                                        <div class="mt-1 text-end">
                                                            <small
                                                                class="text-muted">{{ $log->completed_at?->format('d M Y H:i') }}</small>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

        </div>

        <!-- Section 2: RAB (Read-Only) -->
        <div id="section-rab" x-show="currentStep === 2">
            <div class="mb-3 card">
                <div class="card-header">
                    <h3 class="card-title">2.1 Rencana Anggaran Biaya (RAB)</h3>
                    <div class="card-actions">
                        <x-tabler.badge color="info">Read-Only</x-tabler.badge>
                    </div>
                </div>
                @if ($proposal->budgetItems->isEmpty())
                    <div class="card-body">
                        <div class="py-4 text-muted text-center">
                            <x-lucide-inbox class="mb-2 icon icon-lg" />
                            <p>Belum ada item anggaran</p>
                        </div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="card-table table table-bordered table-vcenter">
                            <thead>
                                <tr>
                                    <th width="20%">Kelompok</th>
                                    <th width="25%">Komponen</th>
                                    <th width="10%">Volume</th>
                                    <th width="10%">Unit</th>
                                    <th width="15%">Harga Satuan</th>
                                    <th width="20%">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($proposal->budgetItems as $item)
                                    <tr>
                                        <td>{{ $item->budgetGroup?->name ?? ($item->group ?? '-') }}</td>
                                        <td>{{ $item->budgetComponent?->name ?? ($item->component ?? '-') }}</td>
                                        <td class="text-center">{{ $item->volume }}</td>
                                        <td class="text-center">
                                            <x-tabler.badge variant="outline">
                                                {{ $item->budgetComponent?->unit ?? '-' }}
                                            </x-tabler.badge>
                                        </td>
                                        <td class="text-end">Rp
                                            {{ number_format($item->unit_price, 0, ',', '.') }}
                                        </td>
                                        <td class="text-end fw-bold">Rp
                                            {{ number_format($item->total_price, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="table-active">
                                    <th colspan="5" class="text-end">Total Anggaran:</th>
                                    <th class="text-end">
                                        <span class="text-primary">
                                            Rp
                                            {{ number_format($proposal->budgetItems->sum('total_price'), 0, ',', '.') }}
                                        </span>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Summary Card -->
            <div class="mb-3 card">
                <div class="card-header">
                    <h3 class="card-title">Ringkasan Anggaran</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Jumlah Item Anggaran</label>
                                <p class="text-reset h4">{{ $proposal->budgetItems->count() }} item</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Total Anggaran</label>
                                <p class="text-primary text-reset h4">
                                    Rp {{ number_format($proposal->budgetItems->sum('total_price'), 0, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    @if ($proposal->sbk_value)
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-0">
                                    <label class="form-label">Nilai SBK</label>
                                    <p class="text-reset">Rp
                                        {{ number_format($proposal->sbk_value, 0, ',', '.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="mt-4">
            <div class="d-flex justify-content-between">
                <button type="button" class="btn" @click="currentStep--" x-show="currentStep > 1">
                    <x-lucide-arrow-left class="icon" />
                    Kembali
                </button>
                <div x-show="currentStep === 1"></div>
                <button type="button" class="btn btn-primary" @click="currentStep++" x-show="currentStep < 2">
                    Selanjutnya
                    <x-lucide-arrow-right class="icon" />
                </button>
            </div>
        </div>


        <!-- Save Button for Submitter -->
        @if ($this->canEdit())
            <div class="mt-3 card">
                <div class="card-body">
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" wire:click="save" class="position-relative btn btn-primary"
                            wire:loading.attr="disabled" wire:target="save" wire:loading.class="btn-loading">
                            <span wire:loading.remove wire:target="save">
                                <x-lucide-save class="icon" />
                                Simpan Perubahan
                            </span>
                            <span wire:loading wire:target="save">
                                <span class="me-2 spinner-border spinner-border-sm"></span>
                                <span>Menyimpan...</span>
                            </span>
                        </button>
                    </div>
                    <small class="d-block mt-2 text-muted">
                        <x-lucide-info class="icon icon-sm" />
                        Pastikan Anda telah memilih kelompok makro riset dan/atau mengunggah file substansi baru
                        sebelum menyimpan.
                    </small>
                </div>
            </div>
        @endif
    </div>
</div>
