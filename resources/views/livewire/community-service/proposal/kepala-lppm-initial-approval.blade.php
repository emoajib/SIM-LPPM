<div>
    @if ($this->canApprove)
        <div class="alert alert-info" role="alert">
            
            <div>
                <h4 class="alert-heading">
                    Persetujuan Awal Kepala LPPM
                </h4>
                <div class="alert-description">
                    Proposal telah disetujui oleh Dekan. Silakan tinjau dan setujui untuk melanjutkan ke tahap penugasan
                    reviewer.
                </div>
            </div>
        </div>

        <div class="btn-list">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#initialApprovalModal">
                
                Setujui & Lanjutkan ke Reviewer
            </button>
        </div>
    @endif

    <x-tabler.alert />

    <!-- Approval Confirmation Modal -->
    @teleport('body')
        <x-tabler.modal id="initialApprovalModal" title="Konfirmasi Persetujuan Awal">
            <x-slot:body>
                <div class="py-3 text-center">
                    
                    <h3>Setujui Proposal?</h3>
                    <div class="mt-2 text-secondary">
                        Dengan menyetujui proposal ini, Admin LPPM akan dapat menugaskan reviewer untuk melakukan penilaian.
                    </div>
                    <div class="mt-3 alert alert-info">
                        <div>
                            <h4 class="alert-heading">Catatan:</h4>
                            <div class="alert-description">Setelah reviewer selesai melakukan review, Anda akan diminta
                                untuk
                                memberikan keputusan akhir.</div>
                        </div>
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
                            <button type="button" wire:click="approve" class="w-100 btn btn-primary"
                                data-bs-dismiss="modal">
                                
                                Ya, Setujui
                            </button>
                        </div>
                    </div>
                </div>
            </x-slot:footer>
        </x-tabler.modal>
    @endteleport
</div>
