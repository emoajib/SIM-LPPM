<div>
    @if ($this->canApprove)
        <div class="btn-list">
            <button wire:click="approve" class="btn btn-success" wire:confirm="Setujui proposal ini?">
                
                Setujui Proposal
            </button>
            <button wire:click="reject" class="btn btn-danger" wire:confirm="Tolak proposal ini?">
                
                Tolak Proposal
            </button>
        </div>
    @elseif ($this->pendingReviewers->count() > 0)
        <div class="d-inline-block alert alert-warning" role="alert">
            <strong>Menunggu Review:</strong> {{ $this->pendingReviewers->count() }} reviewer belum menyelesaikan review
        </div>
    @else
        <div class="d-inline-block alert alert-info" role="alert">
            Proposal tidak dapat diapprove saat ini
        </div>
    @endif

    @if ($this->reviewSummary->isNotEmpty())
        <div class="mt-3 card">
            <div class="card-header">
                <h3 class="card-title">Ringkasan Review</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    @if ($this->reviewSummary->get('approved'))
                        <div class="col-md-4">
                            <div class="stat">
                                <div class="stat-title">Disetujui</div>
                                <div class="text-success stat-value">{{ $this->reviewSummary->get('approved', 0) }}
                                </div>
                            </div>
                        </div>
                    @endif
                    @if ($this->reviewSummary->get('revision_needed'))
                        <div class="col-md-4">
                            <div class="stat">
                                <div class="stat-title">Butuh Revisi</div>
                                <div class="text-warning stat-value">
                                    {{ $this->reviewSummary->get('revision_needed', 0) }}</div>
                            </div>
                        </div>
                    @endif
                    @if ($this->reviewSummary->get('rejected'))
                        <div class="col-md-4">
                            <div class="stat">
                                <div class="stat-title">Ditolak</div>
                                <div class="text-danger stat-value">{{ $this->reviewSummary->get('rejected', 0) }}</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
