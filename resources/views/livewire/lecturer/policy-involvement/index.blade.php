<div>
    <x-page-header title="Rekognisi & Kebijakan"
        subtitle="{{ $isAdmin ? 'Verifikasi dan kelola rekognisi pakar serta keterlibatan kebijakan.' : 'Kelola keterlibatan Anda dalam penyusunan kebijakan dan rekognisi pakar.' }}">
        <x-slot:actions>
            @if(!$isAdmin)
                <button class="btn btn-primary" wire:click="openModal">
                     Tambah Rekognisi
                </button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                @if($isAdmin)
                                    <th>Dosen</th>
                                @endif
                                <th>Judul / Aktivitas</th>
                                <th>Instansi / Organisasi</th>
                                <th>Level</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($involvements as $item)
                                <tr>
                                    @if($isAdmin)
                                        <td>
                                            <div class="font-weight-medium">{{ $item->user->name }}</div>
                                            <div class="text-muted small">{{ $item->user->identity?->identity_id }}</div>
                                        </td>
                                    @endif
                                    <td>
                                        <div class="font-weight-medium">{{ $item->title }}</div>
                                        <div class="text-muted small">{{ $item->role ?? 'N/A' }}</div>
                                    </td>
                                    <td>{{ $item->organization }}</td>
                                    <td>
                                        <span class="badge bg-secondary-lt">{{ $item->level }}</span>
                                    </td>
                                    <td>{{ $item->date->format('M Y') }}</td>
                                    <td>
                                        @if($item->status === 'verified')
                                            <span class="badge bg-success">Terverifikasi</span>
                                        @elseif($item->status === 'rejected')
                                            <span class="badge bg-danger">Ditolak</span>
                                        @else
                                            <span class="badge bg-warning">Menunggu</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-list flex-nowrap">
                                            @if($item->hasMedia('supporting_document'))
                                                @php $media = $item->getFirstMedia('supporting_document'); @endphp
                                                <a data-navigate-ignore="true"
                                                    href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                                    target="_blank" class="btn btn-icon btn-ghost-info" title="Lihat Dokumen">
                                                    
                                                </a>
                                            @endif

                                            @if($isAdmin)
                                                @if($item->status !== 'verified')
                                                    <button class="btn btn-icon btn-ghost-success"
                                                        wire:click="verify('{{ $item->id }}')" title="Verifikasi">
                                                        
                                                    </button>
                                                @endif
                                                @if($item->status !== 'rejected')
                                                    <button class="btn btn-icon btn-ghost-danger"
                                                        wire:click="reject('{{ $item->id }}')" title="Tolak">
                                                        
                                                    </button>
                                                @endif
                                            @endif

                                            @if(!$isAdmin || $item->user_id === auth()->id())
                                                <button class="btn btn-icon btn-ghost-primary"
                                                    wire:click="openModal('{{ $item->id }}')" title="Edit">
                                                    
                                                </button>
                                            @endif

                                            <button class="btn btn-icon btn-ghost-danger"
                                                onclick="confirm('Apakah Anda yakin ingin menghapus data ini?') || event.stopImmediatePropagation()"
                                                wire:click="delete('{{ $item->id }}')" title="Hapus">
                                                
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isAdmin ? '7' : '6' }}" class="text-center py-4 text-muted"> Belum ada
                                        data rekognisi. </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($involvements->hasPages())
                    <div class="card-footer d-flex align-items-center">
                        {{ $involvements->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div class="modal modal-blur fade @if($showModal) show @endif"
        style="@if($showModal) display: block; @else display: none; @endif" tabindex="-1" role="dialog"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $editingId ? 'Edit Rekognisi' : 'Tambah Rekognisi' }}</h5>
                    <button type="button" class="btn-close" wire:click="$set('showModal', false)"
                        aria-label="Close"></button>
                </div>
                <form wire:submit.prevent="save">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label class="form-label">Judul Aktivitas / Kebijakan <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror"
                                        wire:model.defer="title"
                                        placeholder="Contoh: Penyusunan Standar Nasional Pendidikan">
                                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Instansi / Organisasi <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('organization') is-invalid @enderror"
                                        wire:model.defer="organization" placeholder="Contoh: Kemendiktisaintek">
                                    @error('organization') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Level <span class="text-danger">*</span></label>
                                    <select class="form-select @error('level') is-invalid @enderror"
                                        wire:model.defer="level">
                                        <option value="Internasional">Internasional</option>
                                        <option value="Nasional">Nasional</option>
                                        <option value="Regional/Institusi">Regional / Institusi</option>
                                    </select>
                                    @error('level') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Peran <span class="text-muted">(Opsional)</span></label>
                                    <input type="text" class="form-control @error('role') is-invalid @enderror"
                                        wire:model.defer="role" placeholder="Contoh: Ketua Tim / Reviewer">
                                    @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Pelaksanaan <span
                                            class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('date') is-invalid @enderror"
                                        wire:model.defer="date">
                                    @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label class="form-label">Keterangan</label>
                                    <textarea class="form-control" wire:model.defer="description" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label class="form-label">Unggah SK / Surat Tugas <span
                                            class="text-muted">(PDF/Gambar, Max 2MB)</span></label>
                                    <input type="file" class="form-control @error('document') is-invalid @enderror"
                                        wire:model="document">
                                    @error('document') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <div wire:loading wire:target="document" class="text-info small mt-1">Mengunggah...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" wire:click="$set('showModal', false)">
                            Batal </button>
                        <button type="submit" class="btn btn-primary ms-auto" wire:loading.attr="disabled">
                            
                            {{ $editingId ? 'Simpan Perubahan' : 'Simpan Rekognisi' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @if($showModal)
        <div class="modal-backdrop fade show"></div>
    @endif
</div>