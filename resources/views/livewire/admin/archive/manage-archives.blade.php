<div>
    <x-slot:pageHeader>
        {{-- Header empty as requested --}}
    </x-slot:pageHeader>

    <x-slot:pageActions>
        <div class="btn-list">
            <button onclick="Livewire.dispatch('request-template-download')"
                class="btn btn-ghost-success border shadow-sm">
                <i class="ti ti-download me-2"></i>
                {{ __('Unduh Template') }}
            </button>
            <button onclick="Livewire.dispatch('request-export-data')" class="btn btn-success shadow-sm">
                <i class="ti ti-table-export me-2"></i>
                {{ __('Eksport (Excel)') }}
            </button>
            <button onclick="Livewire.dispatch('open-import-modal')" class="btn btn-indigo shadow-sm">
                <i class="ti ti-file-import me-2"></i>
                {{ __('Import Excel') }}
            </button>
        </div>
    </x-slot:pageActions>

    <div class="page-body">
        <div class="container-xl">
            <!-- Filter -->
            <div class="card mb-4 border-0 shadow-sm bg-light-lt">
                <div class="card-body p-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-7">
                            <div class="input-group input-group-flat">
                                <span class="input-group-text">
                                    <i class="ti ti-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0"
                                    placeholder="Cari judul kegiatan atau nama pengusul..."
                                    wire:model.live.debounce.300ms="search">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="d-flex align-items-center gap-2">
                                <div class="text-muted small text-nowrap"><i
                                        class="ti ti-filter me-1"></i>{{ __('Tahun') }}:</div>
                                <select class="form-select shadow-none border-0 bg-white" wire:model.live="yearFilter">
                                    <option value="">{{ __('Semua Tahun') }}</option>
                                    @for($y = date('Y'); $y >= 2010; $y--)
                                        <option value="{{ $y }}">{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="card border-0 shadow-sm overflow-hidden card-stacked">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover">
                        <thead>
                            <tr class="bg-light-lt">
                                <th class="py-3">{{ __('Judul Kegiatan') }}</th>
                                <th class="py-3 text-center">{{ __('Ketua') }}</th>
                                <th class="py-3 text-center">{{ __('Tahun') }}</th>
                                <th class="py-3 text-center">{{ __('Jenis') }}</th>
                                <th class="py-3 text-center">{{ __('Dana (Rp)') }}</th>
                                <th class="py-3 w-1">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($archives as $archive)
                                <tr wire:key="{{ $archive->id }}">
                                    <td>
                                        <div class="fw-bold mb-1">{{ Str::limit($archive->title, 70) }}</div>
                                        <div class="text-secondary small line-clamp-2">
                                            {{ $archive->summary ?: __('Tidak ada ringkasan.') }}</div>
                                    </td>
                                    <td class="text-center">
                                        <div class="fw-medium">{{ $archive->submitter->name ?? 'Unknown' }}</div>
                                        <div class="text-muted small">NIDN:
                                            {{ $archive->submitter->identity?->identity_id ?? '-' }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-indigo-lt p-2">{{ $archive->start_year }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if(str_contains($archive->detailable_type, 'Research'))
                                            <span
                                                class="badge badge-outline border-blue text-blue fw-bold">{{ __('Penelitian') }}</span>
                                        @else
                                            <span
                                                class="badge badge-outline border-success text-success fw-bold">{{ __('Pengabdian') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center fw-bold text-success">
                                        @php
                                            $dana = $archive->approved_budget_total;
                                        @endphp
                                        {{ number_format($dana, 0, ',', '.') }}
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn dropdown-toggle align-text-top btn-sm"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                {{ __('Aksi') }}
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <a class="dropdown-item" href="#"
                                                    wire:click.prevent="edit('{{ $archive->id }}')">
                                                    <i class="ti ti-edit me-2 text-primary"></i> {{ __('Edit Arsip') }}
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item text-danger" href="#"
                                                    wire:confirm="{{ __('Yakin ingin menghapus arsip ini? Data yang dihapus tidak dapat dikembalikan.') }}"
                                                    wire:click.prevent="delete('{{ $archive->id }}')">
                                                    <i class="ti ti-trash me-2"></i> {{ __('Hapus Arsip') }}
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="empty bg-white py-4 rounded-3 border">
                                            <div class="empty-icon mb-3">
                                                <i class="ti ti-database-x text-muted fs-0"></i>
                                            </div>
                                            <p class="empty-title fw-bold">{{ __('Tidak ada data arsip') }}</p>
                                            <p class="empty-subtitle text-secondary">
                                                {{ __('Mulai dengan mengimport data dari template Excel atau sesuaikan filter pencarian Anda.') }}
                                            </p>
                                            <div class="empty-action mt-4">
                                                <button wire:click="$set('showImportModal', true)"
                                                    class="btn btn-indigo shadow-sm">
                                                    <i class="ti ti-plus me-2"></i>{{ __('Import Data Pertama') }}
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($archives->hasPages())
                    <div class="card-footer bg-white border-top-0">
                        {{ $archives->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Import Modal (Neon-Glass Polish) -->
    @if($showImportModal)
        <div class="modal modal-blur fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content shadow-lg border-0">
                    <div class="modal-header bg-light-lt">
                        <h5 class="modal-title fw-bold"><i class="ti ti-file-import me-2"></i>{{ __('Import Data Arsip') }}
                        </h5>
                        <button type="button" class="btn-close shadow-none"
                            wire:click="$set('showImportModal', false)"></button>
                    </div>
                    <form wire:submit="import">
                        <div class="modal-body py-4">
                            <div class="mb-4 text-center">
                                <i class="ti ti-cloud-upload text-indigo opacity-25" style="font-size: 4rem;"></i>
                                <div class="text-secondary small mt-2">
                                    {{ __('Unggah berkas Excel (.xlsx) sesuai dengan template yang telah disediakan.') }}
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ __('Pilih Berkas Excel') }}</label>
                                <input type="file" class="form-control" wire:model="importFile">
                                @error('importFile') <div class="text-danger small mt-1"><i
                                class="ti ti-alert-triangle me-1"></i>{{ $message }}</div> @enderror
                            </div>
                            <div class="alert alert-important alert-info bg-indigo-lt border-0 shadow-sm">
                                <div class="d-flex">
                                    <div><i class="ti ti-info-circle me-3 fs-1"></i></div>
                                    <div>
                                        <div class="fw-bold mb-1">{{ __('Petunjuk Import') }}</div>
                                        <div class="small opacity-75">
                                            {{ __('Kolom wajib: nidn, nama_dosen, judul, skema, tahun. Sistem akan mencocokkan NIDN dengan data dosen yang ada.') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light-lt">
                            <button type="button" class="btn btn-ghost-secondary px-4"
                                wire:click="$set('showImportModal', false)">{{ __('Batal') }}</button>
                            <button type="submit" class="btn btn-indigo px-4" wire:loading.attr="disabled">
                                <span wire:loading.remove><i class="ti ti-check me-2"></i>{{ __('Mulai Import') }}</span>
                                <span wire:loading><span
                                        class="spinner-border spinner-border-sm me-2"></span>{{ __('Memproses...') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Edit Modal (Neon-Glass Polish) -->
    @if($showEditModal)
        <div class="modal modal-blur fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content shadow-lg border-0">
                    <div class="modal-header bg-light-lt">
                        <h5 class="modal-title fw-bold"><i class="ti ti-edit me-2"></i>{{ __('Edit Data Arsip') }}</h5>
                        <button type="button" class="btn-close shadow-none"
                            wire:click="$set('showEditModal', false)"></button>
                    </div>
                    <form wire:submit="update">
                        <div class="modal-body py-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ __('Judul Kegiatan') }}</label>
                                <input type="text" class="form-control" wire:model="editTitle"
                                    placeholder="{{ __('Masukkan judul lengkap...') }}">
                                @error('editTitle') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">{{ __('Tahun Pelaksanaan') }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="ti ti-calendar"></i></span>
                                        <input type="number" class="form-control" wire:model="editYear" min="2000"
                                            max="{{ date('Y') }}">
                                    </div>
                                    @error('editYear') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">{{ __('Dana Disetujui (Rp)') }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" wire:model="editDana" min="0">
                                    </div>
                                    @error('editDana') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label fw-bold">{{ __('Ringkasan / Abstrak') }}</label>
                                <textarea class="form-control" rows="5" wire:model="editSummary"
                                    placeholder="{{ __('Tambahkan ringkasan kegiatan jika tersedia...') }}"></textarea>
                                @error('editSummary') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="modal-footer bg-light-lt">
                            <button type="button" class="btn btn-ghost-secondary px-4"
                                wire:click="$set('showEditModal', false)">{{ __('Batal') }}</button>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                <i class="ti ti-device-floppy me-2"></i>{{ __('Simpan Perubahan') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>