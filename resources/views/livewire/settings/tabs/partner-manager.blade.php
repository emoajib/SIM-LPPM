<div>
    <x-tabler.alert />
    <div class="card">
        <div class="d-flex align-items-center justify-content-between card-header">
            <h3 class="card-title">Daftar Mitra</h3>
            <button type="button" class="btn btn-primary" wire:click='create' data-bs-toggle="modal"
                data-bs-target="#modal-partner">
                
                Tambah Mitra
            </button>
        </div>
        <div class="table-responsive">
            <table class="card-table table table-vcenter">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Institusi</th>
                        <th>Jenis</th>
                        <th>Negara</th>
                        <th>MOU / PKS</th>
                        <th class="w-25">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($partners as $item)
                        <tr wire:key="partner-{{ $item->id }}">
                            <td>
                                <div class="fw-medium">{{ $item->name }}</div>
                                @if($item->email)
                                    <div class="text-muted small">{{ $item->email }}</div>
                                @endif
                            </td>
                            <td>{{ $item->institution ?? '-' }}</td>
                            <td>
                                @if($item->type)
                                    <span class="badge bg-blue-lt">{{ $item->type }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $item->country ?? '-' }}</td>
                            <td>
                                                            @if($item->hasMedia('mou_pks'))
                                    @php $media = $item->getFirstMedia('mou_pks'); @endphp
                                                <a data-navigate-ignore="true" href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                                    download="{{ $media->file_name ?? $media->name ?? 'download' }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                         Lihat
                                    </a>
                                @else
                                    <span class="badge bg-yellow-lt">Belum Ada</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-list">
                                    <button type="button" class="btn-outline-warning btn btn-sm"
                                        wire:click="edit('{{ $item->id }}')">
                                        Edit
                                    </button>
                                    <button type="button" class="btn-outline-danger btn btn-sm"
                                        wire:click="confirmDelete('{{ $item->id }}')" wire:loading.attr="disabled">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $partners->links() }}
        </div>
    </div>

    <x-tabler.modal-confirmation wire:key="modal-confirm-delete-partner" id="modal-confirm-delete-partner"
        title="Konfirmasi Hapus" message="Apakah Anda yakin ingin menghapus {{ $deleteItemName ?? '' }}?"
        confirm-text="Ya, Hapus" cancel-text="Batal" component-id="{{ $this->getId() }}"
        on-confirm="handleConfirmDeleteAction" />

    <x-tabler.modal wire:key="modal-partner" id="modal-partner" :title="$modalTitle" onHide="resetForm"
        component-id="{{ $this->getId() }}" size="lg">
        <x-slot:body>
            <form wire:submit="save" id="form-partner">
                <div class="row g-3">
                    {{-- Nama --}}
                    <div class="col-12">
                        <label class="form-label">Nama Mitra <span class="text-danger">*</span></label>
                        <input type="text" wire:model="name" class="form-control @error('name') is-invalid @enderror"
                            placeholder="Nama lengkap mitra atau perusahaan">
                        @error('name')
                            <div class="d-block invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Institusi --}}
                    <div class="col-md-6">
                        <label class="form-label">Institusi / Lembaga</label>
                        <input type="text" wire:model="institution"
                            class="form-control @error('institution') is-invalid @enderror"
                            placeholder="Nama institusi / lembaga">
                        @error('institution')
                            <div class="d-block invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div class="col-md-6">
                        <label class="form-label">Alamat Surel</label>
                        <input type="email" wire:model="email"
                            class="form-control @error('email') is-invalid @enderror"
                            placeholder="email@mitra.com">
                        @error('email')
                            <div class="d-block invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Jenis --}}
                    <div class="col-md-6">
                        <label class="form-label">Jenis Mitra <span class="text-danger">*</span></label>
                        <select wire:model="type" class="form-select @error('type') is-invalid @enderror">
                            <option value="">-- Pilih Jenis Mitra --</option>
                            @foreach (\App\Livewire\Settings\Tabs\PartnerManager::TYPES as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type')
                            <div class="d-block invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Negara --}}
                    <div class="col-md-6">
                        <label class="form-label">Negara</label>
                        <input type="text" wire:model="country"
                            class="form-control @error('country') is-invalid @enderror"
                            placeholder="Indonesia">
                        @error('country')
                            <div class="d-block invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Alamat --}}
                    <div class="col-12">
                        <label class="form-label">Alamat</label>
                        <textarea wire:model="address" class="form-control @error('address') is-invalid @enderror"
                            rows="2" placeholder="Alamat lengkap mitra"></textarea>
                        @error('address')
                            <div class="d-block invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Dokumen MOU/PKS (Admin) --}}
                    <div class="col-12">
                        <label class="form-label">
                            Dokumen MOU / PKS
                            @if($editingId)
                                <span class="text-muted small">— kosongkan jika tidak ingin mengubah file</span>
                            @endif
                        </label>
                        <div class="text-muted small mb-1">
                            
                            Upload Memorandum of Understanding (MOU) atau Perjanjian Kerjasama (PKS) level institusi.
                            Surat Kesediaan Mitra diupload oleh dosen saat pengajuan proposal.
                        </div>
                        <input type="file" wire:model="mouPksFile"
                            class="form-control @error('mouPksFile') is-invalid @enderror"
                            accept=".pdf">
                        @error('mouPksFile')
                            <div class="d-block invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Maksimal 5MB, format PDF</small>

                        @if($mouPksFile)
                            <div class="mt-1 text-success small">
                                
                                File baru dipilih: {{ $mouPksFile->getClientOriginalName() }}
                            </div>
                        @elseif($editingId)
                            @php $p = \App\Models\Partner::find($editingId); @endphp
                            @if($p && $p->hasMedia('mou_pks'))
                                <div class="mt-1 small">
                                    
                                    File saat ini:
                                    @php $media = $p->getFirstMedia('mou_pks'); @endphp
                                    <a data-navigate-ignore="true" href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                        download="{{ $media->file_name ?? $media->name ?? 'download' }}" target="_blank">
                                        {{ $p->getFirstMedia('mou_pks')->file_name }}
                                    </a>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </form>
        </x-slot:body>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" form="form-partner" class="btn btn-primary" wire:loading.class="btn-loading"
                wire:target="save">Simpan</button>
        </x-slot:footer>
    </x-tabler.modal>

</div>
