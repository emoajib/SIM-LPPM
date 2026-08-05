<div>
    <x-slot:title>Laporan Akhir - {{ $proposal->title }}</x-slot:title>
    <x-slot:pageTitle>Laporan Akhir</x-slot:pageTitle>
    <x-slot:pageSubtitle>{{ $proposal->title }}</x-slot:pageSubtitle>
    <x-slot:pageActions>
        <div class="btn-list">
            <a href="{{ route('community-service.final-report.index') }}" class="btn-outline-secondary btn" wire:navigate.hover>
                <x-lucide-arrow-left class="icon" />
                Kembali
            </a>
            @if ($progressReport && $progressReport->reporting_period === 'final')
                <a data-navigate-ignore="true"
                    href="{{ route('reports.export-pdf', ['proposal' => $proposal, 'type' => 'final', 'preview' => 1]) }}" target="_blank"
                    class="btn btn-outline-info shadow-sm">
                    <i class="ti ti-eye me-2"></i>
                    Tinjau PDF
                </a>
                <a data-navigate-ignore="true"
                    href="{{ route('reports.export-pdf', ['proposal' => $proposal, 'type' => 'final', 'download' => 'true']) }}"
                    class="btn btn-primary shadow-sm">
                    <x-lucide-download class="icon me-2" />
                    Unduh Laporan
                </a>
            @endif
        </div>
    </x-slot:pageActions>

    <div x-on:close-modal.window="
    const modalId = $event.detail.modalId || $event.detail[0]?.modalId;
    if (modalId) {
        const modalEl = document.getElementById(modalId);
        if (modalEl) {
            const modal = window.getBsModal ? window.getBsModal(modalEl) : (window.bootstrap?.Modal?.getInstance(modalEl) || window.tabler?.bootstrap?.Modal?.getInstance(modalEl));
            if (modal) modal.hide();
        }
    }
