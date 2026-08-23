<x-slot:title>Catatan Harian PKM: {{ $proposal->title }}</x-slot:title>
<x-slot:pageTitle>Catatan Harian (Logbook)</x-slot:pageTitle>
<x-slot:pageSubtitle>{{ $proposal->title }}</x-slot:pageSubtitle>
<x-slot:pageActions>
    <a href="{{ route('community-service.daily-note.index') }}" class="btn-outline-secondary btn" wire:navigate.hover>
        <x-lucide-arrow-left class="me-2 icon" />
        Kembali
    </a>
    <a data-navigate-ignore="true" href="{{ route('financial-reports.export-pdf', ['proposal' => $proposal, 'preview' => 1]) }}" target="_blank" class="btn-outline-primary btn">
        <x-lucide-eye class="me-2 icon" />
        Pratinjau LPJ
    </a>
    @if ($this->logbookApprovalMode !== 'upload')
        <a data-navigate-ignore="true" href="{{ route('financial-reports.export-pdf', ['proposal' => $proposal, 'download' => 'true']) }}" target="_blank" class="btn-outline-success btn">
            <x-lucide-download class="me-2 icon" />
            Unduh LPJ
        </a>
    @endif
    @if ($proposal->logbook_approved_at)
        <span class="badge bg-success">
            <x-lucide-check-circle class="me-1 icon icon-inline" /> Telah Disetujui LPPM
        </span>
    @elseif ($this->logbookApprovalMode !== 'upload' && $this->canApprove($proposal) && $proposal->logbook_signed_at)
        <button type="button" class="btn-success btn" x-data @click="if(confirm('Apakah Anda sebagai Kepala LPPM memvalidasi kebenaran catatan harian dan laporan keuangan ini?')) { Livewire.dispatch('approve-logbook') }">
            <x-lucide-shield-check class="me-2 icon" />
            Validasi Kepala LPPM
        </button>
    @elseif ($proposal->logbook_signed_at && $this->logbookApprovalMode !== 'upload')
        <span class="badge bg-warning">Menunggu Validasi LPPM</span>
    @elseif ($this->logbookApprovalMode !== 'upload' && $this->canManage($proposal))
        <button type="button" class="btn-primary btn" x-data @click="if(confirm('Apakah Anda yakin ingin menandatangani logbook dan laporan keuangan ini secara digital? Tanda tangan ini menyatakan keabsahan seluruh aktivitas harian beserta laporannya.')) { Livewire.dispatch('sign-logbook') }">
            <x-lucide-check-square class="me-2 icon" />
            Tandatangani & Unduh LPJ
        </button>
    @endif
</x-slot:pageActions>

