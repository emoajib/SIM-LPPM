<div>
    <!-- Reviewer Assignment (Admin Only) -->
    @if (
            auth()->user()->hasRole(['admin lppm']) &&
            in_array($proposal->status, [\App\Enums\ProposalStatus::WAITING_REVIEWER, \App\Enums\ProposalStatus::UNDER_REVIEW])
        )
        <div class="mb-3">
            <livewire:community-service.proposal.reviewer-assignment :proposalId="$proposal->id"
                :key="'reviewer-assignment-' . $proposal->id" />
        </div>
    @endif

    <!-- Reviewer Form -->
    <div class="mb-3">
        <livewire:community-service.proposal.reviewer-form :proposalId="$proposal->id" :key="'reviewer-form-' . $proposal->id" />
    </div>

    <!-- Dekan Approval (Status: SUBMITTED) -->
    @if (auth()->user()->hasRole(['dekan']) && $proposal->status === \App\Enums\ProposalStatus::SUBMITTED)
        <div class="mb-3 card">
            <div class="card-header">
                <h3 class="card-title">Persetujuan Dekan</h3>
            </div>
            <div class="card-body">
                @php
                    $isKaprodiValidationRequired = \App\Models\Setting::get('feature_kaprodi_validation', false);
                    $isKaprodiValidated = $proposal->is_roadmap_validated_by_kaprodi;
                    $isApprovalDisabled = $isKaprodiValidationRequired && !$isKaprodiValidated;
                @endphp

                @if ($isApprovalDisabled)
                    <div class="alert alert-warning mb-3">
                        <div class="d-flex">
                            <div><x-lucide-alert-triangle class="icon alert-icon" /></div>
                            <div>
                                <h4 class="alert-title">Menunggu Validasi Prodi</h4>
                                <div class="text-secondary">Persetujuan Dekan terkunci karena proposal ini belum divalidasi kesesuaian roadmap/pohon penelitiannya oleh Kepala Program Studi.</div>
                            </div>
                        </div>
                    </div>
                @else
                    <p class="mb-3 text-secondary">
                        Silakan tinjau proposal ini dan berikan keputusan Anda sebagai Dekan.
                    </p>
                @endif
                <div class="gap-2 btn-list">
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approvalModal"
                        wire:click="$set('approvalDecision', 'approved')" @if($isApprovalDisabled) disabled @endif>
                        <x-lucide-check class="icon" />
                        Setujui Proposal
                    </button>
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#approvalModal"
                        wire:click="$set('approvalDecision', 'need_fix')">
                        <x-lucide-alert-triangle class="icon" />
                        Perlu Perbaikan
                    </button>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#approvalModal"
                        wire:click="$set('approvalDecision', 'rejected')">
                        <x-lucide-x class="icon" />
                        Tolak Proposal
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Kepala LPPM Initial Approval -->
    @if (auth()->user()->hasRole(['kepala lppm']) && $proposal->status === \App\Enums\ProposalStatus::APPROVED)
        <div class="mb-3">
            <livewire:community-service.proposal.kepala-lppm-initial-approval :proposalId="$proposal->id"
                :key="'initial-approval-' . $proposal->id" />
        </div>
    @endif

    <!-- Kepala LPPM Final Decision -->
    @if (auth()->user()->hasRole(['kepala lppm']) && $proposal->status === \App\Enums\ProposalStatus::REVIEWED)
        <div class="mb-3">
            <livewire:community-service.proposal.kepala-lppm-final-decision :proposalId="$proposal->id"
                :key="'final-decision-' . $proposal->id" />
        </div>
    @endif


    <div class="row g-3">
        <div class="col-md-4">
            <!-- Status & Actions Card -->
            <div class="mb-3 h-100 card">
                <div class="card-header">
                    <h3 class="card-title">Status & Aksi</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status Saat Ini</label>
                        <p>
                            <x-tabler.badge :color="$proposal->status->color()" class="fw-normal">
                                {{ $proposal->status->label() }}
                            </x-tabler.badge>
                        </p>
                    </div>

                    @if (in_array($proposal->status, [\App\Enums\ProposalStatus::DRAFT, \App\Enums\ProposalStatus::REVISION_NEEDED, \App\Enums\ProposalStatus::NEED_ASSIGNMENT]))
                        <!-- Vetted by AI - Manual Review Required by Senior Engineer/Manager -->
                        <livewire:community-service.proposal.submit-button :proposalId="$proposal->id"
                            :key="'submit-button-' . $proposal->id" />
                    @endif

                    {{-- Accept/Reject for team members --}}
                    @php
                        $currentMember = $proposal->teamMembers->firstWhere('id', auth()->id());
                    @endphp
                    @if ($currentMember && $currentMember->pivot->status === 'pending')
                        <div class="d-flex gap-2 mb-3">
                            <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                data-bs-target="#acceptMemberModal">
                                <x-lucide-check class="icon" />
                                Terima Undangan
                            </button>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                                data-bs-target="#rejectMemberModal">
                                <x-lucide-x class="icon" />
                                Tolak Undangan
                            </button>
                        </div>
                    @endif

                    @if ($this->canDelete)
                        <button type="button" class="btn-outline-danger btn" data-bs-toggle="modal"
                            data-bs-target="#deleteModal">
                            <x-lucide-trash-2 class="icon" />
                            Hapus
                        </button>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <!-- Timeline Card -->
            <div class="mb-3 h-100 card">
                <div class="card-header">
                    <h4 class="mb-0 card-title">Status Proposal</h4>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Dibuat Pada</label>
                        <p>{{ $proposal->created_at->format('d M Y H:i') }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Terakhir Diperbarui</label>
                        <p>{{ $proposal->updated_at->format('d M Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <!-- Review Status Card -->
            <div class="mb-3 h-100 card">
                <div class="card-header">
                    <h4 class="mb-0 card-title">Review Status</h4>
                </div>
                @php $reviewers = $proposal->reviewers; @endphp
                @if ($reviewers->isEmpty())
                    <p class="text-muted">Belum ada reviewer yang ditugaskan</p>
                @else
                    <div class="table-responsive">
                        <table class="card-table table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Reviewer</th>
                                    <th>Status</th>
                                    <th>Tanggal Review</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($reviewers as $reviewer)
                                    <tr>
                                        <td>{{ $reviewer->user?->name ?? '-' }}</td>
                                        <td>
                                            <x-tabler.badge :color="$reviewer->status->color()">
                                                {{ $reviewer->status->label() }}
                                            </x-tabler.badge>
                                        </td>
                                        <td>{{ $reviewer->updated_at?->format('d M Y H:i') ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>