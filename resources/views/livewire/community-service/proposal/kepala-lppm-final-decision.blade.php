<div>
    @if ($this->canDecide)
        @php $isRevisionReview = $this->proposal->status === \App\Enums\ProposalStatus::REVISION_SUBMITTED; @endphp

        <div class="alert alert-{{ $isRevisionReview ? 'purple' : ($this->isInitialReviewedStage ? 'warning' : 'info') }}" role="alert">
            <x-lucide-{{ $isRevisionReview ? 'refresh-cw' : ($this->isInitialReviewedStage ? 'clipboard-list' : 'clipboard-check') }} class="icon" />
            <div>
                <h4 class="alert-heading">
                    @if ($isRevisionReview)
                        Keputusan Final &mdash; Revisi Telah Diajukan
                    @elseif ($this->isInitialReviewedStage)
                        Analisis Hasil Review &mdash; Kembalikan ke Perbaikan
                    @else
                        Keputusan Akhir Kepala LPPM
                    @endif
                </h4>
                <div class="alert-description">
                    @if ($isRevisionReview)
                        Pengusul telah mengajukan revisi proposal. Silakan tinjau dan berikan keputusan akhir (Setujui / Perbaikan Lanjutan / Tolak).
                    @elseif ($this->isInitialReviewedStage)
                        Semua reviewer telah menyelesaikan penilaian. <strong>Seluruh proposal wajib melalui tahap Perbaikan Usulan</strong> agar dosen dapat membaca dan merespons catatan reviewer sebelum keputusan akhir dibuat.
                        <br>
                        <small class="mt-1 d-block text-muted">Tombol "Setujui" akan tersedia setelah dosen mengajukan ulang dari halaman Perbaikan Usulan.</small>
                    @else
                        Semua reviewer telah menyelesaikan review. Silakan berikan keputusan akhir untuk proposal ini.
                    @endif
                </div>
            </div>
        </div>

        <div class="btn-list">
            @if (! $this->isInitialReviewedStage)
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#finalDecisionModal"
                    wire:click="$set('decision', 'completed')">
                    <x-lucide-check class="icon" />
                    Setujui Proposal
                </button>
            @endif
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#finalDecisionModal"
                wire:click="$set('decision', 'revision_needed')">
                <x-lucide-file-edit class="icon" />
                {{ $this->isInitialReviewedStage ? 'Kembalikan ke Perbaikan Usulan' : 'Minta Perbaikan Lanjutan' }}
            </button>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#finalDecisionModal"
                wire:click="$set('decision', 'rejected')">
                <x-lucide-x-circle class="icon" />
                Tolak Proposal
            </button>
        </div>
    @elseif ($this->pendingReviewers->count() > 0)
        <div class="alert alert-warning" role="alert">
            <strong>Menunggu Review:</strong> {{ $this->pendingReviewers->count() }} reviewer belum menyelesaikan review
        </div>
    @else
        {{-- <div class="alert alert-info" role="alert">
            Proposal tidak dapat diputuskan saat ini
        </div> --}}
    @endif

    <!-- Decision Confirmation Modal -->
    @teleport('body')
        <x-tabler.modal id="finalDecisionModal" title="Konfirmasi Keputusan Akhir">
            <x-slot:body>
                <div class="py-3">
                    @if ($decision === 'completed')
                        <div class="mb-3 text-center">
                            <x-lucide-check-circle class="mb-2 text-success icon" style="width: 3rem; height: 3rem;" />
                            <h3>Setujui Proposal?</h3>
                            <div class="text-secondary">
                                Proposal akan disetujui dan statusnya akan berubah menjadi <strong>Selesai</strong>.
                            </div>
                        </div>
                    @elseif ($decision === 'rejected')
                        <div class="mb-3 text-center">
                            <x-lucide-x-circle class="mb-2 text-danger icon" style="width: 3rem; height: 3rem;" />
                            <h3>Tolak Proposal?</h3>
                            <div class="text-secondary">
                                Proposal akan <strong>ditolak secara permanen</strong>. Tindakan ini tidak dapat dibatalkan.
                            </div>
                        </div>
                    @else
                        <div class="mb-3 text-center">
                            <x-lucide-file-edit class="mb-2 text-warning icon" style="width: 3rem; height: 3rem;" />
                            <h3>@php $isRevisionReview = $this->proposal?->status === \App\Enums\ProposalStatus::REVISION_SUBMITTED; @endphp
                                {{ $isRevisionReview ? 'Minta Perbaikan Ulang?' : 'Minta Perbaikan Usulan?' }}</h3>
                            <div class="text-secondary">
                                @if ($isRevisionReview)
                                    Proposal akan dikembalikan ke pengusul untuk melakukan perbaikan lanjutan.
                                @else
                                    Proposal akan dikembalikan ke pengusul untuk melakukan perbaikan sesuai dengan catatan
                                    yang Anda berikan.
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">
                            Catatan
                            @if ($decision === 'revision_needed')
                                <span class="text-danger">*</span> <span class="text-muted small">(Wajib)</span>
                            @elseif ($decision === 'rejected')
                                <span class="text-danger">*</span> <span class="text-muted small">(Wajib)</span>
                            @else
                                <span class="text-muted small">(Opsional)</span>
                            @endif
                        </label>
                        <textarea class="form-control" rows="4" wire:model="notes" placeholder="Tambahkan catatan atau komentar..."></textarea>
                        @if ($decision === 'revision_needed')
                            <small class="form-hint">
                                <strong>Wajib diisi.</strong> Jelaskan secara rinci perbaikan yang diperlukan agar dosen dapat melakukan revisi dengan tepat.
                            </small>
                        @elseif ($decision === 'rejected')
                            <small class="form-hint">
                                <strong>Wajib diisi.</strong> Jelaskan alasan penolakan proposal.
                            </small>
                        @endif
                    </div>
                </div>
            </x-slot:body>

            <x-slot:footer>
                <div class="w-100">
                    <div class="row">
                        <div class="col">
                            <button type="button" class="w-100 btn btn-white" data-bs-dismiss="modal">
                                Batal
                            </button>
                        </div>
                        <div class="col">
                            @php
                                $btnClass = match($decision) {
                                    'completed' => 'btn-success',
                                    'rejected' => 'btn-danger',
                                    default => 'btn-warning',
                                };
                            @endphp
                            <button type="button" wire:click="processDecision"
                                class="w-100 btn {{ $btnClass }}"
                                data-bs-dismiss="modal">
                                @if ($decision === 'completed')
                                    <x-lucide-check class="icon" />
                                    Ya, Setujui
                                @elseif ($decision === 'rejected')
                                    <x-lucide-x-circle class="icon" />
                                    Ya, Tolak
                                @else
                                    <x-lucide-file-edit class="icon" />
                                    Ya, Minta Perbaikan
                                @endif
                            </button>
                        </div>
                    </div>
                </div>
            </x-slot:footer>
        </x-tabler.modal>
    @endteleport
</div>