">
        <x-tabler.alert />

        <!-- Approval Section -->
        @if ($progressReport)
            @php
                $user = auth()->user();
                $isActiveDekan = active_role_is('dekan');
                $isActiveKepalaLppm = active_role_is('kepala lppm');
                $isSubmitted = $progressReport->status === \App\Enums\ReportStatus::SUBMITTED;
                $isApprovedByDekan = $progressReport->status === \App\Enums\ReportStatus::APPROVED_BY_DEKAN;
                $canApprove = ($isActiveDekan && $isSubmitted) || ($isActiveKepalaLppm && $isApprovedByDekan);
            @endphp

            @if ($canApprove)
                <div class="card mb-3 border-primary border-2 border-dashed">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <h3 class="card-title text-primary mb-1">
                                    <x-lucide-check-circle class="icon me-2" />
                                    Butuh Persetujuan Anda
                                </h3>
                                <p class="text-secondary mb-0">
                                    Silakan tinjau laporan ini dan berikan keputusan Anda.
                                </p>
                            </div>
                            <div class="btn-list">
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal"
                                    data-bs-target="#modalReject">
                                    <x-lucide-x-circle class="icon me-1" />
                                    Tolak Laporan
                                </button>
                                <button type="button" class="btn btn-primary" wire:click="approve" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="approve">
                                        <x-lucide-check-circle class="icon me-1" />
                                        Setujui Laporan
                                    </span>
                                    <span wire:loading wire:target="approve">
                                        <span class="spinner-border spinner-border-sm me-1"></span>
                                        Memproses...
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Status Journey -->
                        <div class="steps steps-blue container-tight">
                            <div
                                class="step-item {{ $progressReport->status !== \App\Enums\ReportStatus::DRAFT ? 'active' : '' }}">
                                Dosen (Diajukan)</div>
                            <div
                                class="step-item {{ !in_array($progressReport->status, [\App\Enums\ReportStatus::DRAFT, \App\Enums\ReportStatus::SUBMITTED]) ? 'active' : '' }}">
                                Dekan</div>
                            <div
                                class="step-item {{ $progressReport->status === \App\Enums\ReportStatus::APPROVED ? 'active' : '' }}">
                                Kepala LPPM (Selesai)</div>
                        </div>
                    </div>
                </div>
            @endif
        @endif

        <!-- Alert Info Workflow -->
        <div class="alert alert-info" role="alert">
            <div class="d-flex">
                <div>
                    <x-lucide-info class="icon alert-icon" />
                </div>
                <div>
                    <h4 class="alert-title">Panduan Pengisian Laporan Akhir</h4>
                    <div class="text-secondary">
                        <p class="mb-2">
                            Silakan periksa dan lengkapi form berikut untuk mengajukan Laporan Akhir.
                        </p>
                        <ol class="mb-0 ps-3">
                            <li>Lengkapi <strong>Ringkasan & Kata Kunci</strong> serta upload dokumen laporan akhir.
                            </li>
                            <li>Klik tombol <strong>Simpan Draft</strong> untuk menyimpan data sementara.</li>
                            <li>Setelah draft tersimpan, kolom upload <strong>Luaran Wajib</strong> dan <strong>Luaran
                                    Tambahan</strong> akan muncul.</li>
                            <li>Upload bukti luaran yang diperlukan pada bagian tersebut.</li>
                            <li>Jika semua data sudah lengkap, klik <strong>Ajukan Laporan Akhir</strong> untuk mengirim
                                laporan.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible mb-3" role="alert">
                <div class="d-flex">
                    <div>
                        <x-lucide-alert-circle class="icon alert-icon" />
                    </div>
                    <div>
                        <h4 class="alert-title">Terdapat kesalahan!</h4>
                        <div class="text-secondary">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Ringkasan & Kata Kunci -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title"><x-lucide-file-text class="icon me-2" />Ringkasan & Kata Kunci</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label required">Ringkasan Akhir</label>
                    <textarea wire:model="form.summaryUpdate" rows="8" class="form-control"
                        placeholder="Masukkan ringkasan akhir penelitian..." @disabled(!$canEdit)></textarea>
                    @error('form.summaryUpdate')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Kata Kunci (Keywords)</label>
                    <input type="text" wire:model="form.keywordsInput" class="form-control"
                        placeholder="Contoh: AI; Machine Learning; IoT" @disabled(!$canEdit) />
                    <small class="form-hint">Pisahkan kata kunci dengan titik koma (;). Contoh: AI; Machine Learning;
                        Deep
                        Learning</small>
                    @error('form.keywordsInput')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label required">Tahun Pelaporan</label>
                    <input type="number" wire:model="form.reportingYear" class="form-control" min="2020" max="2030"
                        @disabled(!$canEdit) />
                    @error('form.reportingYear')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Dokumen Laporan Akhir -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title"><x-lucide-file-text class="icon me-2" />Dokumen Laporan Akhir</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label mb-0 required">File Substansi (PDF)</label>
                        @if($this->templateUrl)
                            <a href="{{ $this->templateUrl }}" class="btn btn-link btn-sm p-0" target="_blank">
                                <x-lucide-download class="icon icon-sm" /> Unduh Template
                            </a>
                        @endif
                    </div>
                    <input type="file" wire:model="substanceFile"
                        class="form-control @error('substanceFile') is-invalid @enderror" accept=".pdf"
                        @disabled(!$canEdit) />
                    @error('substanceFile')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-hint">Maksimal 10MB, format PDF</small>

                    <div wire:loading wire:target="substanceFile">
                        <small class="text-muted">
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            Uploading...
                        </small>
                    </div>

                    @if ($progressReport && $progressReport->hasMedia('substance_file'))
                        @php
                            $media = $progressReport->getFirstMedia('substance_file');
                        @endphp
                        <div class="alert alert-success mb-0 mt-2">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <x-lucide-file-check class="text-success icon me-2" />
                                    <strong>{{ $media->name }}</strong>
                                    <small class="text-muted ms-2">({{ $media->human_readable_size }})</small>
                                </div>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a data-navigate-ignore="true"
                                        href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                        target="_blank" class="btn btn-sm btn-primary">
                                        <x-lucide-eye class="icon" /> Lihat
                                    </a>
                                    @if ($canEdit)
                                        <button type="button" wire:click="removeSubstanceFile"
                                            class="btn btn-sm btn-danger" wire:confirm="Yakin ingin menghapus file ini?">
                                            <x-lucide-trash-2 class="icon" /> Hapus
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mb-3">
                    <label class="form-label">File Realisasi Keterlibatan (PDF/DOCX)</label>
                    <input type="file" wire:model="realizationFile"
                        class="form-control @error('realizationFile') is-invalid @enderror" accept=".pdf,.docx"
                        @disabled(!$canEdit) />
                    @error('realizationFile')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-hint">Maksimal 10MB, format PDF atau DOCX</small>

                    <div wire:loading wire:target="realizationFile">
                        <small class="text-muted">
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            Uploading...
                        </small>
                    </div>

                    @if ($progressReport && $progressReport->hasMedia('realization_file'))
                        @php
                            $media = $progressReport->getFirstMedia('realization_file');
                        @endphp
                        <div class="alert alert-success mb-0 mt-2">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <x-lucide-file-check class="text-success icon me-2" />
                                    <strong>{{ $media->name }}</strong>
                                    <small class="text-muted ms-2">({{ $media->human_readable_size }})</small>
                                </div>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a data-navigate-ignore="true"
                                        href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                        target="_blank" class="btn btn-sm btn-primary">
                                        <x-lucide-eye class="icon" /> Lihat
                                    </a>
                                    @if ($canEdit)
                                        <button type="button" wire:click="removeRealizationFile"
                                            class="btn btn-sm btn-danger" wire:confirm="Yakin ingin menghapus file ini?">
                                            <x-lucide-trash-2 class="icon" /> Hapus
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mb-3">
                    <label class="form-label">File Presentasi Hasil (PDF/PPTX)</label>
                    <input type="file" wire:model="presentationFile"
                        class="form-control @error('presentationFile') is-invalid @enderror" accept=".pdf,.pptx"
                        @disabled(!$canEdit) />
                    @error('presentationFile')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-hint">Maksimal 50MB, format PDF atau PPTX</small>

                    <div wire:loading wire:target="presentationFile">
                        <small class="text-muted">
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            Uploading...
                        </small>
                    </div>

                    @if ($progressReport && $progressReport->hasMedia('presentation_file'))
                        @php
                            $media = $progressReport->getFirstMedia('presentation_file');
                        @endphp
                        <div class="alert alert-success mb-0 mt-2">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <x-lucide-file-check class="text-success icon me-2" />
                                    <strong>{{ $media->name }}</strong>
                                    <small class="text-muted ms-2">({{ $media->human_readable_size }})</small>
                                </div>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a data-navigate-ignore="true"
                                        href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                        target="_blank" class="btn btn-sm btn-primary">
                                        <x-lucide-eye class="icon" /> Lihat
                                    </a>
                                    @if ($canEdit)
                                        <button type="button" wire:click="removePresentationFile"
                                            class="btn btn-sm btn-danger" wire:confirm="Yakin ingin menghapus file ini?">
                                            <x-lucide-trash-2 class="icon" /> Hapus
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                @if ($this->reportApprovalMode === 'upload')
                    <div class="mb-3 border-top pt-3 mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0 required">Halaman Pengesahan (Scan Tanda Tangan Fisik)</label>
                            <button type="button" wire:click="downloadReportApprovalPageTemplate"
                                class="btn btn-link btn-sm p-0">
                                <x-lucide-download class="icon icon-sm" /> Unduh Template Halaman Pengesahan
                            </button>
                        </div>
                        <input type="file" wire:model="signatureFile"
                            class="form-control @error('signatureFile') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png"
                            @disabled(!$canEdit) />
                        @error('signatureFile')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-hint">Maksimal 5MB, format PDF atau Gambar (JPG/PNG)</small>

                        <div wire:loading wire:target="signatureFile">
                            <small class="text-muted">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Uploading...
                            </small>
                        </div>

                        @if ($progressReport && $progressReport->hasMedia('signature_page'))
                            @php
                                $media = $progressReport->getFirstMedia('signature_page');
                            @endphp
                            <div class="bg-blue-lt mt-2 rounded border p-2">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <x-lucide-file-check class="text-blue icon me-2" />
                                        <strong>{{ $media->name }}</strong>
                                        <small class="text-muted ms-2">({{ $media->human_readable_size }})</small>
                                    </div>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a data-navigate-ignore="true"
                                            href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                            target="_blank" class="btn btn-sm btn-primary">
                                            <x-lucide-eye class="icon" /> Lihat
                                        </a>
                                        @if ($canEdit)
                                            <button type="button" wire:click="removeSignatureFile"
                                                class="btn btn-sm btn-danger" wire:confirm="Yakin ingin menghapus halaman pengesahan ini?">
                                                <x-lucide-trash-2 class="icon" /> Hapus
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Luaran Wajib -->
    @if ($isFinalReportDraft)
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title"><x-lucide-book-open class="icon me-2" />Luaran Wajib</h3>
            </div>
            <div class="card-body">
                @php
                    $wajibs = $proposal->outputs->where('category', 'Wajib');
                @endphp

                @if ($wajibs->isNotEmpty())
                    <div class="table-responsive">
                        <table class="card-table table-vcenter table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Jenis Luaran</th>
                                    <th>Tahun Target</th>
                                    <th>Target Status</th>
                                    <th>Status Input</th>
                                    <th>Dokumen</th>
                                    <th class="w-1">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($wajibs as $index => $output)
                                    @php
                                        $rowMandatoryOutput = $mandatoryOutputsMap->get($output->id);
                                    @endphp
                                    <tr wire:key="wajib-row-{{ $output->id }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div class="fw-bold">{{ $output->type }}</div>
                                        </td>
                                        <td>{{ $output->output_year }}</td>
                                        <td>
                                            <x-tabler.badge variant="outline">
                                                {{ $output->target_status }}
                                            </x-tabler.badge>
                                        </td>
                                        <td>
                                            @php
                                                $hasData =
                                                    isset($form->mandatoryOutputs[$output->id]['status_type']) &&
                                                    !empty($form->mandatoryOutputs[$output->id]['status_type']);
                                            @endphp
                                            @if ($hasData)
                                                <x-tabler.badge color="success">
                                                    <x-lucide-check class="icon icon-sm" />
                                                    Sudah Diisi
                                                </x-tabler.badge>
                                            @else
                                                <x-tabler.badge color="secondary">
                                                    Belum Diisi
                                                </x-tabler.badge>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($rowMandatoryOutput && $rowMandatoryOutput->hasMedia('journal_article'))
                                                @php
                                                    $media = $rowMandatoryOutput->getFirstMedia('journal_article');
                                                @endphp
                                                <a data-navigate-ignore="true"
                                                    href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                                    target="_blank" class="btn btn-sm btn-success">
                                                    <x-lucide-file-check class="icon icon-sm" />
                                                    Lihat Dokumen
                                                </a>
                                            @else
                                                <span class="text-muted">
                                                    <x-lucide-file-x class="icon icon-sm" />
                                                    Belum Upload
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($canEdit)
                                                <button type="button" wire:click="editMandatoryOutput({{ $output->id }})"
                                                    class="btn btn-sm btn-animate-icon btn-animate-icon-rotate" data-bs-toggle="modal"
                                                    data-bs-target="#modalMandatoryOutput" title="Edit Luaran Wajib"
                                                    aria-label="Edit Luaran Wajib">
                                                    <x-lucide-pencil class="icon" />
                                                </button>
                                            @else
                                                <button type="button" wire:click="editMandatoryOutput({{ $output->id }})"
                                                    class="btn btn-sm btn-animate-icon btn-animate-icon-rotate" data-bs-toggle="modal"
                                                    data-bs-target="#modalMandatoryOutput" title="Lihat Luaran Wajib"
                                                    aria-label="Lihat Luaran Wajib">
                                                    <x-lucide-eye class="icon" />
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted py-4 text-center">
                        <x-lucide-inbox class="icon icon-lg mb-2" />
                        <p>Tidak ada luaran wajib yang direncanakan</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Luaran Tambahan -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title"><x-lucide-book class="icon me-2" />Luaran Tambahan</h3>
            </div>
            <div class="card-body">
                @php
                    $tambahans = $proposal->outputs->where('category', 'Tambahan');
                @endphp

                @if ($tambahans->isNotEmpty())
                    <div class="table-responsive">
                        <table class="card-table table-vcenter table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Jenis Luaran</th>
                                    <th>Tahun Target</th>
                                    <th>Status Input</th>
                                    <th>Dokumen</th>
                                    <th class="w-1">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tambahans as $index => $output)
                                    @php
                                        $rowAdditionalOutput = $additionalOutputsMap->get($output->id);
                                    @endphp
                                    <tr wire:key="tambahan-row-{{ $output->id }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div class="fw-bold">{{ $output->type }}</div>
                                        </td>
                                        <td>{{ $output->output_year }}</td>
                                        <td>
                                            @php
                                                $hasData =
                                                    isset($form->additionalOutputs[$output->id]['status']) &&
                                                    !empty($form->additionalOutputs[$output->id]['status']);
                                            @endphp
                                            @if ($hasData)
                                                <x-tabler.badge color="success">
                                                    <x-lucide-check class="icon icon-sm" />
                                                    Sudah Diisi
                                                </x-tabler.badge>
                                            @else
                                                <x-tabler.badge color="secondary">
                                                    Belum Diisi
                                                </x-tabler.badge>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($rowAdditionalOutput)
                                                <div class="d-flex gap-2">
                                                    @if ($rowAdditionalOutput->hasMedia('book_document'))
                                                        @php
                                                            $media = $rowAdditionalOutput->getFirstMedia(
                                                                'book_document',
                                                            );
                                                        @endphp
                                                        <a data-navigate-ignore="true"
                                                            href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                                            target="_blank" class="btn btn-sm btn-success">
                                                            <x-lucide-book class="icon icon-sm" />
                                                            Buku
                                                        </a>
                                                    @endif

                                                    @if ($rowAdditionalOutput->hasMedia('publication_certificate'))
                                                        @php
                                                            $media = $rowAdditionalOutput->getFirstMedia(
                                                                'publication_certificate',
                                                            );
                                                        @endphp
                                                        <a data-navigate-ignore="true"
                                                            href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                                            target="_blank" class="btn btn-sm btn-info">
                                                            <x-lucide-award class="icon icon-sm" />
                                                            Sertifikat
                                                        </a>
                                                    @endif
                                                </div>

                                                @if (!$rowAdditionalOutput->hasMedia('book_document') && !$rowAdditionalOutput->hasMedia('publication_certificate'))
                                                    <span class="text-muted">
                                                        <x-lucide-file-x class="icon icon-sm" />
                                                        Belum Upload
                                                    </span>
                                                @endif
                                            @else
                                                <span class="text-muted">
                                                    <x-lucide-file-x class="icon icon-sm" />
                                                    Belum Upload
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($canEdit)
                                                <button type="button" wire:click="editAdditionalOutput({{ $output->id }})"
                                                    class="btn btn-sm btn-animate-icon btn-animate-icon-rotate" data-bs-toggle="modal"
                                                    data-bs-target="#modalAdditionalOutput" title="Edit Luaran Tambahan"
                                                    aria-label="Edit Luaran Tambahan">
                                                    <x-lucide-pencil class="icon" />
                                                </button>
                                            @else
                                                <button type="button" wire:click="editAdditionalOutput({{ $output->id }})"
                                                    class="btn btn-sm btn-animate-icon btn-animate-icon-rotate" data-bs-toggle="modal"
                                                    data-bs-target="#modalAdditionalOutput" title="Lihat Luaran Tambahan"
                                                    aria-label="Lihat Luaran Tambahan">
                                                    <x-lucide-eye class="icon" />
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted py-4 text-center">
                        <x-lucide-inbox class="icon icon-lg mb-2" />
                        <p>Tidak ada luaran tambahan yang direncanakan</p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Action Buttons -->
    @if ($canEdit)
        @if ($isFinalReportDraft)
            <div class="alert alert-warning mb-3" role="alert">
                <div class="d-flex">
                    <div>
                        <x-lucide-alert-triangle class="icon alert-icon" />
                    </div>
                    <div>
                        <h4 class="alert-title">Persyaratan Pengajuan Laporan</h4>
                        <div class="text-secondary">
                            Pastikan total pengeluaran pada menu Catatan Harian (Logbook) telah mencapai 100% dari total Pagu
                            RAB sebelum mengajukan Laporan Akhir. Sistem akan memblokir pengajuan jika serapan dana belum genap
                            100%.
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <div class="card">
            <div class="card-body">
                <div class="justify-content-end btn-list">
                    @if (!$isFinalReportDraft)
                        <button type="button" wire:click="save" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="save">
                                <x-lucide-save class="icon" /> Simpan Draft
                            </span>
                            <span wire:loading wire:target="save">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Menyimpan...
                            </span>
                        </button>
                    @else
                        <button type="button" wire:click="submit" class="btn btn-success" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="submit">
                                <x-lucide-send class="icon" /> Ajukan Laporan Akhir
                            </span>
                            <span wire:loading wire:target="submit">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Mengajukan...
                            </span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Modal: Mandatory Output -->
    @teleport('body')
    @php
        $modalMandatoryTitle = 'Luaran Wajib';
        if ($form->editingMandatoryId) {
            $currentOutput = $proposal->outputs->find($form->editingMandatoryId);
            if ($currentOutput) {
                $modalMandatoryTitle .= ' - ' . $currentOutput->type;
            }
        }
    @endphp
    <x-tabler.modal id="modalMandatoryOutput" title="{{ $canEdit ? 'Edit' : 'Lihat' }} {{ $modalMandatoryTitle }}"
        size="xl" scrollable wire:ignore.self onHide="closeMandatoryModal">

        <x-slot:body>
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <div class="d-flex">
                        <div>
                            <x-lucide-alert-circle class="icon alert-icon" />
                        </div>
                        <div>
                            <h4 class="alert-title">Terdapat kesalahan pada form!</h4>
                            <div class="text-secondary">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="close"></button>
                </div>
            @endif

            @if ($form->editingMandatoryId)
                @php
                    $currentOutput = $proposal->outputs->find($form->editingMandatoryId);
                    $outputType = $currentOutput?->type ?? '';
                    $outputGroup = $currentOutput?->group ?? '';
                @endphp

                <div class="row g-3">
                    <!-- Common Fields -->
                    <div class="col-md-6">
                        <label class="form-label required">Status</label>
                        <select wire:model="form.mandatoryOutputs.{{ $form->editingMandatoryId }}.status_type"
                            class="form-select" @disabled(!$canEdit)>
                            <option value="">Pilih Status</option>
                            <option value="draft">Draft</option>
                            <option value="submitted">Submitted</option>
                            <option value="under_review">Under Review</option>
                            <option value="accepted">Accepted</option>
                            <option value="published">Published</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        @error("form.mandatoryOutputs.{$form->editingMandatoryId}.status_type")
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- JURNAL/PROSIDING Fields -->
                    @if (
                            str_contains(strtolower($outputType), 'jurnal') ||
                            str_contains(strtolower($outputGroup), 'jurnal') ||
                            str_contains(strtolower($outputType), 'prosiding') ||
                            str_contains(strtolower($outputGroup), 'prosiding')
                        )
                        <div class="col-md-6">
                            <label class="form-label required">Status Penulis</label>
                            <select wire:model="form.mandatoryOutputs.{{ $form->editingMandatoryId }}.author_status"
                                class="form-select" @disabled(!$canEdit)>
                                <option value="">Pilih Status</option>
                                <option value="first_author">First Author</option>
                                <option value="co_author">Co-Author</option>
                                <option value="corresponding_author">Corresponding Author</option>
                            </select>
                            @error("form.mandatoryOutputs.{$form->editingMandatoryId}.author_status")
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label required">Judul
                                {{ str_contains(strtolower($outputType), 'prosiding') ? 'Prosiding' : 'Jurnal' }}</label>
                            <input type="text" wire:model="form.mandatoryOutputs.{{ $form->editingMandatoryId }}.journal_title"
                                class="form-control" placeholder="Masukkan judul" @disabled(!$canEdit) />
                            @error("form.mandatoryOutputs.{$form->editingMandatoryId}.journal_title")
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label
                                class="form-label">{{ str_contains(strtolower($outputType), 'prosiding') ? 'ISBN' : 'ISSN' }}</label>
                            <input type="text" wire:model="form.mandatoryOutputs.{{ $form->editingMandatoryId }}.issn"
                                class="form-control" placeholder="1234-5678" @disabled(!$canEdit) />
                        </div>
                        <div class="col-md-6">
                            <label
                                class="form-label">E-{{ str_contains(strtolower($outputType), 'prosiding') ? 'ISBN' : 'ISSN' }}</label>
                            <input type="text" wire:model="form.mandatoryOutputs.{{ $form->editingMandatoryId }}.eissn"
                                class="form-control" placeholder="1234-5678" @disabled(!$canEdit) />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Lembaga Pengindeks</label>
                            <select wire:model="form.mandatoryOutputs.{{ $form->editingMandatoryId }}.indexing_body"
                                class="form-select" @disabled(!$canEdit)>
                                <option value="">-- Pilih Lembaga --</option>
                                <option value="SINTA">SINTA</option>
                                <option value="Scopus">Scopus</option>
                                <option value="Nasional">Nasional (Non-SINTA)</option>
                                <option value="Internasional">Internasional (Non-Scopus)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Peringkat (Jika Ada)</label>
                            <select wire:model="form.mandatoryOutputs.{{ $form->editingMandatoryId }}.rank" class="form-select"
                                @disabled(!$canEdit)>
                                <option value="">-- Pilih Peringkat --</option>
                                <optgroup label="SINTA">
                                    <option value="S1">S1</option>
                                    <option value="S2">S2</option>
                                    <option value="S3">S3</option>
                                    <option value="S4">S4</option>
                                    <option value="S5">S5</option>
                                    <option value="S6">S6</option>
                                </optgroup>
                                <optgroup label="Scopus">
                                    <option value="Q1">Q1</option>
                                    <option value="Q2">Q2</option>
                                    <option value="Q3">Q3</option>
                                    <option value="Q4">Q4</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label required">Judul Artikel</label>
                            <input type="text" wire:model="form.mandatoryOutputs.{{ $form->editingMandatoryId }}.article_title"
                                class="form-control" placeholder="Masukkan judul artikel" @disabled(!$canEdit) />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">URL Publikasi</label>
                            <input type="url" wire:model="form.mandatoryOutputs.{{ $form->editingMandatoryId }}.journal_url"
                                class="form-control" placeholder="https://" @disabled(!$canEdit) />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">URL Artikel</label>
                            <input type="url" wire:model="form.mandatoryOutputs.{{ $form->editingMandatoryId }}.article_url"
                                class="form-control" placeholder="https://" @disabled(!$canEdit) />
                        </div>
                    @endif

                    <!-- BUKU Fields -->
                    @if (str_contains(strtolower($outputType), 'buku') || str_contains(strtolower($outputGroup), 'buku'))
                        <div class="col-md-12">
                            <label class="form-label required">Judul Buku</label>
                            <input type="text" wire:model="form.mandatoryOutputs.{{ $form->editingMandatoryId }}.book_title"
                                class="form-control" placeholder="Masukkan judul buku" @disabled(!$canEdit) />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ISBN</label>
                            <input type="text" wire:model="form.mandatoryOutputs.{{ $form->editingMandatoryId }}.isbn"
                                class="form-control" placeholder="ISBN" @disabled(!$canEdit) />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Penerbit</label>
                            <input type="text" wire:model="form.mandatoryOutputs.{{ $form->editingMandatoryId }}.publisher"
                                class="form-control" placeholder="Nama Penerbit" @disabled(!$canEdit) />
                        </div>
                    @endif

                    <!-- HKI Fields -->
                    @if (
                            str_contains(strtolower($outputType), 'hki') ||
                            str_contains(strtolower($outputType), 'paten') ||
                            str_contains(strtolower($outputType), 'hak cipta') ||
                            str_contains(strtolower($outputGroup), 'hki')
                        )
                        <div class="col-md-6">
                            <label class="form-label required">Jenis HKI</label>
                            <input type="text" wire:model="form.mandatoryOutputs.{{ $form->editingMandatoryId }}.hki_type"
                                class="form-control" placeholder="Paten, Hak Cipta, dll" @disabled(!$canEdit) />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nomor Pendaftaran/Sertifikat</label>
                            <input type="text"
                                wire:model="form.mandatoryOutputs.{{ $form->editingMandatoryId }}.registration_number"
                                class="form-control" placeholder="Nomor" @disabled(!$canEdit) />
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Inventor</label>
                            <input type="text" wire:model="form.mandatoryOutputs.{{ $form->editingMandatoryId }}.inventors"
                                class="form-control" placeholder="Nama Inventor" @disabled(!$canEdit) />
                        </div>
                    @endif

                    <!-- MEDIA MASSA Fields -->
                    @if (str_contains(strtolower($outputType), 'media') || str_contains(strtolower($outputGroup), 'media'))
                        <div class="col-md-12">
                            <label class="form-label required">Nama Media Massa</label>
                            <input type="text" wire:model="form.mandatoryOutputs.{{ $form->editingMandatoryId }}.media_name"
                                class="form-control" placeholder="Kompas, Detik, dll" @disabled(!$canEdit) />
                        </div>
                        <div class="col-md-12">
                            <label class="form-label required">URL Berita</label>
                            <input type="url" wire:model="form.mandatoryOutputs.{{ $form->editingMandatoryId }}.media_url"
                                class="form-control" placeholder="https://" @disabled(!$canEdit) />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Tanggal Terbit</label>
                            <input type="date"
                                wire:model="form.mandatoryOutputs.{{ $form->editingMandatoryId }}.publication_date"
                                class="form-control" @disabled(!$canEdit) />
                        </div>
                    @endif

                    <!-- VIDEO Fields -->
                    @if (str_contains(strtolower($outputType), 'video') || str_contains(strtolower($outputGroup), 'video'))
                        <div class="col-md-12">
                            <label class="form-label required">URL Video</label>
                            <input type="url" wire:model="form.mandatoryOutputs.{{ $form->editingMandatoryId }}.video_url"
                                class="form-control" placeholder="https://youtube.com/..." @disabled(!$canEdit) />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Platform</label>
                            <select wire:model="form.mandatoryOutputs.{{ $form->editingMandatoryId }}.platform"
                                class="form-select" @disabled(!$canEdit)>
                                <option value="">Pilih Platform</option>
                                <option value="YouTube">YouTube</option>
                                <option value="Instagram">Instagram</option>
                                <option value="TikTok">TikTok</option>
                                <option value="Facebook">Facebook</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                    @endif

                    <!-- PRODUK/TTG Fields -->
                    @if (
                            str_contains(strtolower($outputType), 'produk') ||
                            str_contains(strtolower($outputType), 'ttg') ||
                            str_contains(strtolower($outputGroup), 'produk')
                        )
                        <div class="col-md-12">
                            <label class="form-label required">Nama Produk/TTG</label>
                            <input type="text" wire:model="form.mandatoryOutputs.{{ $form->editingMandatoryId }}.product_name"
                                class="form-control" placeholder="Masukkan nama produk/TTG" @disabled(!$canEdit) />
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Deskripsi Singkat</label>
                            <textarea wire:model="form.mandatoryOutputs.{{ $form->editingMandatoryId }}.description"
                                class="form-control" rows="3" placeholder="Deskripsi produk/TTG"
                                @disabled(!$canEdit)></textarea>
                        </div>
                    @endif

                    <!-- Common Year Field -->
                    <div class="col-md-3">
                        <label class="form-label required">Tahun</label>
                        <input type="number"
                            wire:model="form.mandatoryOutputs.{{ $form->editingMandatoryId }}.publication_year"
                            class="form-control" min="2000" max="2030" @disabled(!$canEdit) />
                    </div>

                    <!-- File Upload -->
                    <div class="col-md-12">
                        <label class="form-label">Dokumen Bukti (PDF)</label>
                        <input type="file" wire:model="tempMandatoryFiles.{{ $form->editingMandatoryId }}"
                            class="form-control" accept=".pdf" @disabled(!$canEdit) />
                        @error("tempMandatoryFiles.{$form->editingMandatoryId}")
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                        <div wire:loading wire:target="tempMandatoryFiles.{{ $form->editingMandatoryId }}">
                            <small class="text-muted">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Uploading...
                            </small>
                        </div>
                        @if ($mandatoryOutput = $this->mandatoryOutput())
                            @if ($media = $mandatoryOutput->getFirstMedia('journal_article'))
                                <div class="bg-body-tertiary mt-2 rounded border p-2">
                                    <div class="d-flex align-items-center">
                                        <x-lucide-file-text class="text-primary icon me-2" />
                                        <div class="flex-fill">
                                            <small class="text-muted">File yang sudah diunggah:</small><br>
                                            <strong>{{ $media->name }}</strong>
                                            <small class="text-muted">({{ number_format($media->size / 1024, 2) }}
                                                KB)</small>
                                        </div>
                                        <a data-navigate-ignore="true"
                                            href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                            target="_blank" class="btn btn-sm btn-primary">
                                            <x-lucide-download class="icon" /> Download
                                        </a>
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @else
                <p class="text-muted">Tidak ada data yang sedang diedit</p>
            @endif
        </x-slot:body>

        <x-slot:footer>
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                Tutup
            </button>
            @if ($canEdit)
                <button type="button" wire:click="saveMandatoryOutput({{ $form->editingMandatoryId }})"
                    class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveMandatoryOutput">
                        <x-lucide-save class="icon" /> Simpan
                    </span>
                    <span wire:loading wire:target="saveMandatoryOutput">
                        <span class="spinner-border spinner-border-sm me-2"></span>
                        Menyimpan...
                    </span>
                </button>
            @endif
        </x-slot:footer>
    </x-tabler.modal>
    @endteleport

    <!-- Modal: Additional Output -->
    @teleport('body')
    @php
        $modalAdditionalTitle = 'Luaran Tambahan';
        if ($form->editingAdditionalId) {
            $currentOutput = $proposal->outputs->find($form->editingAdditionalId);
            if ($currentOutput) {
                $modalAdditionalTitle .= ' - ' . $currentOutput->type;
            }
        }
    @endphp
    <x-tabler.modal id="modalAdditionalOutput" title="{{ $canEdit ? 'Edit' : 'Lihat' }} {{ $modalAdditionalTitle }}"
        size="lg" scrollable wire:ignore.self onHide="closeAdditionalModal">

        <x-slot:body>
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <div class="d-flex">
                        <div>
                            <x-lucide-alert-circle class="icon alert-icon" />
                        </div>
                        <div>
                            <h4 class="alert-title">Terdapat kesalahan pada form!</h4>
                            <div class="text-secondary">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="close"></button>
                </div>
            @endif

            @if ($form->editingAdditionalId)
                <div class="row g-3">
                    <!-- Status -->
                    <div class="col-md-12">
                        <label class="form-label required">Status</label>
                        <select wire:model="form.additionalOutputs.{{ $form->editingAdditionalId }}.status"
                            class="form-select" @disabled(!$canEdit)>
                            <option value="">Pilih Status</option>
                            <option value="draft">Draft</option>
                            <option value="submitted">Submitted</option>
                            <option value="under_review">Under Review</option>
                            <option value="accepted">Accepted</option>
                            <option value="published">Published</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        @error("form.additionalOutputs.{$form->editingAdditionalId}.status")
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- Book Title -->
                    <div class="col-md-12">
                        <label class="form-label required">Judul Buku</label>
                        <input type="text" wire:model="form.additionalOutputs.{{ $form->editingAdditionalId }}.book_title"
                            class="form-control" placeholder="Masukkan judul buku" @disabled(!$canEdit) />
                        @error("form.additionalOutputs.{$form->editingAdditionalId}.book_title")
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- Publisher Name -->
                    <div class="col-md-6">
                        <label class="form-label required">Nama Penerbit</label>
                        <input type="text"
                            wire:model="form.additionalOutputs.{{ $form->editingAdditionalId }}.publisher_name"
                            class="form-control" placeholder="Masukkan nama penerbit" @disabled(!$canEdit) />
                        @error("form.additionalOutputs.{$form->editingAdditionalId}.publisher_name")
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- ISBN -->
                    <div class="col-md-6">
                        <label class="form-label">ISBN</label>
                        <input type="text" wire:model="form.additionalOutputs.{{ $form->editingAdditionalId }}.isbn"
                            class="form-control" placeholder="978-xxx-xxx-xxx-x" @disabled(!$canEdit) />
                        @error("form.additionalOutputs.{$form->editingAdditionalId}.isbn")
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- Publication Year -->
                    <div class="col-md-6">
                        <label class="form-label">Tahun Terbit</label>
                        <input type="number"
                            wire:model="form.additionalOutputs.{{ $form->editingAdditionalId }}.publication_year"
                            class="form-control" min="2000" max="2030" @disabled(!$canEdit) />
                        @error("form.additionalOutputs.{$form->editingAdditionalId}.publication_year")
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- Total Pages -->
                    <div class="col-md-6">
                        <label class="form-label">Jumlah Halaman</label>
                        <input type="number"
                            wire:model="form.additionalOutputs.{{ $form->editingAdditionalId }}.total_pages"
                            class="form-control" placeholder="100" @disabled(!$canEdit) />
                        @error("form.additionalOutputs.{$form->editingAdditionalId}.total_pages")
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- Publisher URL -->
                    <div class="col-md-6">
                        <label class="form-label">URL Web Penerbit</label>
                        <input type="url" wire:model="form.additionalOutputs.{{ $form->editingAdditionalId }}.publisher_url"
                            class="form-control" placeholder="https://" @disabled(!$canEdit) />
                        @error("form.additionalOutputs.{$form->editingAdditionalId}.publisher_url")
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- Book URL -->
                    <div class="col-md-6">
                        <label class="form-label">URL Buku</label>
                        <input type="url" wire:model="form.additionalOutputs.{{ $form->editingAdditionalId }}.book_url"
                            class="form-control" placeholder="https://" @disabled(!$canEdit) />
                        @error("form.additionalOutputs.{$form->editingAdditionalId}.book_url")
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- Document File -->
                    <div class="col-md-6">
                        <label class="form-label">Dokumen Buku/Draft</label>
                        <input type="file" wire:model="tempAdditionalFiles.{{ $form->editingAdditionalId }}"
                            class="form-control" accept=".pdf" @disabled(!$canEdit) />
                        @error("tempAdditionalFiles.{$form->editingAdditionalId}")
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                        <div wire:loading wire:target="tempAdditionalFiles.{{ $form->editingAdditionalId }}">
                            <small class="text-muted">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Uploading...
                            </small>
                        </div>
                        @if ($additionalOutput = $this->additionalOutput)
                            @if ($media = $additionalOutput->getFirstMedia('book_document'))
                                <div class="bg-body-tertiary mt-2 rounded border p-2">
                                    <div class="d-flex align-items-center">
                                        <x-lucide-file-text class="text-primary icon me-2" />
                                        <div class="flex-fill">
                                            <small class="text-muted">File yang sudah diunggah:</small><br>
                                            <strong>{{ $media->name }}</strong>
                                            <small class="text-muted">({{ number_format($media->size / 1024, 2) }}
                                                KB)</small>
                                        </div>
                                        <a data-navigate-ignore="true"
                                            href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                            target="_blank" class="btn btn-sm btn-primary">
                                            <x-lucide-download class="icon" /> Download
                                        </a>
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>

                    <!-- Publication Certificate -->
                    <div class="col-md-6">
                        <label class="form-label">Surat Keterangan Terbit</label>
                        <input type="file" wire:model="tempAdditionalCerts.{{ $form->editingAdditionalId }}"
                            class="form-control" accept=".pdf" @disabled(!$canEdit) />
                        @error("tempAdditionalCerts.{$form->editingAdditionalId}")
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                        <div wire:loading wire:target="tempAdditionalCerts.{{ $form->editingAdditionalId }}">
                            <small class="text-muted">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Uploading...
                            </small>
                        </div>
                        @if ($additionalOutput = $this->additionalOutput)
                            @if ($media = $additionalOutput->getFirstMedia('publication_certificate'))
                                <div class="bg-body-tertiary mt-2 rounded border p-2">
                                    <div class="d-flex align-items-center">
                                        <x-lucide-file-text class="text-primary icon me-2" />
                                        <div class="flex-fill">
                                            <small class="text-muted">File yang sudah diunggah:</small><br>
                                            <strong>{{ $media->name }}</strong>
                                            <small class="text-muted">({{ number_format($media->size / 1024, 2) }}
                                                KB)</small>
                                        </div>
                                        <a data-navigate-ignore="true"
                                            href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                            target="_blank" class="btn btn-sm btn-primary">
                                            <x-lucide-download class="icon" /> Download
                                        </a>
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @else
                <p class="text-muted">Tidak ada data yang sedang diedit</p>
            @endif
        </x-slot:body>

        <x-slot:footer>
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                Tutup
            </button>
            @if ($canEdit)
                <button type="button" wire:click="saveAdditionalOutput({{ $form->editingAdditionalId }})"
                    class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveAdditionalOutput">
                        <x-lucide-save class="icon" /> Simpan
                    </span>
                    <span wire:loading wire:target="saveAdditionalOutput">
                        <span class="spinner-border spinner-border-sm me-2"></span>
                        Menyimpan...
                    </span>
                </button>
            @endif
        </x-slot:footer>
    </x-tabler.modal>
    @endteleport

    <!-- Modal: Reject Laporan -->
    <x-tabler.modal id="modalReject" title="Tolak Laporan Akhir" size="md" wire:ignore.self>
        <x-slot:body>
            <div class="mb-3">
                <label class="form-label required">Alasan Penolakan / Catatan Perbaikan</label>
                <textarea wire:model="approvalNotes" class="form-control" rows="5"
                    placeholder="Berikan alasan mengapa laporan ini ditolak atau apa yang perlu diperbaiki..."></textarea>
                @error('approvalNotes')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <p class="text-secondary small">
                <x-lucide-info class="icon icon-sm" />
                Laporan yang ditolak akan dikembalikan statusnya ke Draft dan Dosen harus memperbaikinya.
            </p>
        </x-slot:body>
        <x-slot:footer>
            <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Batal</button>
            <button type="button" class="btn btn-danger" wire:click="reject" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="reject">Tolak & Kembalikan</span>
                <span wire:loading wire:target="reject">
                    <span class="spinner-border spinner-border-sm me-1"></span>
                    Memproses...
                </span>
            </button>
        </x-slot:footer>
    </x-tabler.modal>
</div>
</div>