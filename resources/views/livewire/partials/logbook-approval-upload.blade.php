{{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
@if ($this->logbookApprovalMode === 'upload' || $this->logbookApprovalMode === 'both')
    <div class="card mb-3 border-azure-subtle shadow-sm">
        <div class="card-header bg-azure-lt d-flex justify-content-between align-items-center py-2">
            <h4 class="card-title mb-0 text-azure d-flex align-items-center">
                <x-lucide-file-signature class="icon me-2" />
                Lembar Pengesahan Catatan Harian & Keuangan (Tanda Tangan & Cap Basah)
            </h4>
            @if ($proposal->hasMedia('logbook_approval_file'))
                <span class="badge bg-success">
                    <x-lucide-check-circle class="icon icon-sm me-1" /> Berkas Terunggah
                </span>
            @else
                <span class="badge bg-warning text-dark">
                    <x-lucide-alert-circle class="icon icon-sm me-1" /> Belum Diunggah
                </span>
            @endif
        </div>
        <div class="card-body">
            <p class="text-secondary small mb-3">
                Unduh lembar rekapitulasi pengesahan di bawah ini untuk dibubuhi <strong>tanda tangan basah Ketua Pengusul dan tanda tangan serta cap basah Kepala LPPM</strong>. Setelah selesai, scan dokumen dalam format PDF dan unggah kembali di sini.
            </p>

            <div class="row g-3 align-items-center">
                <div class="col-md-5">
                    <label class="form-label font-weight-bold small mb-1">Langkah 1: Unduh Lembar Pengesahan</label>
                    <div>
                        <a data-navigate-ignore="true" href="{{ route('financial-reports.export-pdf', ['proposal' => $proposal, 'download' => 'true']) }}" target="_blank" class="btn btn-outline-success btn-sm w-100">
                            <x-lucide-download class="icon icon-sm me-1" /> Unduh Laporan Keuangan (LPJ)
                        </a>
                    </div>
                </div>

                <div class="col-md-7 border-start-md">
                    <label class="form-label font-weight-bold small mb-1">Langkah 2: Unggah Scan Lembar Basah (PDF, Maks. 10MB)</label>
                    @if ($proposal->hasMedia('logbook_approval_file'))
                        @php $media = $proposal->getFirstMedia('logbook_approval_file'); @endphp
                        <div class="alert alert-success d-flex align-items-center justify-content-between p-2 mb-0">
                            <div class="d-flex align-items-center text-truncate me-2">
                                <x-lucide-file-check class="icon text-success me-2 flex-shrink-0" />
                                <div class="text-truncate">
                                    <strong class="d-block text-truncate">{{ $media->name }}</strong>
                                    <small class="text-muted">({{ $media->human_readable_size }})</small>
                                </div>
                            </div>
                            <div class="btn-group btn-group-sm flex-shrink-0">
                                <a data-navigate-ignore="true" href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(10), ['media' => $media]) }}" target="_blank" class="btn btn-sm btn-primary">
                                    <x-lucide-eye class="icon icon-sm" /> Lihat
                                </a>
                                @if ($this->canManage($proposal))
                                    <button type="button" wire:click="removeLogbookApprovalFile" class="btn btn-sm btn-danger" wire:confirm="Yakin ingin menghapus berkas lembar pengesahan ini?">
                                        <x-lucide-trash-2 class="icon icon-sm" /> Hapus
                                    </button>
                                @endif
                            </div>
                        </div>
                    @elseif ($this->canManage($proposal))
                        <form wire:submit.prevent="saveLogbookApprovalFile" class="d-flex gap-2">
                            <div class="flex-grow-1">
                                <input type="file" wire:model="logbookApprovalFile" class="form-control form-control-sm @error('logbookApprovalFile') is-invalid @enderror" accept=".pdf">
                                @error('logbookApprovalFile')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" wire:loading.attr="disabled" class="btn btn-primary btn-sm flex-shrink-0">
                                <span wire:loading.remove wire:target="logbookApprovalFile,saveLogbookApprovalFile"><x-lucide-upload class="icon icon-sm me-1" /> Unggah</span>
                                <span wire:loading wire:target="logbookApprovalFile,saveLogbookApprovalFile"><span class="spinner-border spinner-border-sm me-1"></span> Mengunggah...</span>
                            </button>
                        </form>
                    @else
                        <div class="text-muted small italic">Belum ada berkas scan pengesahan basah yang diunggah.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif
