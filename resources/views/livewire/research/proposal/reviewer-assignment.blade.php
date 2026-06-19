<div>
    <!-- Add Button -->
    <button type="button" class="mb-3 btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-assign-reviewer">
        <x-lucide-user-plus class="icon" />
        Tugaskan Reviewer
    </button>

    <!-- Assign Reviewer Modal -->
    @teleport('body')
        <x-tabler.modal id="modal-assign-reviewer" title="Tugaskan Reviewer" on-show="resetReviewerForm">
            <x-slot:body>
                <form wire:submit.prevent="assignReviewers" id="reviewer-assignment-form">
                    <div class="mb-3">
                        <label class="form-label" for="selectedReviewer">Pilih Reviewer <span
                                class="text-danger">*</span></label>
                        <div wire:ignore>
                            <select wire:model="selectedReviewer" class="form-select tom-select" id="selectedReviewer"
                                x-data="tomSelect" placeholder="Pilih reviewer..." required>
                                <option value="" selected disabled>Pilih reviewer...</option>
                                @foreach ($this->availableReviewers as $reviewer)
                                    <option wire:key="reviewer-{{ $reviewer->id }}" value="{{ $reviewer->id }}">
                                        {{ $reviewer->name }}
                                        ({{ $reviewer->identity?->identity_id ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('selectedReviewer')
                            <div class="d-block mt-2 text-danger">{{ $message }}</div>
                        @enderror
                        <small class="text-muted form-text">Pilih satu reviewer untuk ditugaskan</small>
                    </div>
                </form>
            </x-slot:body>

            <x-slot:footer>
                <button type="button" class="btn-outline-secondary btn" data-bs-dismiss="modal">
                    Batal
                </button>
                <button type="submit" form="reviewer-assignment-form" class="btn btn-primary" wire:loading.attr="disabled"
                    data-bs-dismiss="modal">
                    <x-lucide-send class="icon" />
                    <span wire:loading.remove>Tugaskan</span>
                    <span wire:loading>Menyimpan...</span>
                </button>
            </x-slot:footer>
        </x-tabler.modal>
    @endteleport

    @if ($this->currentReviewers->count() > 0)
        <div class="card">

            {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Reviewer yang Ditugaskan</h3>
                <div>
                    @if ($this->currentReviewers->count() < $this->requiredReviewerCount)
                        <span class="badge bg-warning-lt">
                            Penugasan: {{ $this->currentReviewers->count() }} dari {{ $this->requiredReviewerCount }} Reviewer Wajib
                        </span>
                    @else
                        <span class="badge bg-success-lt">
                            Penugasan: {{ $this->currentReviewers->count() }} dari {{ $this->requiredReviewerCount }} Reviewer Wajib (Lengkap)
                        </span>
                    @endif
                </div>
            </div>
            <div class="table-responsive">
                <table class="card-table table table-vcenter">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th class="w-1">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->currentReviewers as $reviewer)
                            <tr>
                                <td>{{ $reviewer->user->name }}</td>
                                <td>{{ $reviewer->user->email }}</td>
                                <td>
                                    <x-tabler.badge color="{{ $reviewer->status->color() }}">
                                        {{ $reviewer->status->label() }}
                                    </x-tabler.badge>
                                </td>
                                <td>
                                    <button type="button"
                                        wire:click="confirmRemoveReviewer('{{ $reviewer->user->id }}')"
                                        class="btn btn-icon btn-ghost-danger btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#deleteReviewerModal">
                                        <x-lucide-trash-2 class="icon" />
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif


    <!-- Delete Reviewer Confirmation Modal -->
    @teleport('body')
        <x-tabler.modal id="deleteReviewerModal" title="Hapus Reviewer?">
            <x-slot:body>
                <div class="py-4 text-center">
                    <x-lucide-alert-circle class="mb-2 text-danger icon" style="width: 3rem; height: 3rem;" />
                    <h3>Hapus Reviewer?</h3>
                    <div class="text-secondary">
                        Apakah Anda yakin ingin menghapus reviewer dari proposal ini?
                    </div>
                </div>
            </x-slot:body>

            <x-slot:footer>
                <button type="button" class="btn-outline-secondary btn" data-bs-dismiss="modal">
                    Batal
                </button>
                <button type="button" wire:click="removeReviewer('{{ $confirmingRemoveReviewerId }}')"
                    class="btn btn-danger" data-bs-dismiss="modal">
                    Ya, Hapus Reviewer
                </button>
            </x-slot:footer>
        </x-tabler.modal>
    @endteleport
</div>
