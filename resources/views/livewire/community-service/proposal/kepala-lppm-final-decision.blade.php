<div>
    @if ($this->canDecide)
        <div class="alert alert-purple" role="alert">
            <x-lucide-refresh-cw class="icon" />
            <div>
                <h4 class="alert-heading">
                    Keputusan Final &mdash; Revisi Telah Diajukan
                </h4>
                <div class="alert-description">
                    Pengusul telah mengajukan revisi proposal. Silakan tinjau dan berikan keputusan akhir (Setujui / Perbaikan Lanjutan / Tolak).
                </div>
            </div>
        </div>

        <div class="btn-list">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#finalDecisionModal"
                wire:click="$set('decision', 'completed')">
                <x-lucide-check class="icon" />
                Setujui Proposal
            </button>
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#finalDecisionModal"
                wire:click="$set('decision', 'revision_needed')">
                <x-lucide-file-edit class="icon" />
                Minta Perbaikan Lanjutan
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
                            <h3>Minta Perbaikan Lanjutan?</h3>
                            <div class="text-secondary">
                                Proposal akan dikembalikan ke pengusul untuk melakukan perbaikan lanjutan.
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
                            <button type="button" class="w-100 btn btn-white" data-bs-toggle="modal">
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
