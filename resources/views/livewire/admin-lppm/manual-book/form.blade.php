<div>
    <form wire:submit="save">
        <div class="row g-3">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label required">Judul Manual Book</label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror"
                                wire:model="title" placeholder="Masukkan judul manual book">
                            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                wire:model="description" rows="3" placeholder="Deskripsi manual book (opsional)"></textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">Role Pengguna</label>
                            <p class="text-muted small">Pilih role yang akan melihat manual book ini.</p>
                            <div class="row g-2">
                                @foreach($allRoles as $role)
                                    <div class="col-md-3 col-sm-4 col-6">
                                        <label class="form-check">
                                            <input type="checkbox" class="form-check-input"
                                                wire:model="assignedRoles" value="{{ $role }}">
                                            <span class="form-check-label">{{ format_role_name($role) }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @error('assignedRoles') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label required">Versi</label>
                                <input type="text" class="form-control @error('version_number') is-invalid @enderror"
                                    wire:model="version_number" placeholder="1.0">
                                @error('version_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Status</label>
                                <select class="form-select @error('status') is-invalid @enderror" wire:model="status">
                                    <option value="active">Aktif</option>
                                    <option value="inactive">Nonaktif</option>
                                </select>
                                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">File PDF</h4>
                    </div>
                    <div class="card-body">
                        @if($existingFile)
                            <div class="mb-3">
                                <p class="text-success mb-1">
                                    <i class="icon icon-tabler icon-tabler-file-check me-1"></i>
                                    File terupload:
                                </p>
                                <p class="mb-1 small">{{ $existingFile['name'] }}</p>
                                <p class="text-muted small">{{ number_format($existingFile['size'] / 1024, 1) }} KB</p>
                                <div class="d-flex gap-2">
                                    <a href="{{ $existingFile['url'] }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                        <i class="icon icon-tabler icon-tabler-download"></i> Download
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                        wire:click="removeFile" wire:confirm="Hapus file ini?">
                                        <i class="icon icon-tabler icon-tabler-trash"></i> Hapus
                                    </button>
                                </div>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label">
                                {{ $existingFile ? 'Ganti File' : 'Upload File' }}
                            </label>
                            <input type="file" class="form-control @error('file') is-invalid @enderror"
                                wire:model="file" accept=".pdf">
                            <small class="text-muted">Format: PDF, maks 10 MB</small>
                            @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div wire:loading wire:target="file" class="text-info small mt-1">
                                <i class="icon icon-tabler icon-tabler-loader icon-spin"></i> Mengupload...
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="icon icon-tabler icon-tabler-device-floppy me-1"></i>
                                {{ $isEdit ? 'Simpan Perubahan' : 'Buat Manual Book' }}
                            </button>
                            <a href="{{ route('admin-lppm.manual-books.index') }}" class="btn btn-outline-secondary" wire:navigate>
                                Batal
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
