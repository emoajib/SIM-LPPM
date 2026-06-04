@if ($this->proposalApprovalMode === 'upload' || $this->proposalApprovalMode === 'both')
<!-- Section: Lembar Pengesahan -->
<div class="card mb-3 border-primary shadow-sm">
    <div class="card-status-top bg-primary"></div>
    <div class="card-body">
        <div class="d-flex align-items-center mb-3">
            <x-lucide-file-signature class="icon me-2 text-primary" />
            <h3 class="card-title mb-0">Lembar Pengesahan</h3>
        </div>

        <div class="row align-items-center">
            <div class="col-md-8">
                <p class="text-secondary mb-0">
                    Sesuai dengan pengaturan sistem (Metode: <strong>{{ strtoupper($this->proposalApprovalMode) }}</strong>),
                    Anda wajib mengunggah lembar pengesahan yang telah ditandatangani dan dicap basah oleh pejabat berwenang.
                </p>
                @if ($this->proposalApprovalMode === 'both')
                    <small class="text-info mt-1 d-block">
                        <x-lucide-info class="icon-inline me-1" />
                        Catatan: Sistem akan tetap menyertakan pengesahan digital secara otomatis.
                    </small>
                @endif
                <div class="mt-3">
                    <button type="button" wire:click="downloadProposalApprovalPageTemplate" class="btn btn-outline-primary btn-sm">
                        <x-lucide-download class="icon me-1" />
                        Unduh Template Halaman Persetujuan
                    </button>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <div class="mb-2">
                    <input type="file" wire:model="form.approval_file" class="form-control @error('form.approval_file') is-invalid @enderror" accept=".pdf">
                    @error('form.approval_file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <small class="text-muted">Format PDF, Maksimal 5MB</small>
            </div>
        </div>

        @if ($form->proposal && $form->proposal->detailable && $form->proposal->detailable->hasMedia('approval_file'))
            <div class="mt-3 p-2 bg-light border rounded d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <x-lucide-file-check class="icon text-success me-2" />
                    <div>
                        <div class="small font-weight-bold">File Tersimpan:</div>
                        <div class="small text-muted">{{ $form->proposal->detailable->getFirstMedia('approval_file')->file_name }}</div>
                    </div>
                </div>
                @php $media = $form->proposal->detailable->getFirstMedia('approval_file'); @endphp
                <a data-navigate-ignore="true" href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                    <x-lucide-external-link class="icon" />
                    Lihat File
                </a>
            </div>
        @elseif ($form->approval_file)
            <div class="mt-3 p-2 bg-light border rounded d-flex align-items-center">
                <x-lucide-file-up class="icon text-info me-2" />
                <div>
                    <div class="small font-weight-bold text-info">File Siap Diunggah (Draft):</div>
                    <div class="small text-muted">{{ is_object($form->approval_file) ? $form->approval_file->getClientOriginalName() : 'File terpilih' }}</div>
                </div>
            </div>
        @endif
    </div>
</div>
@endif

<!-- Section: Mitra Kerjasama -->
<div class="mb-3 card">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center">
                <x-lucide-handshake class="me-3 icon" />
                <h3 class="mb-0 card-title">Mitra Kerjasama</h3>
            </div>
            <div class="d-flex align-items-center">
                <button type="button" wire:click="downloadPartnerCommitmentTemplate" class="btn btn-link btn-sm me-2 text-decoration-none">
                    <x-lucide-download class="icon me-1" />
                    Unduh Template Surat Kesanggupan
                </button>
                <button type="button" class="btn btn-primary btn-sm" wire:click="$dispatch('open-modal', {modalId: 'modal-partner'})">
                    <x-lucide-plus class="icon" />
                    Tambah Mitra
                </button>
            </div>
        </div>

        @if (empty($form->partner_ids))
            <div class="alert alert-info">
                <x-lucide-info class="icon me-2" />
                Belum ada mitra yang ditambahkan.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>Nama Mitra</th>
                            <th>Institusi</th>
                            <th>Email</th>
                            <th>Negara</th>
                            <th>Alamat</th>
                            <th>MOU / PKS</th>
                            <th>Surat Kesediaan</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($form->partner_ids as $partnerId)
                            @php
                                $partner = $this->partners->find($partnerId);
                            @endphp
                            @if ($partner)
                                <tr wire:key="partner-{{ $partnerId }}">
                                    <td>
                                        <div class="font-weight-medium">{{ $partner->name }}</div>
                                    </td>
                                    <td>
                                        @if ($partner->institution)
                                            <div class="d-flex align-items-center">
                                                <x-lucide-building class="icon me-1 text-muted" />
                                                {{ $partner->institution }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($partner->email)
                                            <a href="mailto:{{ $partner->email }}" class="text-reset">
                                                <div class="d-flex align-items-center">
                                                    <x-lucide-mail class="icon me-1 text-muted" />
                                                    {{ $partner->email }}
                                                </div>
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($partner->country)
                                            <div class="d-flex align-items-center">
                                                <x-lucide-map-pin class="icon me-1 text-muted" />
                                                {{ $partner->country }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($partner->address)
                                            <div class="text-truncate" style="max-width: 200px;" title="{{ $partner->address }}">
                                                {{ $partner->address }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{-- MOU/PKS dari Admin --}}
                                        @if ($partner->hasMedia('mou_pks'))
                                            @php $media = $partner->getFirstMedia('mou_pks'); @endphp
                                            <a data-navigate-ignore="true" href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                               target="_blank"
                                               class="btn btn-sm btn-outline-primary" title="MOU/PKS">
                                                <x-lucide-file-text class="icon" />
                                                MOU/PKS
                                            </a>
                                        @else
                                            <span class="badge bg-yellow-lt text-yellow-fg" title="Belum ada MOU/PKS">
                                                <x-lucide-file-x class="icon me-1" />
                                                Belum Ada
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        {{-- Surat Kesediaan dari Dosen --}}
                                        <div class="d-flex align-items-center gap-1">
                                            @if ($partner->hasCommitmentForProposal($this->form->proposal?->id))
                                                <a href="{{ $partner->getCommitmentUrlForProposal($this->form->proposal?->id) }}"
                                                   target="_blank"
                                                   class="btn btn-sm btn-outline-success" title="Lihat Surat Kesediaan">
                                                    <x-lucide-file-text class="icon" />
                                                    Lihat
                                                </a>
                                            @else
                                                <span class="badge bg-red-lt text-red-fg">
                                                    <x-lucide-file-x class="icon me-1" />
                                                    Belum
                                                </span>
                                            @endif
                                            <div
                                                x-data="{ uploading: false, progress: 0 }"
                                                wire:key="upload-{{ $partnerId }}"
                                            >
                                                <input
                                                    type="file"
                                                    x-ref="fileInput"
                                                    accept=".pdf"
                                                    class="d-none"
                                                    x-on:change="
                                                        const file = $refs.fileInput.files[0];
                                                        if (!file) return;
                                                        uploading = true;
                                                        progress = 0;
                                                        $wire.upload('commitmentUploadFile', file,
                                                            (successResponse) => {
                                                                $wire.uploadCommitmentLetter('{{ $partnerId }}');
                                                                uploading = false;
                                                                progress = 0;
                                                                $refs.fileInput.value = '';
                                                            },
                                                            (errorResponse) => { uploading = false; },
                                                            (event) => { progress = event.detail.progress; }
                                                        );
                                                    "
                                                >
                                                <button type="button"
                                                    x-on:click="$refs.fileInput.click()"
                                                    x-show="!uploading"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    title="Upload Surat Kesediaan">
                                                    <x-lucide-upload class="icon" />
                                                </button>
                                                <button type="button"
                                                    x-show="uploading"
                                                    class="btn btn-sm btn-outline-secondary disabled"
                                                    disabled>
                                                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                                                    <span x-text="Math.round(progress) + '%'"></span>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-list flex-nowrap">
                                            <button type="button"
                                                wire:click="$set('form.partner_ids', {{ json_encode(array_values(array_diff($form->partner_ids, [$partnerId]))) }})"
                                                class="btn btn-sm btn-danger">
                                                <x-lucide-trash class="icon" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<!-- Modal: Tambah Mitra -->
<x-tabler.modal id="modal-partner" title="Tambah Mitra Kerjasama" size="lg"
    x-data="{ partnerMode: 'existing' }">
    {{-- Tab Toggle --}}
    <div class="mb-3">
        <div class="btn-group w-100" role="group">
            <input type="radio" class="btn-check" name="partner-mode" id="tab-existing-r" value="existing"
                x-model="partnerMode">
            <label class="btn btn-outline-primary" for="tab-existing-r">
                <x-lucide-search class="icon me-1" />
                Pilih Mitra yang Ada
            </label>

            <input type="radio" class="btn-check" name="partner-mode" id="tab-new-r" value="new"
                x-model="partnerMode">
            <label class="btn btn-outline-secondary" for="tab-new-r">
                <x-lucide-plus class="icon me-1" />
                Buat Mitra Baru
            </label>
        </div>
    </div>

    {{-- TAB 1: Pilih Mitra yang Sudah Ada --}}
    <div x-show="partnerMode === 'existing'">
        <div class="mb-3">
            <input type="text" wire:model.live.debounce.300ms="partnerSearch"
                class="form-control" placeholder="Ketik nama mitra untuk mencari...">
        </div>

        @error('existing_partner_id')
            <div class="alert alert-warning py-2">{{ $message }}</div>
        @enderror

        <div style="max-height: 320px; overflow-y: auto;">
            @php
                $existing = $this->partners
                    ->when(!empty($partnerSearch), fn($c) =>
                        $c->filter(fn($p) => str_contains(strtolower($p->name), strtolower($partnerSearch))
                                         || str_contains(strtolower($p->institution ?? ''), strtolower($partnerSearch))))
                    ->values();
            @endphp

            @forelse($existing as $p)
                <div class="d-flex align-items-center justify-content-between p-2 border rounded mb-2
                    {{ in_array($p->id, $form->partner_ids ?? []) ? 'bg-success-lt' : 'bg-light' }}">
                    <div>
                        <div class="fw-medium">{{ $p->name }}</div>
                        <div class="text-muted small">
                            {{ $p->type ?? '-' }}
                            @if($p->institution) · {{ $p->institution }} @endif
                        </div>
                    </div>
                    @if(in_array($p->id, $form->partner_ids ?? []))
                        <span class="badge bg-success">
                            <x-lucide-check class="icon me-1" />
                            Sudah Dipilih
                        </span>
                    @else
                        <button type="button" wire:click="addExistingPartner('{{ $p->id }}')"
                            class="btn btn-sm btn-primary">
                            <x-lucide-plus class="icon" />
                            Pilih
                        </button>
                    @endif
                </div>
            @empty
                <div class="text-center text-muted py-4">
                    <x-lucide-search class="icon mb-2" />
                    <div>Tidak ada mitra yang cocok.</div>
                    <small>Coba gunakan tab "Buat Mitra Baru" untuk menambahkan mitra baru.</small>
                </div>
            @endforelse
        </div>
    </div>

    {{-- TAB 2: Buat Mitra Baru --}}
<div x-show="partnerMode === 'new'"> 
        <div class="mb-3">
            <label class="form-label">Nama Mitra <span class="text-danger">*</span></label>
            <input type="text" wire:model="form.new_partner.name"
                class="form-control @error('form.new_partner.name') is-invalid @enderror"
                placeholder="Nama lengkap mitra" required>
            @error('form.new_partner.name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Alamat Surel</label>
            <input type="email" wire:model="form.new_partner.email"
                class="form-control @error('form.new_partner.email') is-invalid @enderror"
                placeholder="email@example.com">
            @error('form.new_partner.email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Institusi <span class="text-danger">*</span></label>
            <input type="text" wire:model="form.new_partner.institution"
                class="form-control @error('form.new_partner.institution') is-invalid @enderror"
                placeholder="Nama institusi" required>
            @error('form.new_partner.institution')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Jenis Mitra <span class="text-danger">*</span></label>
            <select wire:model="form.new_partner.type" class="form-select @error('form.new_partner.type') is-invalid @enderror" required>
                <option value="">-- Pilih Jenis Mitra --</option>
                @foreach(\App\Constants\ProposalConstants::PARTNER_TYPES as $type)
                    <option value="{{ $type }}">{{ $type }}</option>
                @endforeach
            </select>
            @error('form.new_partner.type')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Negara <span class="text-danger">*</span></label>
            <input type="text" wire:model="form.new_partner.country"
                class="form-control @error('form.new_partner.country') is-invalid @enderror"
                placeholder="Negara" required>
            @error('form.new_partner.country')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Alamat</label>
            <textarea wire:model="form.new_partner.address"
                class="form-control @error('form.new_partner.address') is-invalid @enderror"
                rows="3" placeholder="Alamat lengkap"></textarea>
            @error('form.new_partner.address')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">File Surat Kesediaan Mitra (PDF)</label>
            <input type="file" wire:model="form.new_partner_commitment_file"
                class="form-control @error('form.new_partner_commitment_file') is-invalid @enderror"
                accept=".pdf">
            @error('form.new_partner_commitment_file')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">Maksimal 5MB, format PDF. Opsional (dapat diupload setelah mitra ditambahkan).</small>

            @if ($form->new_partner_commitment_file)
                <div class="mt-2">
                    <x-lucide-file-check class="icon text-success" />
                    File terpilih: {{ $form->new_partner_commitment_file->getClientOriginalName() }}
                </div>
            @endif
        </div>
    </div>

    <x-slot:footer>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="button" wire:click="saveNewPartner" class="btn btn-primary"
            x-show="partnerMode === 'new'">
            <span wire:loading.remove>
                <x-lucide-save class="icon" />
                Simpan Mitra Baru
            </span>
            <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
            <span wire:loading>Menyimpan...</span>
        </button>
    </x-slot:footer>
</x-tabler.modal>

{{-- Modal: Upload Surat Kesediaan Mitra --}}
<x-tabler.modal id="modal-upload-kesediaan" title="Upload Surat Kesediaan Mitra" onHide="resetCommitmentUpload"
    component-id="{{ $this->getId() }}" :wireIgnore="false">
    @if($commitmentUploadPartnerId)
        @php $uploadTarget = $this->partners->find($commitmentUploadPartnerId); @endphp
        <div class="mb-3">
            <div class="alert alert-info py-2 mb-2">
                <x-lucide-info class="icon me-1" />
                Mitra: <strong>{{ $uploadTarget?->name ?? '-' }}</strong>
            </div>
            <p class="text-muted small mb-0">
                Surat Kesediaan adalah dokumen yang menyatakan kesediaan mitra bekerjasama dalam proposal ini.
                Berbeda dengan MOU/PKS yang merupakan perjanjian resmi tingkat institusi (dikelola Admin).
            </p>
        </div>
    @endif

    <div class="mb-3">
        <label class="form-label">File Surat Kesediaan <span class="text-danger">*</span></label>
        <input type="file" wire:model="commitmentUploadFile"
            class="form-control @error('commitmentUploadFile') is-invalid @enderror"
            accept=".pdf">
        @error('commitmentUploadFile')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="text-muted">Format PDF, maksimal 5MB</small>

        @if($commitmentUploadFile)
            <div class="mt-2 text-success small">
                <x-lucide-file-check class="icon" />
                File dipilih: {{ $commitmentUploadFile->getClientOriginalName() }}
            </div>
        @endif
    </div>

    <x-slot:footer>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" wire:click="uploadCommitmentLetter" class="btn btn-success"
            wire:loading.class="btn-loading" wire:target="uploadCommitmentLetter">
            <span wire:loading.remove>
                <x-lucide-upload class="icon me-1" />
                Upload Surat Kesediaan
            </span>
            <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
            <span wire:loading>Mengupload...</span>
        </button>
    </x-slot:footer>
</x-tabler.modal>
