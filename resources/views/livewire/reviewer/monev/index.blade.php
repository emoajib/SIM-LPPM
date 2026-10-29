<div>
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Portal Reviewer Monev
                    </h2>
                    <div class="text-muted mt-1 text-uppercase">Tahun Akademik: {{ date('Y') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card mb-3">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 0 1 0 14 0a7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg>
                                </span>
                                <input type="text" wire:model.live="search" class="form-control" placeholder="Cari judul atau pengusul...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-sm">
                        <thead>
                            <tr>
                                <th>Pengusul & Judul</th>
                                <th>Jenis</th>
                                <th>Tahun/Sem</th>
                                <th>Status</th>
                                <th>Skor</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->assignments as $review)
                                <tr>
                                    <td>
                                        <div class="font-weight-bold">{{ $review->proposal?->submitter?->name ?? 'N/A' }}</div>
                                        <div class="text-muted text-truncate" style="max-width: 400px; font-size: 0.75rem;">
                                            {{ $review->proposal?->title ?? 'Tidak ada judul' }}</div>
                                    </td>
                                    <td>
                                        @if ($review->proposal->detailable_type === \App\Models\Research::class)
                                            <span class="badge bg-blue-lt">Penelitian</span>
                                        @else
                                            <span class="badge bg-green-lt">Pengabdian</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $review->academic_year }} / {{ ucfirst($review->semester) }}
                                    </td>
                                    <td>
                                        @if($review->reviewed_at)
                                            <span class="badge bg-blue-lt">{{ str_replace('_', ' ', strtoupper($review->status)) }}</span>
                                        @else
                                            <span class="badge bg-warning-lt">PENDING</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="fw-bold">{{ $review->score ?? '-' }}</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-white" wire:click="selectReview('{{ $review->id }}')">
                                            Evaluasi
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Belum ada penugasan monev.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer border-0">
                    {{ $this->assignments->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Evaluasi -->
    <div class="modal modal-blur fade @if($showReviewModal) show @endif"
        style="@if($showReviewModal) display: block; @endif" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: 95%;">
            <div class="modal-content shadow-lg border-0 rounded-5 overflow-hidden">
                @if ($selectedReview)
                    <!-- Header Modal Compact -->
                    <div class="p-3 border-bottom bg-slate-50 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-blue-600 p-2 rounded-3 shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-clipboard-check text-white" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2"></path>
                                    <path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z"></path>
                                    <path d="M9 14l2 2l4 -4"></path>
                                </svg>
                            </div>
                            <div>
                                <h2 class="fw-bolder text-slate-900 mb-0 fs-2 letter-spacing-tight">Audit Konsol Monev</h2>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-slate-200 text-slate-600 border-0 fw-bold px-2" style="font-size: 0.75rem;">Sesi Penilaian Aktif</span>
                                    <span class="text-slate-500 text-truncate" style="font-size: 0.75rem; max-width: 500px;">{{ $selectedReview->proposal?->title }}</span>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn-close" wire:click="$set('showReviewModal', false)"></button>
                    </div>

                    <div class="modal-body p-0">
                        <div class="row g-0" style="height: calc(100vh - 140px); overflow: hidden;">
                            <style>
                                .stylish-scrollbar::-webkit-scrollbar { width: 8px; }
                                .stylish-scrollbar::-webkit-scrollbar-track { background: #f8fafc; border-radius: 10px; }
                                .stylish-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
                                .stylish-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
                                
                                .glass-card {
                                    background: rgba(255, 255, 255, 0.9);
                                    backdrop-filter: blur(8px);
                                    border: 1px solid #f1f5f9;
                                    border-radius: 0.75rem;
                                    transition: all 0.2s;
                                }
                                .hover-bg-slate-50:hover { background-color: #f8fafc !important; }
                            </style>

                            <!-- LEFT SIDE: Data Panel -->
                            <div class="col-md-6 border-end d-flex flex-column bg-slate-50/30 h-100">
                                <div class="px-3 pt-3 border-bottom bg-white flex-shrink-0">
                                    <ul class="nav nav-tabs border-0 gap-1" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active border-0 fw-bold px-3 py-2 rounded-top-3 fs-5" data-bs-toggle="tab" href="#tab-realisasi">Laporan Realisasi</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link border-0 text-slate-500 fw-bold px-3 py-2 rounded-top-3 fs-5" data-bs-toggle="tab" href="#tab-rencana">Rencana</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link border-0 text-slate-500 fw-bold px-3 py-2 rounded-top-3 fs-5" data-bs-toggle="tab" href="#tab-logbook">Logbook</a>
                                        </li>
                                    </ul>
                                </div>

                                <div class="tab-content overflow-auto flex-grow-1 p-3 stylish-scrollbar">
                                    <!-- Tab 1: Realisasi (Contextual) -->
                                    <div class="tab-pane fade show active" id="tab-realisasi">
                                        @php 
                                            $isResearch = $selectedReview->proposal->detailable_type === \App\Models\Research::class;
                                            $allOutputs = collect($this->activeReport?->mandatoryOutputs ?? [])->merge($this->activeReport?->additionalOutputs ?? []);
                                        @endphp

                                        @if($this->activeReport)
                                            <!-- Group 1: Narasi & Berkas Utama -->
                                            <div class="mb-3">
                                                <label class="text-slate-400 fw-bold fs-6 mb-2 d-flex justify-content-between align-items-center uppercase tracking-wider">
                                                    <span>{{ $isResearch ? 'RINGKASAN SUBSTANSI & CAPAIAN' : 'DESKRIPSI PELAKSANAAN & DAMPAK' }}</span>
                                                    @if($isResearch)
                                                        <span class="badge bg-purple-lt fs-6">Kriteria 4</span>
                                                    @else
                                                        <span class="badge bg-purple-lt fs-6">Kriteria 6</span>
                                                    @endif
                                                </label>
                                                <div class="glass-card p-3 border border-purple-100 bg-purple-50/20 mb-2">
                                                    <p class="text-slate-700 italic mb-0 fs-5" style="text-align: justify; line-height: 1.5;">
                                                        "{{ $this->activeReport->summary_update ?? 'Pangusul belum menyertakan narasi ringkasan.' }}"
                                                    </p>
                                                </div>
                                                @if($this->activeReport->hasMedia('substance_file'))
                                                    @php $media = $this->activeReport->getFirstMedia('substance_file'); @endphp
                                                    <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}" target="_blank" 
                                                       class="btn btn-outline-primary btn-sm w-100 py-1.5 fw-bold fs-6 gap-2">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /></svg>
                                                        Dokumen Laporan Akhir (.pdf)
                                                    </a>
                                                @endif
                                            </div>

                                            @if(!$isResearch)
                                                <!-- PkM Specific: Mitra & MBKM -->
                                                <div class="row g-2 mb-3">
                                                    <div class="col-12">
                                                        <label class="text-slate-400 fw-bold fs-6 mb-1 d-flex justify-content-between align-items-center">
                                                            <span>DATA MITRA & PERMASALAHAN</span>
                                                            <span class="badge bg-emerald-lt fs-6">Kriteria 2</span>
                                                        </label>
                                                        <div class="glass-card p-3 bg-emerald-50/20 border-emerald-100">
                                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                                <div class="bg-emerald-600 p-1 rounded-circle"><svg xmlns="http://www.w3.org/2000/svg" class="text-white" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /></svg></div>
                                                                <div class="fw-bold text-slate-800 fs-5">{{ $selectedReview->proposal->detailable->partner?->name ?? 'Mitra tidak terdefinisi' }}</div>
                                                            </div>
                                                            <div class="text-slate-600 fs-6 italic mb-1">"{{ $selectedReview->proposal->detailable->partner_issue_summary ?? 'Masalah mitra tidak dicatat.' }}"</div>
                                                            <div class="text-emerald-700 fs-6 fw-bold">Solusi: {{ $selectedReview->proposal->detailable->solution_offered ?? '-' }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="text-slate-400 fw-bold fs-6 mb-1 d-flex justify-content-between align-items-center">
                                                            <span>REKOGNISI MBKM (PELIBATAN MAHASISWA)</span>
                                                            <span class="badge bg-indigo-lt fs-6">Kriteria 5</span>
                                                        </label>
                                                        <div class="glass-card p-3 bg-indigo-50/20 border-indigo-100">
                                                            <div class="d-flex align-items-center gap-3">
                                                                <div class="bg-indigo-600 p-2 rounded-3">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="text-white" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M22 9l-10 -4l-10 4l10 4l10 -4v6" /><path d="M6 10.6v5.4a6 3 0 0 0 12 0v-5.4" /></svg>
                                                                </div>
                                                                <div>
                                                                    <div class="fw-bold text-slate-800 fs-5">{{ count($selectedReview->proposal->student_members ?? []) }} Mahasiswa Terlibat</div>
                                                                    <div class="text-slate-500 fs-6">Program mendukung konversi SKS / rekognisi MBKM.</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            <!-- Group 2: Luaran & Publikasi -->
                                            <div class="mb-4">
                                                <label class="text-slate-400 fw-bold fs-6 mb-2 d-flex justify-content-between align-items-center uppercase tracking-wider">
                                                    <span>{{ $isResearch ? 'EVALUASI LUARAN (WAJIB & TAMBAHAN)' : 'PUBLIKASI, PRODUK & VIDEO' }}</span>
                                                    @if($isResearch)
                                                        <span class="badge bg-orange-lt fs-6">Kriteria 1 & 2</span>
                                                    @else
                                                        <span class="badge bg-orange-lt fs-6">Kriteria 1, 3, 4</span>
                                                    @endif
                                                </label>
                                                <div class="row g-2">
                                                    @forelse($allOutputs as $output)
                                                        <div class="col-12">
                                                            <div class="glass-card p-2 px-3 border border-slate-200 bg-white hover-bg-slate-50 transition-all">
                                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                                    <div class="d-flex align-items-center gap-2">
                                                                        <span class="badge {{ $output instanceof \App\Models\MandatoryOutput ? 'bg-orange-lt' : 'bg-blue-lt' }} border-0 fs-6">
                                                                            {{ $output instanceof \App\Models\MandatoryOutput ? 'Wajib' : 'Tambahan' }}
                                                                        </span>
                                                                        @if(!$isResearch && $output->video_url)
                                                                            <span class="badge bg-red-lt fs-6">Video</span>
                                                                        @endif
                                                                    </div>
                                                                    <span class="text-slate-400 fs-6 fw-bold">{{ strtoupper($output->status_type ?? ($output->status ?? 'Draft')) }}</span>
                                                                </div>
                                                                <div class="fw-bold text-slate-800 fs-5 text-truncate mb-2">{{ $output->article_title ?: ($output->product_name ?: $output->book_title) }}</div>
                                                                
                                                                <div class="d-flex gap-2">
                                                                    @php $url = $output->article_url ?: ($output->video_url ?: $output->media_url); @endphp
                                                                    @if($url)
                                                                        @if(str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be'))
                                                                            <a href="{{ $url }}" target="_blank" class="btn btn-danger btn-sm flex-grow-1 py-1 fw-bold fs-6">
                                                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M3 5m0 4a4 4 0 0 1 4 -4h10a4 4 0 0 1 4 4v6a4 4 0 0 1 -4 4h-10a4 4 0 0 1 -4 -4z" /><path d="M10 9l5 3l-5 3z" /></svg>
                                                                                Tonton Video
                                                                            </a>
                                                                        @else
                                                                            <a href="{{ $url }}" target="_blank" class="btn btn-ghost-primary btn-sm flex-grow-1 py-1 fs-6 fw-bold">Verifikasi URL Luaran</a>
                                                                        @endif
                                                                    @endif

                                                                    @if($output->hasMedia('journal_article') || $output->hasMedia('book_document') || $output->hasMedia('publication_certificate'))
                                                                        @php
                                                                            $media = $output->getFirstMedia('journal_article')
                                                                                ?? $output->getFirstMedia('book_document')
                                                                                ?? $output->getFirstMedia('publication_certificate');
                                                                        @endphp
                                                                        <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                                                            target="_blank" class="btn btn-ghost-emerald btn-sm {{ $url ? '' : 'flex-grow-1' }} py-1 fs-6 fw-bold">Unduh Berkas</a>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <div class="col-12 text-center text-slate-400 fs-6 py-3 border border-dashed rounded-3">Belum ada data luaran yang diunggah.</div>
                                                    @endforelse
                                                </div>
                                            </div>

                                            @if($isResearch)
                                                <!-- Research Specific: Integrasi Pendidikan -->
                                                <div class="mb-3">
                                                    <label class="text-slate-400 fw-bold fs-6 mb-2 d-flex justify-content-between align-items-center uppercase tracking-wider">
                                                        <span>INTEGRASI PENDIDIKAN (MBKM/MATA KULIAH)</span>
                                                        <span class="badge bg-indigo-lt fs-6">Kriteria 5</span>
                                                    </label>
                                                    <div class="glass-card p-3 bg-indigo-50/20 border-indigo-100">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <div class="avatar bg-indigo-100 text-indigo-600 rounded-circle">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M22 9l-10 -4l-10 4l10 4l10 -4v6" /><path d="M6 10.6v5.4a6 3 0 0 0 12 0v-5.4" /></svg>
                                                            </div>
                                                            <div>
                                                                <div class="fw-bold text-slate-800 fs-5">{{ count($selectedReview->proposal->student_members ?? []) }} Mahasiswa Terlibat</div>
                                                                <p class="text-slate-500 fs-6 mb-0">Hasil penelitian telah diintegrasikan ke dalam aktivitas akademik.</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @else
                                            <div class="alert alert-warning py-3 text-center fs-6 border-dashed">Laporan belum diunggah oleh pengusul.</div>
                                        @endif
                                    </div>

                                    <!-- Tab 2: Rencana -->
                                    <div class="tab-pane fade" id="tab-rencana">
                                        <div class="mb-4">
                                            <label class="text-slate-400 fw-bold fs-6 mb-2 d-flex justify-content-between align-items-center uppercase tracking-wider">
                                                <span>ABSTRAKSI PROPOSAL (RENCANA AWAL)</span>
                                                <span class="badge bg-blue-lt fs-6">Kriteria 3</span>
                                            </label>
                                            <div class="glass-card p-3 bg-blue-50/10 border-blue-100">
                                                <p class="text-slate-700 fs-5 mb-0" style="text-align: justify; line-height: 1.5;">{{ $selectedReview->proposal?->summary }}</p>
                                            </div>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <div class="glass-card p-2 text-center border-slate-100">
                                                    <div class="text-slate-400 fs-6 fw-bold">SKEMA</div>
                                                    <div class="fw-bold text-slate-800 fs-5">{{ $selectedReview->proposal?->researchScheme?->name ?: ($selectedReview->proposal?->communityServiceScheme?->name ?: 'N/A') }}</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="glass-card p-2 text-center border-slate-100">
                                                    <div class="text-slate-400 fs-6 fw-bold">TKT TARGET</div>
                                                    <div class="fw-bold text-slate-800 fs-5">Level {{ $selectedReview->proposal?->tkt_target ?? 'N/A' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tab 3: Logbook -->
                                    <div class="tab-pane fade" id="tab-logbook">
                                        <div class="space-y-2">
                                            @forelse($selectedReview->proposal?->dailyNotes ?? [] as $note)
                                                <div class="p-2 px-3 border rounded-3 bg-white mb-2 shadow-sm">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span class="fw-bold text-slate-900 fs-6">{{ $note->activity_date->format('d/m/Y') }}</span>
                                                        <span class="badge bg-blue-lt fs-6">{{ $note->progress_percentage }}%</span>
                                                    </div>
                                                    <p class="text-slate-600 fs-6 mb-1">{{ $note->activity_description }}</p>
                                                    @if($note->hasMedia('evidence'))
                                                        @php $media = $note->getFirstMedia('evidence'); @endphp
                                                        <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}" target="_blank" class="text-blue fw-bold fs-6">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M15 7l-6.5 6.5a1.5 1.5 0 0 0 3 3l6.5 -6.5a3 3 0 0 0 -6 -6l-6.5 6.5a4.5 4.5 0 0 0 9 9l6.5 -6.5" /></svg>
                                                            Lihat Bukti
                                                        </a>
                                                    @endif
                                                </div>
                                            @empty
                                                <div class="text-center py-4 text-slate-400 fs-6 border border-dashed rounded-3">Belum ada catatan logbook.</div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- RIGHT SIDE: Evaluation Panel -->
                            <div class="col-md-6 d-flex flex-column bg-white h-100 position-relative">
                                <div class="p-3 border-bottom bg-blue-50/20 d-flex justify-content-between align-items-center flex-shrink-0">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-blue-600 p-1.5 rounded-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="text-white" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M4 6c0 1.657 3.582 3 8 3s8 -1.343 8 -3s-3.582 -3 -8 -3s-8 1.343 -8 3z"></path><path d="M4 12c0 1.258 2.083 2.327 5 2.768"></path><path d="M8 18c-2.917 -.441 -5 -1.51 -5 -2.768v-3.232"></path><path d="M12 21c-4.418 0 -8 -1.343 -8 -3v-3"></path></svg>
                                        </div>
                                        <h3 class="fw-bold text-slate-800 mb-0 fs-4">Instrumen Penilaian</h3>
                                    </div>
                                    <div class="text-end ps-3 border-start">
                                        <div class="fw-black text-slate-800 fs-1 lh-1">{{ $this->totalScore }}</div>
                                        <div class="fs-6 text-slate-500 fw-bold uppercase letter-spacing-tight">Total Nilai</div>
                                    </div>
                                </div>

                                <div class="flex-grow-1 overflow-auto p-3 stylish-scrollbar">
                                    @if($this->activeCriteria->isNotEmpty())
                                        <!-- Header Table -->
                                        <div class="row g-0 bg-slate-100 rounded-2 p-2 mb-2 d-none d-md-flex text-slate-500 fw-bold fs-6 letter-spacing-tight">
                                            <div class="col-8">KRITERIA</div>
                                            <div class="col-1 text-center">BOBOT</div>
                                            <div class="col-3 text-center">PENILAIAN</div>
                                        </div>

                                        <div class="space-y-1">
                                            @foreach($this->activeCriteria as $index => $criteria)
                                                @php $key = \Illuminate\Support\Str::snake($criteria->criteria); @endphp
                                                <div class="row g-0 align-items-center py-2 px-2 border-bottom border-slate-50 hover-bg-slate-50 rounded-2 transition-all">
                                                    <div class="col-md-8 pe-3">
                                                        <div class="d-flex gap-2">
                                                            <div class="bg-blue-50 text-blue-600 rounded-circle fw-bold d-flex align-items-center justify-content-center" style="width: 20px; height: 20px; flex-shrink: 0; font-size: 0.75rem;">{{ $index+1 }}</div>
                                                            <div>
                                                                <div class="fw-bold text-slate-800 fs-5 mb-0">{{ $criteria->criteria }}</div>
                                                                <p class="text-slate-500 fs-6 mb-0" style="text-align: justify; line-height: 1.3;">{{ $criteria->description }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-1 text-center">
                                                        <span class="text-slate-400 fw-bold fs-6">{{ $criteria->weight }}%</span>
                                                    </div>
                                                    <div class="col-md-3 ps-2" x-data="{ showWarning: false }">
                                                        <div class="input-group mb-1 shadow-sm">
                                                            <span class="input-group-text bg-blue-600 text-white border-blue-600 px-2 fw-bold" style="font-size: 0.6rem;">SKOR</span>
                                                            <input type="number" wire:model.live="borang_data.{{ $key }}_score" 
                                                                   class="form-control text-center fw-black border-2 border-blue-600 text-blue-900 bg-blue-50 focus-ring" 
                                                                   style="font-size: 1.25rem; height: 40px;" min="0" max="100"
                                                                   @input="if($el.value > 100) { $el.value = 100; showWarning = true; setTimeout(() => { showWarning = false; }, 3000); $wire.set('borang_data.{{ $key }}_score', 100); } else if ($el.value < 0) { $el.value = 0; $wire.set('borang_data.{{ $key }}_score', 0); }">
                                                        </div>
                                                        <div x-show="showWarning" x-transition.opacity style="display: none;" class="text-danger fw-bold mb-1 letter-spacing-tight" style="font-size: 0.7rem !important;">*Maksimal Skor 100</div>
                                                        <textarea wire:model.live="borang_data.{{ $key }}_notes" 
                                                                  class="form-control form-control-sm fs-6 py-2 bg-slate-50 border-slate-300 focus-ring hover-bg-slate-50" 
                                                                  rows="2" placeholder="Catatan per kriteria..."></textarea>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <!-- Decision Console (Premium & Functional) -->
                                <div class="bg-blue-50 p-4 border-top border-blue-200 shadow-lg flex-shrink-0 position-relative z-3">
                                    <div class="row g-3 align-items-center">
                                        <!-- Score Accumulator block -->
                                        <div class="col-md-3 d-flex flex-column justify-content-center text-center border-end border-blue-200 pe-3">
                                            <div class="text-blue-800 fs-6 fw-bold uppercase letter-spacing-tight mb-2">Skor Akumulasi</div>
                                            <div class="bg-white rounded-3 border-2 border-blue-600 py-3 shadow-sm d-flex justify-content-center align-items-baseline gap-1">
                                                <span class="fw-black text-blue-700 lh-1" style="font-size: 2.75rem;">{{ $this->totalScore }}</span>
                                                <span class="fs-4 fw-bold text-blue-400">/100</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Form & Submit block -->
                                        <div class="col-md-9 ps-md-4">
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-5">
                                                    <label class="text-blue-900 fs-6 fw-bold mb-2 d-block">REKOMENDASI AKHIR</label>
                                                    <select wire:model.live="status" class="form-select bg-white border-slate-300 text-slate-800 fw-bold py-2 fs-5 @error('status') is-invalid @enderror">
                                                        <option value="">-- Berikan Putusan --</option>
                                                        <option value="sangat_baik">SANGAT BAIK (Lanjut)</option>
                                                        <option value="baik">BAIK (Sedikit Perbaikan)</option>
                                                        <option value="cukup">CUKUP (Perlu Perhatian)</option>
                                                    </select>
                                                    @error('status') <span class="text-danger fs-6 fw-bold mt-1 d-block">{{ $message }}</span> @enderror
                                                </div>
                                                <div class="col-md-7">
                                                    <label class="text-blue-900 fs-6 fw-bold mb-2 d-block">CATATAN STRATEGIS REVIEWER</label>
                                                    <textarea wire:model.live="notes" 
                                                              class="form-control bg-white border-slate-300 text-slate-800 fs-5 py-2 @error('notes') is-invalid @enderror" 
                                                              rows="2" placeholder="Berikan catatan singkat (minimal 10 huruf)..."></textarea>
                                                    @error('notes') <span class="text-danger fs-6 fw-bold mt-1 d-block">{{ $message }}</span> @enderror
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-end align-items-center gap-3">
                                                @if($selectedReview->reviewed_at)
                                                    <div class="d-flex flex-column align-items-end gap-1">
                                                        <div class="text-success fs-4 fw-black d-flex align-items-center bg-white px-4 py-2 rounded-3 border-2 border-success shadow-sm">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-shield-check me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                                <path d="M9 12l2 2l4 -4"></path>
                                                                <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3"></path>
                                                            </svg>
                                                            TTD Digital Berhasil Terverifikasi
                                                        </div>
                                                        <div class="text-slate-400 fs-6 italic">Waktu TTD: {{ $selectedReview->reviewed_at->format('d/m/Y H:i') }} WIB</div>
                                                    </div>
                                                @else
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="text-blue-800 fs-6 italic text-end">
                                                            Data dapat disimpan sebagai draft.<br>
                                                            TTD Digital baru dilakukan saat <strong>Submit</strong>.
                                                        </div>
                                                        <div class="d-flex gap-2">
                                                            <button wire:click="saveReview" 
                                                                    wire:loading.attr="disabled"
                                                                    class="btn btn-white btn-lg px-4 fw-bold shadow-sm">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" /><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M14 4l0 4l-4 0l0-4" /></svg>
                                                                SIMPAN DRAFT
                                                            </button>
                                                            <button wire:click="submitReview" 
                                                                    wire:loading.attr="disabled"
                                                                    class="btn btn-blue btn-lg px-5 fw-black shadow-lg hover-scale"
                                                                    style="border-radius: 0.75rem;">
                                                                SUBMIT & TTD
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($showReviewModal)
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
