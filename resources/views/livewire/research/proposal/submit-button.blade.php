<div>
    @php
        $isRevision = $this->proposal->status === \App\Enums\ProposalStatus::REVISION_NEEDED;
        $buttonText = $isRevision ? 'Kirim Perbaikan' : 'Submit Proposal';
        $modalTitle = $isRevision ? 'Konfirmasi Pengajuan Perbaikan' : 'Konfirmasi Pengajuan Proposal';
        $modalText = $isRevision 
            ? 'Apakah Anda yakin ingin mengajukan perbaikan usulan ini ke Kepala LPPM? Pastikan semua revisi telah tersimpan.' 
            : 'Apakah Anda yakin ingin mengajukan proposal ini? Setelah diajukan, proposal tidak dapat diubah.';
    @endphp
    
    <button type="button" class="mb-3 w-full btn btn-success @if(!$this->canSubmit) disabled @endif"
        @if($this->canSubmit) wire:click="confirmSubmit" @endif>
        <x-lucide-send class="icon" />
        {{ $buttonText }}
    </button>

    @teleport('body')
        <x-tabler.modal id="confirmSubmitModal" title="{{ $modalTitle }}" component-id="{{ $this->getId() }}">
            <x-slot:body>
                <div class="py-4 text-center">
                    <x-lucide-send class="mb-2 text-success icon" style="width: 3rem; height: 3rem;" />
                    <h3>{{ $modalTitle }}</h3>
                    <div class="text-secondary">
                        {{ $modalText }}
                    </div>
                </div>
            </x-slot:body>

            <x-slot:footer>
                <div class="w-100">
                    <div class="row">
                        <div class="col">
                            <button type="button" class="w-100 btn btn-light" data-bs-dismiss="modal">
                                Batalkan
                            </button>
                        </div>
                        <div class="col">
                            <button type="button" wire:click="submit" class="w-100 btn btn-success"
                                data-bs-dismiss="modal">
                                Ya, {{ $buttonText }}
                            </button>
                        </div>
                    </div>
                </div>
            </x-slot:footer>
        </x-tabler.modal>
    @endteleport


    @if ($this->pendingMembers->count() > 0)
        <div class="d-inline-block alert alert-warning" role="alert">
            <strong>Menunggu Persetujuan:</strong> {{ $this->pendingMembers->count() }} anggota belum menerima undangan
        </div>
    @elseif (!$this->eligibility['eligible'])
        <div class="d-inline-block alert alert-warning" role="alert">
            <strong>Tidak Memenuhi Syarat:</strong> {{ implode(', ', $this->eligibility['reasons']) }}
        </div>
    @elseif ($this->rejectedMembers->count() > 0)
        <div class="d-inline-block alert alert-danger" role="alert">
            <strong>Ada yang Menolak:</strong> {{ $this->rejectedMembers->count() }} anggota menolak undangan. Silahkan
            hapus dan tambah anggota baru.
        </div>
    @else
        <div class="d-inline-block alert alert-info" role="alert">
            Proposal dapat disubmit oleh Author setelah semua anggota menerima undangan.
        </div>
    @endif
</div>
