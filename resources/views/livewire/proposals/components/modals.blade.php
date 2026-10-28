<div>
    <!-- Delete Modal -->
    @if ($this->canDelete)
        @teleport('body')
            <x-tabler.modal id="deleteModal" title="Hapus Proposal?" wire:ignore.self>
                <x-slot:body>
                    <div class="py-1 text-center">
                        
                        <h3 class="mt-2">Hapus Proposal?</h3>
                        <div class="text-secondary">
                            Apakah Anda yakin ingin menghapus proposal ini? Tindakan ini tidak dapat dibatalkan.
                        </div>
                    </div>
                </x-slot:body>

                <x-slot:footer>
                    <button type="button" class="btn-outline-secondary btn" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="button" wire:click="delete" class="btn btn-danger" data-bs-dismiss="modal">
                        Ya, Hapus Proposal
                    </button>
                </x-slot:footer>
            </x-tabler.modal>
        @endteleport
    @endif

    <!-- Accept Member Confirmation Modal -->
    @teleport('body')
        <x-tabler.modal id="acceptMemberModal" title="Terima Undangan?" wire:ignore.self>
            <x-slot:body>
                <div class="py-1 text-center">
                        
                        <h3 class="mt-2">Terima Undangan?</h3>
                        <div class="text-secondary">
                            Apakah Anda yakin ingin menerima undangan sebagai anggota tim proposal ini?
                        </div>
                </div>
            </x-slot:body>

            <x-slot:footer>
                <button type="button" class="btn-outline-secondary btn" data-bs-dismiss="modal">
                    Batal
                </button>
                <button type="button" wire:click="acceptMember" class="btn btn-success" data-bs-dismiss="modal"
                    onclick="setTimeout(() => window.location.reload(), 3000)">
                    Ya, Terima
                </button>
            </x-slot:footer>
        </x-tabler.modal>
    @endteleport

    <!-- Reject Member Confirmation Modal -->
    @teleport('body')
        <x-tabler.modal id="rejectMemberModal" title="Tolak Undangan?" wire:ignore.self>
            <x-slot:body>
                <div class="py-1 text-center">
                        
                        <h3 class="mt-2">Tolak Undangan?</h3>
                        <div class="text-secondary">
                            Apakah Anda yakin ingin menolak undangan sebagai anggota tim proposal ini?
                        </div>
                </div>
            </x-slot:body>

            <x-slot:footer>
                <button type="button" class="btn-outline-secondary btn" data-bs-dismiss="modal">
                    Batal
                </button>
                <button type="button" wire:click="rejectMember" class="btn btn-danger" data-bs-dismiss="modal"
                    onclick="setTimeout(() => window.location.reload(), 3000)">
                    Ya, Tolak
                </button>
            </x-slot:footer>
        </x-tabler.modal>
    @endteleport

    <!-- Approval Modal (Dekan Decision) -->
    @teleport('body')
        <x-tabler.modal id="approvalModal" title="Keputusan Dekan" wire:ignore.self>
            <x-slot:body>
                <div class="py-1">
                    <div class="mb-3 text-center">
                        @if ($approvalDecision === 'approved')
                            
                            <h3>Setujui Proposal?</h3>
                        @elseif($approvalDecision === 'need_fix')
                            
                            <h3>Perlu Perbaikan?</h3>
                        @elseif($approvalDecision === 'rejected')
                            
                            <h3>Tolak Proposal?</h3>
                        @endif
                        <div class="text-secondary">
                            Apakah Anda yakin dengan keputusan ini?
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan (Opsional)</label>
                        <textarea class="form-control" wire:model="approvalNotes" rows="3"
                            placeholder="Masukkan catatan atau alasan jika ada..."></textarea>
                    </div>
                </div>
            </x-slot:body>

            <x-slot:footer>
                <button type="button" class="btn-outline-secondary btn" data-bs-dismiss="modal">
                    Batal
                </button>
                <button type="button" wire:click="submitDekanDecision" class="btn btn-primary" data-bs-dismiss="modal">
                    Ya, Konfirmasi
                </button>
            </x-slot:footer>
        </x-tabler.modal>
    @endteleport
</div>
