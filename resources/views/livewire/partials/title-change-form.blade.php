{{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
<div class="card mb-3 border-primary-subtle shadow-sm">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">
            <x-lucide-edit-3 class="icon me-2 text-primary" /> Pengajuan Perubahan Judul (Opsional)
        </h3>
        @if ($progressReport && $progressReport->title_change_status)
            @if ($progressReport->title_change_status === 'pending')
                <span class="badge bg-warning text-dark">
                    <x-lucide-clock class="icon icon-sm me-1" /> Menunggu Persetujuan LPPM
                </span>
            @elseif ($progressReport->title_change_status === 'approved')
                <span class="badge bg-success">
                    <x-lucide-check-circle class="icon icon-sm me-1" /> Perubahan Judul Disetujui
                </span>
            @elseif ($progressReport->title_change_status === 'rejected')
                <span class="badge bg-danger">
                    <x-lucide-x-circle class="icon icon-sm me-1" /> Pengajuan Ditolak
                </span>
            @endif
        @endif
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label text-muted mb-1">Judul Terdaftar Saat Ini (Kontrak/SK):</label>
            <div class="p-2 bg-light rounded border font-weight-bold text-dark">
                {{ $proposal->title }}
            </div>
        </div>

        @if ($progressReport && $progressReport->title_change_status === 'pending')
            <div class="alert alert-info d-flex align-items-start mb-3">
                <x-lucide-info class="icon me-2 mt-1 flex-shrink-0" />
                <div class="flex-grow-1">
                    <strong>Pengajuan Perubahan Judul Sedang Ditinjau:</strong>
                    <div class="mt-1"><strong>Judul Baru:</strong> {{ $progressReport->proposed_title }}</div>
                    <div class="mt-1"><strong>Alasan/Justifikasi:</strong> {{ $progressReport->title_change_reason }}</div>
                </div>
            </div>
        @elseif ($progressReport && $progressReport->title_change_status === 'approved')
            <div class="alert alert-success d-flex align-items-center mb-3">
                <x-lucide-check-circle-2 class="icon me-2 text-success" />
                <div>
                    <strong>Perubahan judul telah disetujui LPPM pada {{ $progressReport->title_change_reviewed_at?->format('d/m/Y H:i') }}.</strong>
                    @if ($progressReport->title_change_review_notes)
                        <div class="small text-muted mt-1">Catatan LPPM: {{ $progressReport->title_change_review_notes }}</div>
                    @endif
                </div>
            </div>
        @elseif ($progressReport && $progressReport->title_change_status === 'rejected')
            <div class="alert alert-danger d-flex align-items-start mb-3">
                <x-lucide-alert-triangle class="icon me-2 text-danger mt-1" />
                <div>
                    <strong>Pengajuan perubahan judul ditolak oleh LPPM pada {{ $progressReport->title_change_reviewed_at?->format('d/m/Y H:i') }}.</strong>
                    <div class="small mt-1"><strong>Alasan Penolakan:</strong> {{ $progressReport->title_change_review_notes ?? '-' }}</div>
                </div>
            </div>
        @endif

        {{-- Admin LPPM Approval Action Box --}}
        @if (auth()->user()?->activeHasAnyRole(['admin lppm', 'admin lppm saintek', 'admin lppm dekabita', 'kepala lppm', 'superadmin']) && $progressReport && $progressReport->title_change_status === 'pending')
            <div class="border border-warning rounded p-3 bg-warning-lt mb-3">
                <h4 class="text-warning-emphasis mb-2 d-flex align-items-center">
                    <x-lucide-shield-alert class="icon me-2" /> Panel Verifikasi Perubahan Judul (Admin LPPM)
                </h4>
                <p class="small text-muted mb-2">
                    Dosen pengusul mengajukan perubahan judul laporan akhir. Tinjau justifikasi di atas sebelum menyetujui atau menolak.
                </p>
                <div class="mb-3">
                    <label class="form-label small">Catatan Review / Alasan Penolakan (Opsional jika disetujui, Wajib jika ditolak):</label>
                    <textarea wire:model="titleChangeReviewNotes" class="form-control form-control-sm @error('titleChangeReviewNotes') is-invalid @enderror" rows="2" placeholder="Tuliskan catatan verifikasi..."></textarea>
                    @error('titleChangeReviewNotes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="d-flex gap-2">
                    <button type="button" wire:click="approveTitleChange" wire:loading.attr="disabled" class="btn btn-success btn-sm">
                        <x-lucide-check class="icon me-1" /> Setujui Perubahan Judul
                    </button>
                    <button type="button" wire:click="rejectTitleChange" wire:loading.attr="disabled" class="btn btn-outline-danger btn-sm">
                        <x-lucide-x class="icon me-1" /> Tolak Pengajuan
                    </button>
                </div>
            </div>
        @endif

        {{-- Lecturer Edit Form --}}
        @if ($canEdit)
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="toggleTitleChange" wire:model.live="isRequestingTitleChange">
                <label class="form-check-label font-weight-bold" for="toggleTitleChange">
                    Ajukan Perubahan / Penyesuaian Judul pada Laporan Akhir
                </label>
            </div>

            @if ($isRequestingTitleChange)
                <div class="p-3 bg-light rounded border">
                    <div class="mb-3">
                        <label class="form-label required">Judul Baru yang Diajukan</label>
                        <input type="text" wire:model="proposedTitle" class="form-control @error('proposedTitle') is-invalid @enderror" placeholder="Ketikkan judul baru yang disesuaikan...">
                        @error('proposedTitle')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-hint">Maksimal 500 karakter. Sesuaikan dengan fokus dan hasil laporan akhir.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Alasan / Justifikasi Perubahan Judul</label>
                        <textarea wire:model="titleChangeReason" rows="3" class="form-control @error('titleChangeReason') is-invalid @enderror" placeholder="Jelaskan alasan mengapa judul disesuaikan dari usulan awal..."></textarea>
                        @error('titleChangeReason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-hint">Jelaskan latar belakang, temuan lapangan, atau kesepakatan penyesuaian judul.</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" wire:click="saveTitleChangeRequest" wire:loading.attr="disabled" class="btn btn-primary btn-sm">
                            <x-lucide-send class="icon me-1" /> Kirim Pengajuan Perubahan Judul
                        </button>
                        @if ($progressReport && $progressReport->title_change_status === 'pending')
                            <button type="button" wire:click="cancelTitleChangeRequest" wire:loading.attr="disabled" class="btn btn-outline-secondary btn-sm">
                                Batalkan Pengajuan
                            </button>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