<div>
    <x-tabler.alert />

    <div class="alert alert-info" role="alert">
        <div class="d-flex">
            <div>
                <x-lucide-info class="icon alert-icon" />
            </div>
            <div>
                <h4 class="alert-title">Informasi Catatan Harian</h4>
                <div class="text-secondary">
                    Gunakan fitur ini untuk mencatat aktivitas harian pengabdian masyarakat Anda. Catatan ini akan
                    menjadi bukti pelaksanaan kegiatan dan digunakan dalam pemantauan progres pengabdian. Lampirkan
                    foto atau dokumen sebagai bukti dukung kegiatan.
                </div>
            </div>
        </div>
    </div>

    @include('livewire.partials.logbook-approval-upload')

    @if ($budget_groups->count() > 0)
        <div class="mt-3 mb-3 border bg-white rounded">
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <span class="bg-primary-lt p-2 rounded me-3">
                        <x-lucide-calculator class="icon text-primary icon-md" />
                    </span>
                    <div>
                        <div class="text-secondary small fw-medium">Total RAB Disetujui</div>
                        <div class="mb-0 h3 fw-bold text-primary">Rp {{ number_format($total_proposed_budget, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="d-flex align-items-center text-end">
                    <div class="me-4 border-end pe-4">
                        <div class="text-secondary small fw-medium">Total Digunakan</div>
                        <div class="mb-0 h4 fw-bold text-warning">Rp {{ number_format($notes_list->sum('amount'), 0, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium">Sisa Saldo</div>
                        <div class="mb-0 h4 fw-bold {{ ($total_proposed_budget - $notes_list->sum('amount')) < 0 ? 'text-danger' : 'text-success' }}">
                            Rp {{ number_format($total_proposed_budget - $notes_list->sum('amount'), 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-0 table-responsive bg-white rounded-bottom">
                 <table class="table table-vcenter table-nowrap mb-0 table-sm">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3 text-uppercase font-weight-bold" style="font-size: 0.75rem;">Kelompok Anggaran</th>
                            <th class="text-uppercase font-weight-bold text-end" style="font-size: 0.75rem;">RAB (Pagu)</th>
                            <th class="text-uppercase font-weight-bold text-end" style="font-size: 0.75rem;">Terpakai (T)</th>
                            <th class="text-uppercase font-weight-bold text-end" style="font-size: 0.75rem;">Sisa Saldo (S)</th>
                            <th class="text-uppercase font-weight-bold w-25 pe-3" style="font-size: 0.75rem;">Progres Pemakaian (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($budget_groups as $group)
                            @php 
                                                        $groupNotes = $notes_list->where('budget_group_id', $group->id);
                                $groupUsed = $groupNotes->sum('amount');
                                $groupBudget = isset($budget_summaries[$group->id]) ? $budget_summaries[$group->id]->total_budget : 0;
                                $groupSisa = $groupBudget - $groupUsed;
                                $percentage = $groupBudget > 0 ? min(100, round(($groupUsed / $groupBudget) * 100)) : 0;

                                $progressClass = 'bg-blue';
                                if ($percentage >= 90) {
                                    $progressClass = 'bg-red';
                                } elseif ($percentage >= 75) {
                                    $progressClass = 'bg-yellow';
                                }
                            @endphp
                            <tr>
                                <td class="ps-3 fw-medium text-secondary">
                                    <div class="d-flex align-items-center">
                                       <span class="bg-blue-lt me-2 p-1 rounded-1">
                                           <x-lucide-tag class="icon" style="width: 1rem; height: 1rem;" />
                                       </span>
                                       {{ $group->name }}
                                    </div>
                                </td>
                                <td class="text-end fw-medium">Rp {{ number_format($groupBudget, 0, ',', '.') }}</td>
                                <td class="text-end {{ $groupUsed > 0 ? 'text-warning fw-medium' : 'text-muted' }}">Rp {{ number_format($groupUsed, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold {{ $groupSisa < 0 ? 'text-danger' : 'text-success' }}">Rp {{ number_format($groupSisa, 0, ',', '.') }}</td>
                                <td class="pe-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="w-100 progress progress-xs">
                                            <div class="progress-bar {{ $progressClass }}" style="width: {{ $percentage }}%"></div>
                                        </div>
                                        <span class="small text-muted fw-bold" style="min-width: 35px; text-align: right;">{{ $percentage }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                 </table>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Riwayat Aktivitas Pengabdian</h3>
            @if ($this->canManage($proposal))
                <div class="card-actions">
                    <button wire:click="create" class="btn btn-primary">
                        <x-lucide-plus class="me-2 icon" />
                        Tambah Catatan
                    </button>
                </div>
            @endif
        </div>
        <div class="table-responsive">
            <table class="card-table table table-vcenter">
                <thead>
                    <tr>
                        <th width="15%">Tanggal</th>
                        <th>Aktivitas</th>
                        <th width="12%">Kelompok RAB</th>
                        <th width="12%">Nominal</th>
                        <th width="12%">Progres</th>
                        <th width="12%">Bukti</th>
                        @if ($this->canManage($proposal))
                            <th width="10%">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($notes_list as $note)
                        <tr wire:key="note-{{ $note->id }}">
                            <td class="text-secondary align-top">{{ $note->activity_date->format('d/m/Y') }}</td>
                            <td class="align-top">
                                <div class="fw-bold">{{ $note->activity_description }}</div>
                                @if ($note->notes)
                                    <small class="d-block mt-1 text-muted italic">
                                        <x-lucide-info class="icon-inline me-1 icon"
                                            style="width: 12px; height: 12px;" />
                                        {{ $note->notes }}
                                    </small>
                                @endif
                            </td>
                            <td class="align-top">
                                @if ($note->budgetGroup)
                                    <span class="bg-blue-lt badge">{{ $note->budgetGroup->name }}</span>
                                @else
                                    <span class="text-muted small">Tanpa RAB</span>
                                @endif
                            </td>
                            <td class="align-top">
                                @if ($note->amount)
                                    <div class="fw-bold">Rp {{ number_format($note->amount, 0, ',', '.') }}</div>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="align-top">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="w-100 progress progress-xs">
                                        <div class="bg-blue progress-bar"
                                            style="width: {{ $note->progress_percentage }}%"></div>
                                    </div>
                                    <span class="small">{{ $note->progress_percentage }}%</span>
                                </div>
                            </td>
                            <td class="align-top">
                                @if ($note->media->isNotEmpty())
                                    <div class="d-flex flex-column gap-1">
                                        @foreach ($note->media as $media)
                                            <div class="d-flex align-items-center gap-1">
                                                @if (str_starts_with($media->mime_type, 'image/'))
                                                    @php
                                                        $signedUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]);
                                                    @endphp
                                                    <a href="#"
                                                        data-url="{{ $signedUrl }}"
                                                        @click.prevent="window.ImagePreviewModal.show('image-preview-modal', $el.dataset.url)"
                                                        class="text-decoration-none text-truncate"
                                                        style="max-width: 150px;" title="{{ $media->file_name }}">
                                                        <x-lucide-image class="icon-inline me-1 text-muted icon" />
                                                        <small>{{ $media->file_name }}</small>
                                                    </a>
                                                @else
                                                    <a data-navigate-ignore="true" href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}" target="_blank"
                                                        download="{{ $media->file_name ?? $media->name ?? 'download' }}"
                                                        class="text-decoration-none text-truncate" data-navigate-ignore="true"
                                                        style="max-width: 150px;" title="{{ $media->file_name }}">
                                                        <x-lucide-file-text class="icon-inline me-1 text-muted icon" />
                                                        <small>{{ $media->file_name }}</small>
                                                    </a>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            @if ($this->canManage($proposal))
                                <td class="align-top">
                                    <div class="dropdown">
                                        <button class="align-text-top btn btn-sm dropdown-toggle"
                                            data-bs-toggle="dropdown">
                                            Aksi
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item" href="#"
                                                wire:click.prevent="edit('{{ $note->id }}')">
                                                <x-lucide-pencil class="me-2 icon" />
                                                Edit
                                            </a>
                                            <a class="text-danger dropdown-item" href="#"
                                                wire:confirm="Apakah Anda yakin ingin menghapus catatan ini?"
                                                wire:click.prevent="delete('{{ $note->id }}')">
                                                <x-lucide-trash-2 class="me-2 icon" />
                                                Hapus
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-4 text-muted text-center">
                                Belum ada catatan aktivitas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form -->
    <x-tabler.modal id="daily-note-modal" :title="$editingId ? 'Edit Catatan' : 'Tambah Catatan Baru'" wire:ignore.self size="xl">
        <form wire:submit="save">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Tanggal Kegiatan</label>
                        <input type="date" class="form-control @error('activity_date') is-invalid @enderror"
                            wire:model="activity_date">
                        @error('activity_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <div class="mb-3">
                                <label class="form-label">Kelompok RAB (Opsional)</label>
                                <select class="form-select @error('budget_group_id') is-invalid @enderror"
                                    wire:model="budget_group_id">
                                    <option value="">-- Pilih Kelompok RAB --</option>
                                    @foreach ($budget_groups as $group)
                                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                                    @endforeach
                                </select>
                                @error('budget_group_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Pilih jika aktivitas ini menggunakan anggaran RAB</small>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="mb-3">
                                <label class="form-label">Nominal Digunakan (Rp)</label>
                                <div x-data="moneyInputSingle('amount')">
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" x-model="display" x-ref="input" @focus="handleFocus"
                                            @input="handleInput"
                                            class="form-control @error('amount') is-invalid @enderror" placeholder="0">
                                    </div>
                                    <div x-cloak x-show="display && display !== '0'" class="mt-1 small text-muted text-end">
                                        Ekuivalen dengan <span class="fw-bold text-info" x-text="(((parseInt(display.replace(/[^0-9]/g, '')) || 0) / {{ $total_proposed_budget > 0 ? $total_proposed_budget : 1 }}) * 100).toFixed(2) + '%'"></span> dari total RAB.
                                    </div>
                                </div>
                                @error('amount')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Deskripsi Aktivitas</label>
                        <textarea class="form-control @error('activity_description') is-invalid @enderror" wire:model="activity_description"
                            rows="4" placeholder="Jelaskan aktivitas pengabdian yang dilakukan..."></textarea>
                        @error('activity_description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Progres</label>
                        <div class="input-group">
                            <input type="number" min="0" max="100" class="form-control @error('progress_percentage') is-invalid @enderror" wire:model="progress_percentage" placeholder="25">
                            <span class="input-group-text">%</span>
                        </div>
                        @error('progress_percentage')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catatan Tambahan (Opsional)</label>
                        <input type="text" class="form-control @error('notes') is-invalid @enderror"
                            wire:model="notes" placeholder="Misal: Kendala, respon mitra, dll.">
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Bukti Dukung (Foto/Dokumen)</label>
                        <input type="file" class="form-control @error('evidence.*') is-invalid @enderror"
                            wire:model="evidence" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        @error('evidence.*')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Bisa upload lebih dari satu file (Max 5MB/file). Format: PDF, DOC,
                            DOCX, JPG, PNG</small>

                        <div wire:loading wire:target="evidence" class="mt-2">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            <span class="ms-1 small">Uploading...</span>
                        </div>
                    </div>
                </div>

                <div class="border-start col-md-6">
                    <label class="mb-3 form-label">Preview File</label>

                    <div class="d-flex flex-column gap-3">
                        {{-- New Uploads Preview --}}
                        @if ($evidence)
                            <div class="bg-muted-lt card">
                                <div class="p-2 card-body">
                                    <div class="mb-2 small fw-bold">File Baru:</div>
                                    <div class="row g-2">
                                        @foreach ($evidence as $index => $file)
                                            <div class="col-12">
                                                <div
                                                    class="d-flex align-items-center justify-content-between bg-body-tertiary p-2 border rounded">
                                                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                                                        @if (str_starts_with($file->getMimeType(), 'image/'))
                                                            <img src="{{ $file->temporaryUrl() }}"
                                                                class="rounded object-cover"
                                                                style="width: 40px; height: 40px;">
                                                        @else
                                                            <x-lucide-file class="text-muted icon" />
                                                        @endif
                                                        <div class="text-truncate small"
                                                            title="{{ $file->getClientOriginalName() }}">
                                                            {{ $file->getClientOriginalName() }}
                                                        </div>
                                                    </div>
                                                    <button type="button"
                                                        wire:click="removeEvidence({{ $index }})"
                                                        wire:confirm="Batalkan upload file ini?"
                                                        class="btn btn-icon btn-sm btn-ghost-danger"
                                                        title="Hapus file">
                                                        <x-lucide-x class="icon" />
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Existing Files --}}
                        @if ($editingId)
                            @php
                                $editingNote = $notes_list->firstWhere('id', $editingId);
                            @endphp
                            @if ($editingNote && $editingNote->media->isNotEmpty())
                                <div>
                                    <div class="mb-2 small fw-bold">File Tersimpan:</div>
                                    <div class="row g-2">
                                        @foreach ($editingNote->media as $media)
                                            <div class="col-12">
                                                <div
                                                    class="d-flex align-items-center justify-content-between p-2 border rounded">
                                                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                                                        @if (str_starts_with($media->mime_type, 'image/'))
                                                            @php $signedUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]); @endphp
                                                            <a href="{{ $signedUrl }}" target="_blank" download="{{ $media->file_name ?? $media->name ?? 'download' }}">
                                                                <img src="{{ $signedUrl }}"
                                                                    class="rounded object-cover"
                                                                    style="width: 40px; height: 40px;">
                                                            </a>
                                                        @else
                                                            @php $signedUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]); @endphp
                                                            <a href="{{ $signedUrl }}" target="_blank" download="{{ $media->file_name ?? $media->name ?? 'download' }}">
                                                                <x-lucide-file class="text-muted icon" />
                                                            </a>
                                                        @endif
                                                        <div class="text-truncate small"
                                                            title="{{ $media->file_name }}">
                                                            {{ $media->file_name }}
                                                        </div>
                                                    </div>
                                                    <button type="button"
                                                        wire:click="deleteEvidence('{{ $media->id }}')"
                                                        wire:confirm="Hapus file ini?"
                                                        class="btn btn-icon btn-sm btn-ghost-danger"
                                                        title="Hapus file">
                                                        <x-lucide-x class="icon" />
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif

                        @if (!$evidence && (!$editingId || ($editingNote && $editingNote->media->isEmpty())))
                            <div class="py-5 text-muted text-center">
                                <x-lucide-file-up class="opacity-50 mb-2 icon icon-lg" />
                                <div class="small">Belum ada file yang dipilih/diupload</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal"
                    wire:click="cancelEdit">
                    Batal
                </button>
                <button type="submit" class="ms-auto btn btn-primary">
                    <x-lucide-save class="me-2 icon" />
                    Simpan
                </button>
            </div>
        </form>
    </x-tabler.modal>

    <x-tabler.modal-image id="image-preview-modal" title="Preview Bukti" downloadable="true" />
</div>
