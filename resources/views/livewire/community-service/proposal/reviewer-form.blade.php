<div>
    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="mb-3 alert alert-success alert-dismissible" role="alert">
            <div class="d-flex">
                <x-lucide-check-circle class="me-2 alert-icon" />
                <div>{{ session('success') }}</div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
    @endif

    @if ($this->allReviews->isNotEmpty() || $this->canReview)
        {{-- Card 1: Status Reviewer Saat Ini --}}
        <div class="shadow-sm mb-3 border-0 card card-md">
            <div class="card-header">
                <h3 class="card-title">
                    <x-lucide-users class="me-2 icon" />
                    Status Reviewer Saat Ini
                </h3>
                <div class="card-actions">
                    <span class="bg-blue-lt badge">{{ $this->allReviews->count() }} Reviewer</span>
                </div>
            </div>
            <div class="p-2 card-body">
                @if ($this->allReviews->isNotEmpty())
                    <div class="divide-y">
                        @foreach ($this->allReviews as $review)
                            <div class="px-2 py-2">
                                <div class="align-items-start row g-3">
                                    <div class="col-auto">
                                        <span
                                            class="bg-blue-lt avatar avatar-sm fw-bold">{{ substr($review->user->name, 0, 1) }}</span>
                                    </div>
                                    <div class="col">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <div>
                                                <div class="fw-bold">{{ $review->user->name }}</div>
                                                <div class="text-secondary small">{{ $review->user->email }}</div>
                                            </div>
                                            <div class="text-end">
                                                <x-tabler.badge :color="$review->status->color()" class="mb-1">
                                                    {{ $review->status->label() }}
                                                </x-tabler.badge>
                                                @if ($review->round > 1)
                                                    <span class="bg-purple-lt badge" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Siklus review ke-{{ $review->round }}. #1 = review awal, #2+ = review ulang setelah revisi">#{{ $review->round }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        @if ($review->recommendation)
                                            <div
                                                class="rounded-2 {{ $review->recommendation === 'approved'
                                                    ? 'bg-success-lt'
                                                    : ($review->recommendation === 'rejected'
                                                        ? 'bg-danger-lt'
                                                        : 'bg-warning-lt') }} my-2 p-2">
                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                    <div class="d-flex align-items-center small fw-bold">
                                                        @if ($review->recommendation === 'approved')
                                                            <x-lucide-check-circle class="me-1 text-success icon" />
                                                            Rekomendasi: Disetujui
                                                        @elseif($review->recommendation === 'rejected')
                                                            <x-lucide-x-circle class="me-1 text-danger icon" />
                                                            Rekomendasi: Ditolak
                                                        @else
                                                            <x-lucide-refresh-cw class="me-1 text-warning icon" />
                                                            Rekomendasi: Perlu Revisi
                                                        @endif
                                                    </div>
                                                    @if ($review->isCompleted())
                                                        <div class="d-flex align-items-center gap-3">
                                                            <div class="text-dark small fw-bold">
                                                                Total Skor:
                                                                {{ number_format($review->scores->where('round', $review->round)->sum('value'), 0) }}
                                                            </div>
                                                            <a data-navigate-ignore="true" href="{{ route('reviewers.export-pdf', [$review->id, 'preview' => 1]) }}"
                                                                target="_blank"
                                                                class="px-2 py-1 btn btn-sm btn-ghost-danger">
                                                                <x-lucide-eye class="me-1 icon icon-sm" />
                                                                Tinjau PDF
                                                            </a>
                                                            <a data-navigate-ignore="true" href="{{ route('reviewers.export-pdf', $review->id) }}"
                                                                target="_blank"
                                                                class="px-2 py-1 btn btn-sm btn-ghost-danger">
                                                                <x-lucide-file-text class="me-1 icon icon-sm" />
                                                                Export PDF
                                                            </a>
                                                        </div>
                                                    @endif
                                                </div>
                                                @if ($review->review_notes)
                                                    <p class="mb-1 text-body small" style="white-space: pre-line;">
                                                        {{ $review->review_notes }}</p>
                                                @endif

                                                @if ($review->isCompleted())
                                                    <div class="mt-2 pt-2 border-dark-subtle border-top">
                                                        <div class="table-responsive">
                                                            <table class="table table-borderless table-sm mb-0 small">
                                                                <thead class="text-muted">
                                                                    <tr>
                                                                        <th>Kriteria</th>
                                                                        <th>Catatan / Acuan</th>
                                                                        <th class="text-center">Skor</th>
                                                                        <th class="text-center">Bobot</th>
                                                                        <th class="text-end">Nilai</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach ($review->scores->where('round', $review->round) as $s)
                                                                        <tr>
                                                                            <td class="text-wrap">
                                                                                {{ $s->criteria->criteria }}</td>
                                                                            <td class="italic text-wrap small">
                                                                                {{ $s->acuan }}</td>
                                                                            <td class="text-center">{{ $s->score }}
                                                                            </td>
                                                                            <td class="text-center">
                                                                                {{ number_format($s->weight_snapshot, 0) }}%
                                                                            </td>
                                                                            <td class="text-end fw-bold">
                                                                                {{ number_format($s->value, 0) }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                                <tfoot>
                                                                    @php $rs = $review->scores->where('round', $review->round); @endphp
                                                                    <tr class="border-top fw-bold">
                                                                        <td colspan="2" class="text-end">TOTAL:</td>
                                                                        <td class="text-center">{{ $rs->sum('score') }}
                                                                        </td>
                                                                        <td class="text-center">
                                                                            {{ number_format($rs->sum('weight_snapshot'), 0) }}%
                                                                        </td>
                                                                        <td class="text-primary text-end">
                                                                            {{ number_format($rs->sum('value'), 0) }}
                                                                        </td>
                                                                    </tr>
                                                                </tfoot>
                                                            </table>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif

                                        <div class="d-flex align-items-center justify-content-between mt-2">
                                            <small class="text-secondary">
                                                <x-lucide-clock class="icon-inline me-1 icon" />
                                                {{ $review->updated_at?->diffForHumans() ?? '-' }}
                                            </small>
                                            @if ($review->completed_at)
                                                <small class="text-muted italic">
                                                     Diselesaikan pada: {{ $review->completed_at ? $review->completed_at->format('d M Y H:i') : 'Belum selesai' }}
                                                 </small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-surface-secondary py-5 rounded-3 text-center">
                        <x-lucide-users class="mb-2 text-muted icon icon-lg" />
                        <div class="text-secondary">Belum ada reviewer yang ditugaskan.</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Card 2: Riwayat & Daftar Review --}}
        @if ($this->allReviewLogs->isNotEmpty())
            <div class="shadow-sm mb-3 border-0 card card-md">
                <div class="card-header">
                    <h3 class="card-title">
                        <x-lucide-history class="me-2 icon" />
                        Riwayat & Daftar Review
                    </h3>
                </div>
                <div class="p-0 card-body">
                    <div class="accordion" id="reviewHistoryAccordion">
                        @foreach ($this->allReviewLogs as $round => $logs)
                            <div class="border-0 border-bottom accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}"
                                        type="button" data-bs-toggle="collapse"
                                        data-bs-target="#historyRound{{ $round }}"
                                        aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                                        <x-lucide-clipboard-list class="me-2 icon" />
                                        <span data-bs-toggle="tooltip" data-bs-placement="top"
                                            title="Siklus review. #1 = review awal, #2+ = review ulang setelah revisi">#{{ $round }}</span>
                                        <span class="bg-secondary-lt ms-2 badge">{{ $logs->count() }} review</span>
                                        @if ($round == $this->reviewRound)
                                            <span class="bg-primary-lt ms-1 badge">Saat ini</span>
                                        @endif
                                    </button>
                                </h2>
                                <div id="historyRound{{ $round }}"
                                    class="accordion-collapse {{ $loop->first ? 'show' : '' }} collapse"
                                    data-bs-parent="#reviewHistoryAccordion">
                                    <div class="p-0 accordion-body">
                                        <div class="divide-y">
                                            @foreach ($logs as $log)
                                                <div class="p-3">
                                                    <div class="align-items-start row g-3">
                                                        <div class="col-auto">
                                                            <span class="bg-blue-lt avatar avatar-sm fw-bold">
                                                                {{ substr($log->user?->name ?? 'R', 0, 1) }}
                                                            </span>
                                                        </div>
                                                        <div class="col">
                                                            <div
                                                                class="d-flex align-items-center justify-content-between mb-1">
                                                                <div>
                                                                    <div class="fw-bold">
                                                                        {{ $log->user?->name ?? 'Reviewer' }}</div>
                                                                    <div class="text-secondary small">
                                                                        {{ $log->user?->email }}</div>
                                                                </div>
                                                                <div class="text-end">
                                                                    <span
                                                                        class="badge bg-{{ $log->recommendation_color }}-lt">
                                                                        {{ $log->recommendation_label }}
                                                                    </span>
                                                                    @if ($log->total_score)
                                                                        <div class="mt-1 small fw-bold">Skor:
                                                                            {{ $log->total_score }}</div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            @if ($log->review_notes)
                                                                <div class="bg-body-tertiary my-2 p-2 rounded-2">
                                                                    <p class="mb-1 text-body small"
                                                                        style="white-space: pre-line;">
                                                                        {{ $log->review_notes }}
                                                                    </p>

                                                                    @php $logScores = $log->scores->where('round', $log->round); @endphp
                                                                    @if ($logScores->isNotEmpty())
                                                                        <div
                                                                            class="mt-2 pt-2 border-gray-300 border-top">
                                                                            <table
                                                                                class="table table-borderless table-sm mb-0 text-muted small">
                                                                                <thead>
                                                                                    <tr>
                                                                                        <th>Kriteria</th>
                                                                                        <th>Catatan</th>
                                                                                        <th class="text-center">Skor
                                                                                        </th>
                                                                                        <th class="text-end">Nilai</th>
                                                                                    </tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                    @foreach ($logScores as $ls)
                                                                                        <tr>
                                                                                            <td class="text-wrap">
                                                                                                {{ $ls->criteria->criteria }}
                                                                                            </td>
                                                                                            <td
                                                                                                class="italic text-wrap small">
                                                                                                {{ $ls->acuan }}
                                                                                            </td>
                                                                                            <td class="text-center">
                                                                                                {{ $ls->score }}
                                                                                            </td>
                                                                                            <td
                                                                                                class="text-end fw-bold">
                                                                                                {{ number_format($ls->value, 0) }}
                                                                                            </td>
                                                                                        </tr>
                                                                                    @endforeach
                                                                                </tbody>
                                                                                <tfoot>
                                                                                    <tr class="border-top fw-bold">
                                                                                        <td colspan="2"
                                                                                            class="text-muted text-end small">
                                                                                            TOTAL:</td>
                                                                                        <td
                                                                                            class="text-muted text-center small">
                                                                                            {{ $logScores->sum('score') }}
                                                                                        </td>
                                                                                        <td
                                                                                            class="text-primary text-end small">
                                                                                            {{ number_format($logScores->sum('value'), 0) }}
                                                                                        </td>
                                                                                    </tr>
                                                                                </tfoot>
                                                                            </table>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @endif
                                                            <div
                                                                class="d-flex align-items-center justify-content-between mt-2">
                                                                <small class="text-secondary">
                                                                    <x-lucide-clock class="icon-inline me-1 icon" />
                                                                    {{ $log->completed_at?->diffForHumans() ?? '-' }}
                                                                </small>
                                                                @if ($log->completed_at)
                                                                    <small class="text-muted italic">
                                                                        {{ $log->completed_at ? $log->completed_at->format('d M Y H:i') : 'Belum selesai' }}
                                                                    </small>
                                                                @endif
                                                            </div>
                                                        </div>
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

        {{-- Card 3: Panel Reviewer (Form) - At the bottom --}}
        @if ($this->canReview)
            <div class="shadow-sm mb-3 border-0 card card-md" id="review-section">
                <div class="card-status-top bg-primary"></div>
                <div class="bg-primary-lt card-header">
                    <div>
                        <h3 class="text-primary card-title">
                            <x-lucide-edit-3 class="me-2 icon" />
                            Panel Reviewer
                            @if ($this->reviewRound > 1)
                                <span class="bg-purple-lt ms-2 badge" data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="Siklus review ke-{{ $this->reviewRound }}. #1 = review awal, #2+ = review ulang setelah revisi">#{{ $this->reviewRound }}</span>
                            @endif
                        </h3>
                        <div class="mt-1 text-secondary small">Silakan berikan penilaian dan rekomendasi Anda untuk
                            proposal ini.</div>
                    </div>

                    <div class="card-actions">
                        @if ($this->needsAction)
                            <button type="button"
                                class="btn {{ $this->showForm ? 'btn-secondary' : 'btn-primary' }} btn-pill shadow-sm"
                                wire:click="toggleForm">
                                @if ($this->showForm)
                                    <x-lucide-x class="me-1 icon" />
                                    Tutup Form
                                @else
                                    <x-lucide-play-circle class="me-1 icon" />
                                    Mulai Review
                                @endif
                            </button>
                        @elseif ($this->canEditReview)
                            <button type="button"
                                class="btn {{ $this->showForm ? 'btn-outline-secondary' : 'btn-outline-primary' }} btn-sm"
                                wire:click="toggleForm">
                                <x-lucide-edit-3 class="me-1 icon" />
                                {{ $this->showForm ? 'Tutup Form' : 'Ubah Review' }}
                            </button>
                        @endif
                    </div>
                </div>

                @if ($this->myReview)
                    <div class="bg-surface-secondary py-3 card-body">
                        <div class="align-items-center row g-3">
                            <div class="col-auto">
                                <div class="text-secondary small">Status Anda:</div>
                                <x-tabler.badge :color="$this->myReview->status->color()">
                                    <x-dynamic-component :component="'lucide-' . $this->myReview->status->icon()" class="icon-inline me-1 icon" />
                                    {{ $this->myReview->status->label() }}
                                </x-tabler.badge>
                            </div>
                            @if ($this->deadline)
                                <div class="ps-3 col-auto" style="border-left: 1px solid var(--tblr-border-color);">
                                    <div class="text-secondary small">Batas Waktu:</div>
                                    <div class="fw-bold {{ $this->isOverdue ? 'text-danger' : 'text-body' }}">
                                        <x-lucide-calendar class="me-1 icon" />
                                        {{ $this->deadline ? $this->deadline->format('d M Y') : '-' }}
                                        @if ($this->isOverdue)
                                            <span class="bg-danger-lt ms-1 badge">Terlambat!</span>
                                        @elseif($this->daysRemaining !== null)
                                            <span
                                                class="ms-1 font-normal text-muted small">({{ $this->daysRemaining }}
                                                hari lagi)</span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($this->showForm)
                    <div class="card-body">
                        <form wire:submit="submitReview">
                            @if ($this->needsReReview)
                                <div class="shadow-sm mb-4 alert alert-important alert-warning" role="alert">
                                    <div class="d-flex">
                                        <div><x-lucide-refresh-cw class="me-2 alert-icon" /></div>
                                        <div>
                                            <h4 class="alert-title">Review Ulang Dibutuhkan</h4>
                                            <div class="text-secondary">Proposal ini telah direvisi oleh pengusul.
                                                Silakan
                                                periksa perubahan dan berikan penilaian baru.</div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Penilaian Scoring --}}
                            <div class="mb-4">
                                <label class="mb-3 form-label h4 fw-bold">
                                    Penilaian Substansi <span class="text-danger">*</span>
                                </label>
                                <div class="table-responsive shadow-sm border rounded-3 overflow-hidden">
                                    <table class="card-table table table-nowrap table-vcenter mb-0">
                                        <thead class="bg-surface-secondary">
                                            <tr>
                                                <th class="w-1">No</th>
                                                <th>Kriteria & Acuan Penilaian</th>
                                                <th class="w-1 text-center">Bobot (%)</th>
                                                <th class="w-25">Input Acuan / Catatan <span
                                                        class="text-danger">*</span></th>
                                                <th class="w-1 text-center">Skor (1-5) <span
                                                        class="text-danger">*</span></th>
                                                <th class="w-1 text-end">Nilai</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($this->activeCriterias as $criteria)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td class="text-wrap">
                                                        <div class="fw-bold">{{ $criteria->criteria }}</div>
                                                        <div class="text-muted small">{{ $criteria->description }}
                                                        </div>
                                                    </td>
                                                    <td class="font-monospace text-center">{{ $criteria->weight }}%
                                                    </td>
                                                    <td>
                                                        <textarea wire:model="scores.{{ $criteria->id }}.acuan"
                                                            class="form-control form-control-sm @error('scores.' . $criteria->id . '.acuan') is-invalid @enderror"
                                                            rows="2" placeholder="Input acuan kriteria ini..."></textarea>
                                                        @error('scores.' . $criteria->id . '.acuan')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </td>
                                                    <td>
                                                        <select wire:model.live="scores.{{ $criteria->id }}.score"
                                                            class="form-select form-select-sm @error('scores.' . $criteria->id . '.score') is-invalid @enderror">
                                                            <option value="">Pilih Skor</option>
                                                            <option value="1">1 (Sangat Kurang)</option>
                                                            <option value="2">2 (Kurang)</option>
                                                            <option value="3">3 (Cukup Baik)</option>
                                                            <option value="4">4 (Baik)</option>
                                                            <option value="5">5 (Sangat Baik)</option>
                                                        </select>
                                                        @error('scores.' . $criteria->id . '.score')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </td>
                                                    <td class="font-monospace text-end fw-bold">
                                                        @php
                                                            $score = $scores[$criteria->id]['score'] ?? 0;
                                                            $val = is_numeric($score) ? $score * $criteria->weight : 0;
                                                        @endphp
                                                        {{ number_format($val, 0) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                            <tr class="bg-surface-secondary">
                                                <td colspan="2" class="text-end fw-bold h4">TOTAL NILAI:</td>
                                                <td class="font-monospace text-center fw-bold h4">
                                                    {{ number_format($this->activeCriterias->sum('weight'), 0) }}%</td>
                                                <td></td>
                                                <td class="font-monospace text-center fw-bold h4">
                                                    @php
                                                        $totalRawScore = 0;
                                                        foreach ($this->activeCriterias as $c) {
                                                            $totalRawScore += (int) ($scores[$c->id]['score'] ?? 0);
                                                        }
                                                    @endphp
                                                    {{ $totalRawScore }}
                                                </td>
                                                <td class="font-monospace text-primary text-end fw-bold h4">
                                                    {{ number_format($this->totalScore, 0) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-2 text-secondary small">
                                    <x-lucide-info class="icon-inline me-1 icon" />
                                    Total nilai dihitung otomatis: (Skor × Bobot). Passing Grade: 300.
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="mb-2 form-label h4 fw-bold" for="reviewNotes">
                                    Catatan Review Keseluruhan <span class="text-danger">*</span>
                                </label>
                                <div class="mb-2 text-secondary small">Berikan feedback final yang konstruktif dan
                                    jelas untuk
                                    pengusul. Minimal 10 karakter.</div>
                                <textarea wire:model="reviewNotes" id="reviewNotes"
                                    class="form-control @error('reviewNotes') is-invalid @enderror shadow-sm" rows="5"
                                    placeholder="Masukkan catatan detail review proposal..." required></textarea>
                                @error('reviewNotes')
                                    <div class="d-block invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label class="mb-2 form-label h4 fw-bold" for="recommendation">
                                    Rekomendasi Keputusan <span class="text-danger">*</span>
                                </label>
                                <div class="row g-2">
                                    @foreach ([
        'approved' => ['label' => 'Disetujui', 'color' => 'success', 'icon' => 'check-circle'],
        'revision_needed' => ['label' => 'Butuh Revisi', 'color' => 'warning', 'icon' => 'refresh-cw'],
        'rejected' => ['label' => 'Ditolak', 'color' => 'danger', 'icon' => 'x-circle'],
    ] as $value => $meta)
                                        <div class="col-md-4">
                                            <label class="w-100 form-selectgroup-item">
                                                <input type="radio" wire:model="recommendation"
                                                    value="{{ $value }}" class="form-selectgroup-input">
                                                <div class="d-flex align-items-center p-3 form-selectgroup-label">
                                                    <x-dynamic-component :component="'lucide-' . $meta['icon']"
                                                        class="icon text-{{ $meta['color'] }} me-3" />
                                                    <div class="text-start">
                                                        <div class="font-weight-medium">{{ $meta['label'] }}</div>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                @error('recommendation')
                                    <div class="d-block mt-2 invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex align-items-center justify-content-between mt-4">
                                <div class="text-muted small">
                                    <x-lucide-info class="me-1 icon" />
                                    Review Anda akan dapat dilihat oleh Admin dan Kepala LPPM.
                                </div>
                                <div class="btn-list">
                                    <button type="button" class="btn btn-link link-secondary"
                                        wire:click="toggleForm">Batal</button>
                                    <button type="submit" class="shadow-sm px-4 btn btn-primary"
                                        wire:loading.attr="disabled">
                                        <span wire:loading class="me-2 spinner-border spinner-border-sm"></span>
                                        <x-lucide-send class="me-1 icon" wire:loading.remove />
                                        {{ $this->hasReviewed ? 'Simpan Perubahan' : 'Kirim Review Sekarang' }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        @endif
    @endif
</div>
