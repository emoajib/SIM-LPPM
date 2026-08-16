{{--
    Partial: Form Edit Luaran Tambahan (kondisional berdasarkan group)
    Variables yang dibutuhkan:
      - $outputId        : ID proposal_output (key di form->additionalOutputs)
      - $outputGroup     : group dari ProposalOutput ('buku','jurnal','prosiding','hki','produk','video','media')
      - $form            : ReportForm instance
      - $canEdit         : bool
      - $additionalOutput: AdditionalOutput model (bisa null)
    Vetted by AI - Manual Review Required by Senior Engineer/Manager
--}}
@php
    $group = strtolower($outputGroup ?? '');
    $id    = $outputId;
@endphp

<div class="row g-3">

    {{-- STATUS (semua tipe) --}}
    <div class="col-md-12">
        <label class="form-label required">Status</label>
        <select wire:model="form.additionalOutputs.{{ $id }}.status"
            class="form-select" @disabled(!$canEdit)>
            <option value="">Pilih Status</option>
            <option value="draft">Draft</option>
            <option value="submitted">Submitted</option>
            <option value="under_review">Under Review</option>
            <option value="accepted">Accepted</option>
            <option value="published">Published</option>
            <option value="rejected">Rejected</option>
        </select>
        @error("form.additionalOutputs.{$id}.status")
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    {{-- VIDEO --}}
    @if ($group === 'video')
        <div class="col-md-12">
            <label class="form-label required">URL Video (YouTube / Medsos)</label>
            <input type="url" wire:model="form.additionalOutputs.{{ $id }}.video_url"
                class="form-control" placeholder="https://youtube.com/watch?v=..." @disabled(!$canEdit) />
            @error("form.additionalOutputs.{$id}.video_url")
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Platform</label>
            <input type="text" wire:model="form.additionalOutputs.{{ $id }}.platform"
                class="form-control" placeholder="YouTube / Instagram / TikTok / dll" @disabled(!$canEdit) />
        </div>
        <div class="col-md-6">
            <label class="form-label">Tanggal Publikasi</label>
            <input type="date" wire:model="form.additionalOutputs.{{ $id }}.publication_date"
                class="form-control" @disabled(!$canEdit) />
        </div>

    {{-- PRODUK / TTG --}}
    @elseif ($group === 'produk')
        <div class="col-md-12">
            <label class="form-label required">Nama Produk / TTG</label>
            <input type="text" wire:model="form.additionalOutputs.{{ $id }}.product_name"
                class="form-control" placeholder="Nama produk atau teknologi tepat guna" @disabled(!$canEdit) />
            @error("form.additionalOutputs.{$id}.product_name")
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>
        <div class="col-md-12">
            <label class="form-label">Deskripsi</label>
            <textarea wire:model="form.additionalOutputs.{{ $id }}.description"
                class="form-control" rows="3" placeholder="Deskripsi singkat produk/TTG" @disabled(!$canEdit)></textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Tingkat Kesiapterapan (TKT)</label>
            <input type="text" wire:model="form.additionalOutputs.{{ $id }}.readiness_level"
                class="form-control" placeholder="Contoh: TKT 4" @disabled(!$canEdit) />
        </div>
        <div class="col-md-6">
            <label class="form-label">Lokasi Implementasi</label>
            <input type="text" wire:model="form.additionalOutputs.{{ $id }}.implementation_location"
                class="form-control" placeholder="Lokasi penerapan TTG" @disabled(!$canEdit) />
        </div>
        <div class="col-md-12">
            <label class="form-label">File Dokumen TTG / Foto Produk</label>
            <input type="file" wire:model="tempAdditionalFiles.{{ $id }}"
                class="form-control" accept=".pdf,.jpg,.jpeg,.png" @disabled(!$canEdit) />
            @error("tempAdditionalFiles.{$id}") <small class="text-danger">{{ $message }}</small> @enderror
            <div wire:loading wire:target="tempAdditionalFiles.{{ $id }}">
                <small class="text-muted"><span class="spinner-border spinner-border-sm me-2"></span> Uploading...</small>
            </div>
            @if ($additionalOutput?->getFirstMedia('book_document'))
                @php $media = $additionalOutput->getFirstMedia('book_document'); @endphp
                <div class="bg-body-tertiary mt-2 rounded border p-2">
                    <div class="d-flex align-items-center">
                        <x-lucide-file-text class="text-primary icon me-2" />
                        <div class="flex-fill">
                            <small class="text-muted">File yang sudah diunggah:</small><br>
                            <strong>{{ $media->name }}</strong> <small class="text-muted">({{ number_format($media->size / 1024, 2) }} KB)</small>
                        </div>
                        <a data-navigate-ignore="true" href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                            target="_blank" class="btn btn-sm btn-primary"><x-lucide-download class="icon" /> Download</a>
                    </div>
                </div>
            @endif
        </div>

    {{-- HKI / HAK CIPTA --}}
    @elseif ($group === 'hki')
        <div class="col-md-6">
            <label class="form-label required">Jenis HKI</label>
            <input type="text" wire:model="form.additionalOutputs.{{ $id }}.hki_type"
                class="form-control" placeholder="Hak Cipta / Paten / Merek / dll" @disabled(!$canEdit) />
            @error("form.additionalOutputs.{$id}.hki_type") <small class="text-danger">{{ $message }}</small> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Nomor Pendaftaran</label>
            <input type="text" wire:model="form.additionalOutputs.{{ $id }}.registration_number"
                class="form-control" placeholder="Nomor sertifikat/pendaftaran" @disabled(!$canEdit) />
        </div>
        <div class="col-md-12">
            <label class="form-label">Nama Inventor / Pencipta</label>
            <input type="text" wire:model="form.additionalOutputs.{{ $id }}.inventors"
                class="form-control" placeholder="Nama inventor (pisahkan dengan koma)" @disabled(!$canEdit) />
        </div>
        <div class="col-md-12">
            <label class="form-label">Sertifikat HKI</label>
            <input type="file" wire:model="tempAdditionalFiles.{{ $id }}"
                class="form-control" accept=".pdf" @disabled(!$canEdit) />
            @error("tempAdditionalFiles.{$id}") <small class="text-danger">{{ $message }}</small> @enderror
            <div wire:loading wire:target="tempAdditionalFiles.{{ $id }}">
                <small class="text-muted"><span class="spinner-border spinner-border-sm me-2"></span> Uploading...</small>
            </div>
            @if ($additionalOutput?->getFirstMedia('book_document'))
                @php $media = $additionalOutput->getFirstMedia('book_document'); @endphp
                <div class="bg-body-tertiary mt-2 rounded border p-2">
                    <div class="d-flex align-items-center">
                        <x-lucide-file-text class="text-primary icon me-2" />
                        <div class="flex-fill">
                            <small class="text-muted">File yang sudah diunggah:</small><br>
                            <strong>{{ $media->name }}</strong> <small class="text-muted">({{ number_format($media->size / 1024, 2) }} KB)</small>
                        </div>
                        <a data-navigate-ignore="true" href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                            target="_blank" class="btn btn-sm btn-primary"><x-lucide-download class="icon" /> Download</a>
                    </div>
                </div>
            @endif
        </div>

    {{-- JURNAL --}}
    @elseif ($group === 'jurnal')
        <div class="col-md-12">
            <label class="form-label required">Judul Artikel</label>
            <input type="text" wire:model="form.additionalOutputs.{{ $id }}.journal_title"
                class="form-control" placeholder="Judul artikel jurnal" @disabled(!$canEdit) />
            @error("form.additionalOutputs.{$id}.journal_title") <small class="text-danger">{{ $message }}</small> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">ISSN</label>
            <input type="text" wire:model="form.additionalOutputs.{{ $id }}.issn"
                class="form-control" placeholder="xxxx-xxxx" @disabled(!$canEdit) />
        </div>
        <div class="col-md-6">
            <label class="form-label">E-ISSN</label>
            <input type="text" wire:model="form.additionalOutputs.{{ $id }}.eissn"
                class="form-control" placeholder="xxxx-xxxx" @disabled(!$canEdit) />
        </div>
        <div class="col-md-4">
            <label class="form-label">Volume</label>
            <input type="text" wire:model="form.additionalOutputs.{{ $id }}.volume"
                class="form-control" placeholder="Vol. 1" @disabled(!$canEdit) />
        </div>
        <div class="col-md-4">
            <label class="form-label">Nomor Terbitan</label>
            <input type="text" wire:model="form.additionalOutputs.{{ $id }}.issue_number"
                class="form-control" placeholder="No. 1" @disabled(!$canEdit) />
        </div>
        <div class="col-md-4">
            <label class="form-label">Tahun Terbit</label>
            <input type="number" wire:model="form.additionalOutputs.{{ $id }}.publication_year"
                class="form-control" min="2000" max="2030" @disabled(!$canEdit) />
        </div>
        <div class="col-md-12">
            <label class="form-label">DOI</label>
            <input type="text" wire:model="form.additionalOutputs.{{ $id }}.doi"
                class="form-control" placeholder="10.xxxx/xxxxx" @disabled(!$canEdit) />
        </div>
        <div class="col-md-6">
            <label class="form-label">Dokumen Artikel</label>
            <input type="file" wire:model="tempAdditionalFiles.{{ $id }}"
                class="form-control" accept=".pdf" @disabled(!$canEdit) />
            @error("tempAdditionalFiles.{$id}") <small class="text-danger">{{ $message }}</small> @enderror
            <div wire:loading wire:target="tempAdditionalFiles.{{ $id }}">
                <small class="text-muted"><span class="spinner-border spinner-border-sm me-2"></span> Uploading...</small>
            </div>
            @if ($additionalOutput?->getFirstMedia('book_document'))
                @php $media = $additionalOutput->getFirstMedia('book_document'); @endphp
                <div class="bg-body-tertiary mt-2 rounded border p-2">
                    <div class="d-flex align-items-center">
                        <x-lucide-file-text class="text-primary icon me-2" />
                        <div class="flex-fill"><small class="text-muted">File yang sudah diunggah:</small><br><strong>{{ $media->name }}</strong></div>
                        <a data-navigate-ignore="true" href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                            target="_blank" class="btn btn-sm btn-primary"><x-lucide-download class="icon" /> Download</a>
                    </div>
                </div>
            @endif
        </div>
        <div class="col-md-6">
            <label class="form-label">Surat Keterangan Terbit</label>
            <input type="file" wire:model="tempAdditionalCerts.{{ $id }}"
                class="form-control" accept=".pdf" @disabled(!$canEdit) />
            <div wire:loading wire:target="tempAdditionalCerts.{{ $id }}">
                <small class="text-muted"><span class="spinner-border spinner-border-sm me-2"></span> Uploading...</small>
            </div>
            @if ($additionalOutput?->getFirstMedia('publication_certificate'))
                @php $media = $additionalOutput->getFirstMedia('publication_certificate'); @endphp
                <div class="bg-body-tertiary mt-2 rounded border p-2">
                    <div class="d-flex align-items-center">
                        <x-lucide-file-text class="text-primary icon me-2" />
                        <div class="flex-fill"><small class="text-muted">File yang sudah diunggah:</small><br><strong>{{ $media->name }}</strong></div>
                        <a data-navigate-ignore="true" href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                            target="_blank" class="btn btn-sm btn-primary"><x-lucide-download class="icon" /> Download</a>
                    </div>
                </div>
            @endif
        </div>

    {{-- PROSIDING --}}
    @elseif ($group === 'prosiding')
        <div class="col-md-12">
            <label class="form-label required">Judul Makalah</label>
            <input type="text" wire:model="form.additionalOutputs.{{ $id }}.journal_title"
                class="form-control" placeholder="Judul makalah / paper" @disabled(!$canEdit) />
            @error("form.additionalOutputs.{$id}.journal_title") <small class="text-danger">{{ $message }}</small> @enderror
        </div>
        <div class="col-md-12">
            <label class="form-label">Nama Seminar / Konferensi</label>
            <input type="text" wire:model="form.additionalOutputs.{{ $id }}.publisher_name"
                class="form-control" placeholder="Nama seminar atau konferensi" @disabled(!$canEdit) />
        </div>
        <div class="col-md-6">
            <label class="form-label">Tahun Pelaksanaan</label>
            <input type="number" wire:model="form.additionalOutputs.{{ $id }}.publication_year"
                class="form-control" min="2000" max="2030" @disabled(!$canEdit) />
        </div>
        <div class="col-md-6">
            <label class="form-label">DOI / URL Prosiding</label>
            <input type="text" wire:model="form.additionalOutputs.{{ $id }}.doi"
                class="form-control" placeholder="DOI atau URL prosiding" @disabled(!$canEdit) />
        </div>
        <div class="col-md-6">
            <label class="form-label">Dokumen Makalah</label>
            <input type="file" wire:model="tempAdditionalFiles.{{ $id }}"
                class="form-control" accept=".pdf" @disabled(!$canEdit) />
            <div wire:loading wire:target="tempAdditionalFiles.{{ $id }}">
                <small class="text-muted"><span class="spinner-border spinner-border-sm me-2"></span> Uploading...</small>
            </div>
            @if ($additionalOutput?->getFirstMedia('book_document'))
                @php $media = $additionalOutput->getFirstMedia('book_document'); @endphp
                <div class="bg-body-tertiary mt-2 rounded border p-2">
                    <div class="d-flex align-items-center">
                        <x-lucide-file-text class="text-primary icon me-2" />
                        <div class="flex-fill"><small class="text-muted">File yang sudah diunggah:</small><br><strong>{{ $media->name }}</strong></div>
                        <a data-navigate-ignore="true" href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                            target="_blank" class="btn btn-sm btn-primary"><x-lucide-download class="icon" /> Download</a>
                    </div>
                </div>
            @endif
        </div>
        <div class="col-md-6">
            <label class="form-label">Surat Keterangan Terbit</label>
            <input type="file" wire:model="tempAdditionalCerts.{{ $id }}"
                class="form-control" accept=".pdf" @disabled(!$canEdit) />
            <div wire:loading wire:target="tempAdditionalCerts.{{ $id }}">
                <small class="text-muted"><span class="spinner-border spinner-border-sm me-2"></span> Uploading...</small>
            </div>
            @if ($additionalOutput?->getFirstMedia('publication_certificate'))
                @php $media = $additionalOutput->getFirstMedia('publication_certificate'); @endphp
                <div class="bg-body-tertiary mt-2 rounded border p-2">
                    <div class="d-flex align-items-center">
                        <x-lucide-file-text class="text-primary icon me-2" />
                        <div class="flex-fill"><small class="text-muted">File yang sudah diunggah:</small><br><strong>{{ $media->name }}</strong></div>
                        <a data-navigate-ignore="true" href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                            target="_blank" class="btn btn-sm btn-primary"><x-lucide-download class="icon" /> Download</a>
                    </div>
                </div>
            @endif
        </div>

    {{-- BUKU (default) --}}
    @else
        <div class="col-md-12">
            <label class="form-label required">Judul Buku</label>
            <input type="text" wire:model="form.additionalOutputs.{{ $id }}.book_title"
                class="form-control" placeholder="Masukkan judul buku" @disabled(!$canEdit) />
            @error("form.additionalOutputs.{$id}.book_title") <small class="text-danger">{{ $message }}</small> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label required">Nama Penerbit</label>
            <input type="text" wire:model="form.additionalOutputs.{{ $id }}.publisher_name"
                class="form-control" placeholder="Masukkan nama penerbit" @disabled(!$canEdit) />
            @error("form.additionalOutputs.{$id}.publisher_name") <small class="text-danger">{{ $message }}</small> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">ISBN</label>
            <input type="text" wire:model="form.additionalOutputs.{{ $id }}.isbn"
                class="form-control" placeholder="978-xxx-xxx-xxx-x" @disabled(!$canEdit) />
        </div>
        <div class="col-md-6">
            <label class="form-label">Tahun Terbit</label>
            <input type="number" wire:model="form.additionalOutputs.{{ $id }}.publication_year"
                class="form-control" min="2000" max="2030" @disabled(!$canEdit) />
        </div>
        <div class="col-md-6">
            <label class="form-label">Jumlah Halaman</label>
            <input type="number" wire:model="form.additionalOutputs.{{ $id }}.total_pages"
                class="form-control" placeholder="100" @disabled(!$canEdit) />
        </div>
        <div class="col-md-6">
            <label class="form-label">URL Web Penerbit</label>
            <input type="url" wire:model="form.additionalOutputs.{{ $id }}.publisher_url"
                class="form-control" placeholder="https://" @disabled(!$canEdit) />
        </div>
        <div class="col-md-6">
            <label class="form-label">URL Buku</label>
            <input type="url" wire:model="form.additionalOutputs.{{ $id }}.book_url"
                class="form-control" placeholder="https://" @disabled(!$canEdit) />
        </div>
        <div class="col-md-6">
            <label class="form-label">Dokumen Buku / Draft</label>
            <input type="file" wire:model="tempAdditionalFiles.{{ $id }}"
                class="form-control" accept=".pdf" @disabled(!$canEdit) />
            <div wire:loading wire:target="tempAdditionalFiles.{{ $id }}">
                <small class="text-muted"><span class="spinner-border spinner-border-sm me-2"></span> Uploading...</small>
            </div>
            @if ($additionalOutput?->getFirstMedia('book_document'))
                @php $media = $additionalOutput->getFirstMedia('book_document'); @endphp
                <div class="bg-body-tertiary mt-2 rounded border p-2">
                    <div class="d-flex align-items-center">
                        <x-lucide-file-text class="text-primary icon me-2" />
                        <div class="flex-fill"><small class="text-muted">File yang sudah diunggah:</small><br><strong>{{ $media->name }}</strong></div>
                        <a data-navigate-ignore="true" href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                            target="_blank" class="btn btn-sm btn-primary"><x-lucide-download class="icon" /> Download</a>
                    </div>
                </div>
            @endif
        </div>
        <div class="col-md-6">
            <label class="form-label">Surat Keterangan Terbit</label>
            <input type="file" wire:model="tempAdditionalCerts.{{ $id }}"
                class="form-control" accept=".pdf" @disabled(!$canEdit) />
            <div wire:loading wire:target="tempAdditionalCerts.{{ $id }}">
                <small class="text-muted"><span class="spinner-border spinner-border-sm me-2"></span> Uploading...</small>
            </div>
            @if ($additionalOutput?->getFirstMedia('publication_certificate'))
                @php $media = $additionalOutput->getFirstMedia('publication_certificate'); @endphp
                <div class="bg-body-tertiary mt-2 rounded border p-2">
                    <div class="d-flex align-items-center">
                        <x-lucide-file-text class="text-primary icon me-2" />
                        <div class="flex-fill"><small class="text-muted">File yang sudah diunggah:</small><br><strong>{{ $media->name }}</strong></div>
                        <a data-navigate-ignore="true" href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                            target="_blank" class="btn btn-sm btn-primary"><x-lucide-download class="icon" /> Download</a>
                    </div>
                </div>
            @endif
        </div>
    @endif

</div>
