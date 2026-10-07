<div>
    <x-slot:title>Monitoring & Evaluasi (Monev)</x-slot:title>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-sm bg-primary-lt">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-primary text-white avatar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"></path>
                                    <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"></path>
                                </svg>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Total Proposal Monev</div>
                            <div class="text-muted">{{ $this->proposals->total() }} Judul</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Monitoring Laporan akhir penelitian dan pengabdian</h3>
            <div class="card-actions">
                <div class="d-flex gap-2 align-items-center">
                    @if($this->pendingRektorCount > 0)
                        <div class="badge bg-warning-lt py-2 px-3 me-2 border border-warning shadow-sm d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg>
                            <span>{{ $this->pendingRektorCount }} Laporan belum dikirim ke Rektor</span>
                            <button class="btn btn-xs btn-warning border-0 ms-3 fw-bold" 
                                wire:click="sendReminderToKepala" 
                                wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="sendReminderToKepala">Kirim Pengingat</span>
                                <span wire:loading wire:target="sendReminderToKepala">Mengirim...</span>
                            </button>
                        </div>
                    @endif
                    <select class="form-select form-select-sm w-auto" wire:model.live="academicYear">
                        @foreach($this->academicYears as $year)
                            <option value="{{ $year }}">{{ $year }} / {{ $year + 1 }}</option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-sm w-auto" wire:model.live="semester">
                        <option value="all">Semua Semester</option>
                        <option value="ganjil">Ganjil</option>
                        <option value="genap">Genap</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body border-bottom py-3">
            <div class="d-flex align-items-center">
                <div class="text-muted">
                    Tampilkan
                    <div class="mx-2 d-inline-block">
                        <select class="form-select form-select-sm w-auto">
                            <option value="10">10</option>
                            <option value="25">25</option>
                        </select>
                    </div>
                    data
                </div>
                <div class="ms-auto text-muted">
                    Cari:
                    <div class="ms-2 d-inline-block">
                        <input type="text" class="form-control form-control-sm" wire:model.live.debounce.300ms="search"
                            placeholder="Judul, Nama Pengusul...">
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap datatable">
                <thead>
                    <tr>
                        <th>Pengusul & Judul</th>
                        <th>Jenis</th>
                        <th>Progres</th>
                        <th>Monev Terakhir</th>
                        <th>Laporan Akhir</th>
                        <th>Skor Evaluasi</th>
                        <th class="w-1">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->proposals as $proposal)
                        <tr>
                            <td>
                                <div class="font-weight-bold">{{ $proposal->submitter->name }}</div>
                                <div class="text-muted small text-truncate" style="max-width: 400px;"
                                    title="{{ $proposal->title }}">
                                    {{ $proposal->title }}
                                </div>
                            </td>
                            <td>
                                @php
                                    $typeLabel = $proposal->detailable instanceof \App\Models\Research ? 'Penelitian' : 'PKM';
                                    $typeColor = $proposal->detailable instanceof \App\Models\Research ? 'blue' : 'green';
                                @endphp
                                <span class="badge bg-{{ $typeColor }}-lt">{{ $typeLabel }}</span>
                            </td>
                            <td>
                                @php
                                    $latestMonev = $proposal->monevs->first();
                                    $progress = $latestMonev ? $latestMonev->progress_percentage : 0;
                                @endphp
                                <div class="d-flex align-items-center mb-1">
                                    <div class="text-muted small me-2">{{ $progress }}%</div>
                                    <div class="progress progress-xs flex-grow-1">
                                        <div class="progress-bar bg-primary" @style(['width' => $progress . '%'])></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($latestMonev)
                                    <div class="small">{{ $latestMonev->monev_date->format('d/m/Y') }}</div>
                                    <div class="text-muted extra-small">Oleh: {{ $latestMonev->reviewer?->name ?? 'Reviewer' }}</div>
                                @else
                                    <span class="text-muted small">- Belum ada -</span>
                                @endif
                            </td>
                            <td>
                                @php $finalReport = $proposal->progressReports->first(); @endphp
                                @if($finalReport)
                                    <span class="badge bg-purple-lt" title="Laporan Akhir Diajukan">Diajukan</span>
                                    <div class="text-muted extra-small">{{ $finalReport->submitted_at?->format('d/m/Y') }}</div>
                                @else
                                    <span class="text-muted small">- Belum -</span>
                                @endif
                            </td>
                            <td>
                                    @php $review = $proposal->monevReviews->first(); @endphp
                                @if($review && $review->reviewed_at)
                                    <div class="d-flex align-items-center">
                                        <div class="font-weight-bold text-blue me-2">{{ $review->score }}</div>
                                        @if($review->finalized_by_lppm_at)
                                            @if($review->reported_to_rektor_at)
                                                <span class="badge bg-purple-lt p-1" title="Laporan sudah terkirim ke Rektor">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                                                </span>
                                            @elseif($review->approved_by_kepala_at)
                                                <span class="badge bg-success-lt p-1" title="Disahkan Kepala, Belum Lapor Rektor">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                                                </span>
                                            @else
                                                <span class="badge bg-azure-lt p-1" title="Terverifikasi Admin">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                    <div class="text-muted extra-small text-capitalize">
                                        @if($review->reported_to_rektor_at)
                                            Dilaporkan Rektor
                                        @elseif($review->approved_by_kepala_at)
                                            Disahkan Kepala
                                        @else
                                            {{ str_replace('_', ' ', $review->status) }}
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted small">- Belum direview -</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    @if($review && $review->reviewed_at && !$review->finalized_by_lppm_at)
                                        <button class="btn btn-sm btn-primary font-weight-bold" 
                                            wire:confirm="Verifikasi hasil Monev ini? QR Code TTD Anda akan digenerate." 
                                            wire:click="finalizeReview('{{ $review->id }}')">
                                            Verifikasi
                                        </button>
                                    @endif
                                    <button class="btn btn-sm btn-outline-primary"
                                        wire:click="selectProposal('{{ $proposal->id }}')">
                                        Detail & Monev
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Tidak ada proposal dalam tahap
                                pelaksanaan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex align-items-center">
            {{ $this->proposals->links() }}
        </div>
    </div>

    <!-- Modal List Monev -->
    <div class="modal modal-blur fade @if($showListModal) show @endif"
        style="@if($showListModal) display: block; @endif" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                @if ($selectedProposal)
                    <div class="modal-header">
                        <h5 class="modal-title">Daftar Monev: {{ $selectedProposal->submitter->name }}</h5>
                        <button type="button" class="btn-close" wire:click="$set('showListModal', false)"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                @if($selectedProposal->monevReviews->isEmpty())
                                    <div class="mb-3">
                                        <label class="form-label font-weight-bold">Tugaskan Reviewer Monev</label>
                                        <div class="input-group">
                                            <select class="form-select" wire:model="reviewer_id">
                                                <option value="">Pilih Reviewer...</option>
                                                @foreach($this->reviewers as $reviewer)
                                                    <option value="{{ $reviewer->id }}">{{ $reviewer->name }}
                                                        {{ $reviewer->identity?->is_external ? '(Eksternal)' : '(Internal)' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-primary" wire:click="assignReviewer"
                                                wire:loading.attr="disabled">
                                                Simpan
                                            </button>
                                        </div>
                                        @error('reviewer_id') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                @endif
                                
                                {{-- Section Hasil Reviewer --}}
                                @if($selectedProposal->monevReviews->isNotEmpty())
                                    @php $activeMonevReview = $selectedProposal->monevReviews->first(); @endphp
                                    <div class="card card-sm mb-3">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="font-weight-bold">Hasil Evaluasi Reviewer</div>
                                                <div class="ms-auto">
                                                    @if($activeMonevReview->reviewed_at)
                                                        @if($activeMonevReview->finalized_by_lppm_at)
                                                            <div class="btn-list">
                                                                <button class="btn btn-sm btn-danger font-weight-bold" wire:confirm="Batalkan verifikasi? BA akan ditarik kembali." wire:click="unfinalizeReview('{{ $activeMonevReview->id }}')">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 14l-4 -4l4 -4" /><path d="M5 10h11a4 4 0 1 1 0 8h-1" /></svg>
                                                                    Batalkan Verifikasi
                                                                </button>
                                                                <span class="badge bg-success-lt font-weight-bold p-2">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                                                                    Sudah Diverifikasi
                                                                </span>
                                                                <a href="{{ route('export.monev.ba', $activeMonevReview->id) }}" class="btn btn-sm btn-outline-info" target="_blank">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>
                                                                    Unduh BA
                                                                </a>
                                                            </div>
                                                        @else
                                                            <div class="btn-list">
                                                                <button class="btn btn-sm btn-danger font-weight-bold" wire:confirm="Kembalikan hasil ini ke Reviewer? Status 'Selesai Review' akan dibatalkan." wire:click="unfinalizeReview('{{ $activeMonevReview->id }}')">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 14l-4 -4l4 -4" /><path d="M5 10h11a4 4 0 1 1 0 8h-1" /></svg>
                                                                    Kembalikan ke Reviewer
                                                                </button>
                                                                <button class="btn btn-sm btn-primary font-weight-bold" wire:confirm="Anda yakin memverifikasi hasil Monev ini? QR Code Tanda Tangan Anda akan digenerate otomatis ke dalam BA." wire:click="finalizeReview('{{ $activeMonevReview->id }}')">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                                                                    Verifikasi (TTD)
                                                                </button>
                                                            </div>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-4">
                                                    <div class="text-muted small">Reviewer</div>
                                                    <div class="font-weight-medium">{{ $activeMonevReview->reviewer->name }}</div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="text-muted small">Skor</div>
                                                    <div class="font-weight-bold {{ ($activeMonevReview->score ?? 0) >= 80 ? 'text-success' : 'text-blue' }}">
                                                        {{ $activeMonevReview->score ?? '-' }}
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="text-muted small">Status</div>
                                                    <span class="badge bg-blue-lt text-uppercase">{{ str_replace('_', ' ', $activeMonevReview->status ?? 'pending') }}</span>
                                                </div>
                                            </div>
                                            @if($activeMonevReview->notes)
                                                <div class="mt-2 text-muted small border-top pt-1">
                                                    <strong>Catatan:</strong> {{ Str::limit($activeMonevReview->notes, 80) }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                
                                {{-- Section Hasil Laporan --}}
                                @if($selectedProposal->progressReports->isNotEmpty())
                                    <div class="card card-sm mb-3 border-purple-lt shadow-sm">
                                        <div class="card-status-top bg-purple"></div>
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="font-weight-bold text-purple">Hasil Laporan Akhir</div>
                                                <div class="ms-auto">
                                                    @php $finalDoc = $selectedProposal->progressReports->first(); @endphp
                                                    @if($finalDoc->hasMedia('substance_file'))
                                                        <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(5), ['media' => $finalDoc->getFirstMedia('substance_file')]) }}" 
                                                           class="btn btn-xs btn-purple" target="_blank">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>
                                                            Unduh Laporan
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="text-muted small mb-2">
                                                <strong>Ringkasan:</strong> {{ Str::limit($finalDoc->summary_update, 150) }}
                                            </div>
                                            <div class="row g-2">
                                                @if($finalDoc->hasMedia('rps_file') || $finalDoc->hasMedia('presentation_file'))
                                                    @php
                                                        $rpsMedia = $finalDoc->getFirstMedia('rps_file') ?? $finalDoc->getFirstMedia('presentation_file');
                                                    @endphp
                                                    <div class="col-auto">
                                                        <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(5), ['media' => $rpsMedia]) }}" 
                                                           class="badge bg-indigo-lt p-1" target="_blank">📄 RPS</a>
                                                    </div>
                                                @endif
                                                @if($finalDoc->hasMedia('realization_file'))
                                                    <div class="col-auto">
                                                        <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(5), ['media' => $finalDoc->getFirstMedia('realization_file')]) }}" 
                                                           class="badge bg-azure-lt p-1" target="_blank">📋 Realisasi</a>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="card-title mb-0">Riwayat Pelaksanaan</h3>
                            <button class="btn btn-primary btn-sm" wire:click="addMonev" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="addMonev">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                    Catat Progres
                                </span>
                                <span wire:loading wire:target="addMonev">Memuat...</span>
                            </button>
                        </div>

                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-vcenter">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Progres</th>
                                        <th>Reviewer</th>
                                        <th>Arsip</th>
                                        <th class="w-1"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($selectedProposal->monevs as $monev)
                                        <tr>
                                            <td>{{ $monev->monev_date->format('d/m/Y') }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress progress-xs w-100 me-2">
                                                        <div class="progress-bar bg-primary"
                                                            @style(['width' => $monev->progress_percentage . '%'])></div>
                                                    </div>
                                                    <div class="small">{{ $monev->progress_percentage }}%</div>
                                                </div>
                                            </td>
                                            <td>{{ $monev->reviewer?->name ?? '-' }}</td>
                                            <td>
                                                <div class="btn-group">
                                                    @if($monev->hasMedia('berita_acara'))
                                                        @php $media = $monev->getFirstMedia('berita_acara'); @endphp
                                                        <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                                            class="btn btn-xs btn-outline-info" title="Berita Acara"
                                                            target="_blank">BA</a>
                                                    @endif
                                                    @if($monev->hasMedia('borang'))
                                                        @php $media = $monev->getFirstMedia('borang'); @endphp
                                                        <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                                            class="btn btn-xs btn-outline-success" title="Borang"
                                                            target="_blank">BR</a>
                                                    @endif
                                                    @if($monev->hasMedia('rekap_penilaian'))
                                                        @php $media = $monev->getFirstMedia('rekap_penilaian'); @endphp
                                                        <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                                            class="btn btn-xs btn-outline-warning" title="Rekap"
                                                            target="_blank">RK</a>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-list flex-nowrap">
                                                    <button class="btn btn-icon btn-sm btn-outline-primary"
                                                        wire:click="editMonev('{{ $monev->id }}')">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                                            height="24" viewBox="0 0 24 24" stroke-width="2"
                                                            stroke="currentColor" fill="none" stroke-linecap="round"
                                                            stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                            <path
                                                                d="M9 7h-3a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-3">
                                                            </path>
                                                            <path d="M9 15h3l8.5 -8.5a1.5 1.5 0 0 0 -3 -3l-8.5 8.5v3">
                                                            </path>
                                                            <line x1="16" y1="5" x2="19" y2="8"></line>
                                                        </svg>
                                                    </button>
                                                    <button class="btn btn-icon btn-sm btn-outline-danger"
                                                        wire:confirm="Hapus data monev ini?"
                                                        wire:click="deleteMonev('{{ $monev->id }}')">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                                            height="24" viewBox="0 0 24 24" stroke-width="2"
                                                            stroke="currentColor" fill="none" stroke-linecap="round"
                                                            stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                            <line x1="4" y1="7" x2="20" y2="7"></line>
                                                            <line x1="10" y1="11" x2="10" y2="17"></line>
                                                            <line x1="14" y1="11" x2="14" y2="17"></line>
                                                            <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                                                            <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Belum ada riwayat
                                                monev.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            wire:click="$set('showListModal', false)">Tutup</button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal Form Monev -->
    <div class="modal modal-blur fade @if($showFormModal) show @endif"
        style="@if($showFormModal) display: block; @endif" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="z-index: 1060;">
            <div class="modal-content border-primary shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ $selectedMonev ? 'Edit' : 'Tambah' }} Data Monev</h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="$set('showFormModal', false)"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Tanggal Pelaksanaan</label>
                                <input type="date" class="form-control" wire:model="monev_date"
                                    wire:loading.attr="disabled"
                                    wire:target="saveMonev, berita_acara, borang, rekap_penilaian">
                                @error('monev_date') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Capaian (%)</label>
                                <input type="number" class="form-control" wire:model="progress_percentage" min="0"
                                    max="100" wire:loading.attr="disabled"
                                    wire:target="saveMonev, berita_acara, borang, rekap_penilaian">
                                @error('progress_percentage') <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan (Opsional)</label>
                        <textarea class="form-control" wire:model="notes" rows="2" placeholder="Catatan kunjungan lapangan atau progres spesifik..."></textarea>
                    </div>
                    
                    <div class="alert alert-info py-2 shadow-sm mb-3">
                        <div class="d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9h.01" /><path d="M11 12h1v4h1" /><path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z" /></svg>
                            <div class="small">
                                <strong>Catatan:</strong> Dokumen formal (BA ber-TTD) akan digenerate **otomatis** oleh sistem setelah verifikasi. Unggahan di bawah bersifat **opsional** jika Anda memiliki dokumen fisik tambahan.
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">
                    <!-- Berita Acara -->
                    <div class="mb-3" x-data="{ uploading: false, progress: 0 }"
                        x-on:livewire-upload-start="uploading = true" x-on:livewire-upload-finish="uploading = false"
                        x-on:livewire-upload-error="uploading = false"
                        x-on:livewire-upload-progress="progress = $event.detail.progress">
                        <label class="form-label">Lampiran Berita Acara (Opsional)</label>
                        <input type="file" class="form-control" wire:model="berita_acara" wire:loading.attr="disabled"
                            wire:target="saveMonev, berita_acara, borang, rekap_penilaian">
                        <div class="progress progress-xs mt-2" x-show="uploading" style="display: none;">
                            <div class="progress-bar bg-primary" :style="`width: ${progress}%`"></div>
                        </div>
                        @if($selectedMonev && $selectedMonev->hasMedia('berita_acara'))
                            @php $media = $selectedMonev->getFirstMedia('berita_acara'); @endphp
                            <div class="mt-1 small text-success">Sudah diunggah: <a
                                    href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                    target="_blank">Lihat</a></div>
                        @endif
                        @error('berita_acara') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <!-- Borang -->
                    <div class="mb-3" x-data="{ uploading: false, progress: 0 }"
                        x-on:livewire-upload-start="uploading = true" x-on:livewire-upload-finish="uploading = false"
                        x-on:livewire-upload-error="uploading = false"
                        x-on:livewire-upload-progress="progress = $event.detail.progress">
                        <label class="form-label">Lampiran Borang Monev (Opsional)</label>
                        <input type="file" class="form-control" wire:model="borang" wire:loading.attr="disabled"
                            wire:target="saveMonev, berita_acara, borang, rekap_penilaian">
                        <div class="progress progress-xs mt-2" x-show="uploading" style="display: none;">
                            <div class="progress-bar bg-primary" :style="`width: ${progress}%`"></div>
                        </div>
                        @if($selectedMonev && $selectedMonev->hasMedia('borang'))
                            @php $media = $selectedMonev->getFirstMedia('borang'); @endphp
                            <div class="mt-1 small text-success">Sudah diunggah: <a
                                    href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                    target="_blank">Lihat</a></div>
                        @endif
                        @error('borang') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <!-- Rekap -->
                    <div class="mb-3" x-data="{ uploading: false, progress: 0 }"
                        x-on:livewire-upload-start="uploading = true" x-on:livewire-upload-finish="uploading = false"
                        x-on:livewire-upload-error="uploading = false"
                        x-on:livewire-upload-progress="progress = $event.detail.progress">
                        <label class="form-label">Lampiran Rekap Penilaian (Opsional)</label>
                        <input type="file" class="form-control" wire:model="rekap_penilaian"
                            wire:loading.attr="disabled" wire:target="saveMonev, berita_acara, borang, rekap_penilaian">
                        <div class="progress progress-xs mt-2" x-show="uploading" style="display: none;">
                            <div class="progress-bar bg-primary" :style="`width: ${progress}%`"></div>
                        </div>
                        @if($selectedMonev && $selectedMonev->hasMedia('rekap_penilaian'))
                            @php $media = $selectedMonev->getFirstMedia('rekap_penilaian'); @endphp
                            <div class="mt-1 small text-success">Sudah diunggah: <a
                                    href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                    target="_blank">Lihat</a></div>
                        @endif
                        @error('rekap_penilaian') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary"
                        wire:click="$set('showFormModal', false)">Batal</button>
                    <button type="button" class="btn btn-primary ms-auto" wire:click="saveMonev"
                        wire:loading.attr="disabled" wire:target="saveMonev, berita_acara, borang, rekap_penilaian">
                        <span wire:loading.remove wire:target="saveMonev">Simpan Monev</span>
                        <span wire:loading wire:target="saveMonev">Menyimpan...</span>
                        <span wire:loading wire:target="berita_acara, borang, rekap_penilaian">Mengunggah file...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if($showListModal)
        <div class="modal-backdrop fade show"></div>
    @endif
    @if($showFormModal)
        <div class="modal-backdrop fade show" style="z-index: 1055;"></div>
    @endif
</div>
