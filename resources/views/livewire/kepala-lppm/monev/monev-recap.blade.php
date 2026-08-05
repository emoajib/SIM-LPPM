<div>
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Rekapitulasi Monev Kolektif (Kepala LPPM)
                    </h2>
                    <div class="text-muted mt-1 text-uppercase">Tahun Akademik: {{ $academicYear }} | Semester:
                        {{ $semester }}</div>
                    <div class="mt-2">
                        <span class="badge bg-purple-lt shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg>
                            Terintegrasi dengan Laporan Institusi Pusat
                        </span>
                    </div>
                </div>
                <div class="col-auto ms-auto">
                    <button class="btn btn-teal shadow-sm" wire:click="approveAll"
                        onclick="confirm('Setujui semua hasil monev yang telah difinalisasi LPPM?') || event.stopImmediatePropagation()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                        Setujui Semua
                    </button>
                        <a href="{{ route('reports.monev.pdf', ['academic_year' => $academicYear, 'semester' => $semester, 'preview' => 1]) }}"
                            class="btn btn-outline-info ms-2" target="_blank">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg>
                            Tinjau PDF
                        </a>
                        <a href="{{ route('reports.monev.pdf', ['academic_year' => $academicYear, 'semester' => $semester]) }}"
                            class="btn btn-info ms-2" target="_blank">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><path d="M12 11v6" /><path d="M9 14l3 3l3 -3" /></svg>
                            Unduh PDF
                        </a>
                        <a href="{{ route('export.monev.recap', ['academic_year' => $academicYear, 'semester' => $semester]) }}"
                            class="btn btn-success ms-2" target="_blank">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24"
                                stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" />
                                <path d="M7 11l5 5l5 -5" />
                                <path d="M12 4l0 12" />
                            </svg>
                            Ekspor Excel
                        </a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            {{-- Institutional Report Approval Section --}}
            @if(active_role_is('kepala lppm') || active_role_is('rektor'))
                <div class="card mb-3 border-primary shadow-sm glass-card">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="d-flex align-items-center mb-1">
                                <h3 class="card-title h3 mb-0 me-2 text-primary">Validasi Dokumen Institusi (Monev)</h3>
                                @if($institutionalReport)
                                    <span class="badge bg-{{ $institutionalReport->status->color() }}-lt">
                                        {{ $institutionalReport->status->label() }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary-lt">Belum Diajukan</span>
                                @endif
                            </div>
                            <p class="text-secondary mb-0 small">
                                @if(!$institutionalReport || $institutionalReport->status === \App\Enums\InstitutionalReportStatus::DRAFT)
                                    Rekapitulasi monev periode {{ $academicYear }} belum diajukan ke Rektor.
                                @elseif($institutionalReport->status === \App\Enums\InstitutionalReportStatus::SUBMITTED)
                                    Menunggu persetujuan dan tanda tangan digital Rektor.
                                @elseif($institutionalReport->status === \App\Enums\InstitutionalReportStatus::APPROVED)
                                    Telah disahkan Rektor pada {{ $institutionalReport->approved_at->format('d M Y H:i') }}.
                                @elseif($institutionalReport->status === \App\Enums\InstitutionalReportStatus::REJECTED)
                                    Perbaikan: <strong>{{ $institutionalReport->notes }}</strong>
                                @endif
                            </p>
                        </div>
                        <div class="btn-list">
                            @if(active_role_is('kepala lppm') && (!$institutionalReport || in_array($institutionalReport->status, [\App\Enums\InstitutionalReportStatus::DRAFT, \App\Enums\InstitutionalReportStatus::REJECTED])))
                                <button class="btn btn-primary shadow-sm" wire:click="reportToRektor"
                                    wire:loading.attr="disabled"
                                    onclick="confirm('Apakah Anda yakin ingin melaporkan hasil monev periode ini ke Rektor?') || event.stopImmediatePropagation()">
                                    <i class="ti ti-send me-2"></i>
                                    Ajukan ke Rektor
                                </button>
                            @endif

                            @if(active_role_is('rektor') && ($institutionalReport?->status === \App\Enums\InstitutionalReportStatus::SUBMITTED))
                                <button class="btn btn-outline-danger shadow-sm" data-bs-toggle="modal"
                                    data-bs-target="#modal-reject-institutional">
                                    <i class="ti ti-x me-2"></i>
                                    Minta Perbaikan
                                </button>
                                <button class="btn btn-success shadow-sm" wire:click="approveInstitutionalReport('monev', {{ $academicYear }})"
                                    wire:loading.attr="disabled">
                                    <i class="ti ti-circle-check me-2"></i>
                                    Setujui & Tanda Tangani
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                                        <path d="M21 21l-6 -6" />
                                    </svg>
                                </span>
                                <input type="text" wire:model.live="search" class="form-control"
                                    placeholder="Cari judul/dosen...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select wire:model.live="academicYear" class="form-select">
                                @foreach($this->academicYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select wire:model.live="semester" class="form-select">
                                <option value="all">Semua Semester</option>
                                <option value="ganjil">Ganjil</option>
                                <option value="genap">Genap</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Pengusul & Judul</th>
                                <th>Reviewer</th>
                                <th>Skor</th>
                                <th>Status Akhir</th>
                                <th>Status Approval</th>
                                <th>BA</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->reviews as $review)
                                <tr>
                                    <td>
                                        <div class="font-weight-bold">{{ $review->proposal->submitter->name }}</div>
                                        <div class="text-muted text-truncate" style="max-width: 350px;">
                                            {{ $review->proposal->title }}</div>
                                    </td>
                                    <td>{{ $review->reviewer->name }}</td>
                                    <td>{{ $review->score ?? '-' }}</td>
                                    <td>
                                        @if($review->status)
                                            <span class="badge bg-blue-lt">{{ $review->status }}</span>
                                        @else
                                            <span class="text-muted italic">Belum dinilai</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($review->reported_to_rektor_at)
                                            <span class="badge bg-purple-lt">Dilaporkan ke Rektor</span>
                                        @elseif($review->approved_by_kepala_at)
                                            <span class="badge bg-success-lt">Disetujui Kepala</span>
                                        @elseif($review->finalized_by_lppm_at)
                                            <span class="badge bg-warning-lt">Menunggu Approval</span>
                                        @else
                                            <span class="badge bg-secondary-lt">Proses LPPM</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('export.monev.ba', $review->id) }}" class="btn btn-sm btn-outline-info" target="_blank">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>
                                            Unduh BA
                                        </a>
                                    </td>
                                    <td>
                                        @if(!$review->approved_by_kepala_at)
                                            <button class="btn btn-sm btn-success font-weight-bold" wire:confirm="Anda yakin mengesahkan BA Monev ini? Barcode TTD Anda akan digenerate otomatis." wire:click="approveReview('{{ $review->id }}')" 
                                                title="Sahkan Monev">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                                                Sahkan (TTD)
                                            </button>
                                        @else
                                            <span class="badge bg-success-lt font-weight-bold p-2" title="Disahkan pada {{ $review->approved_by_kepala_at->format('d/m/Y H:i') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                                                Sudah Disahkan
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Tidak ada data monev untuk periode
                                        ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    {{ $this->reviews->links() }}
                </div>
            </div>
        </div>
    </div>

    @if(active_role_is('rektor'))
        <div class="modal modal-blur fade" id="modal-reject-institutional" tabindex="-1" role="dialog" aria-hidden="true"
            wire:ignore.self>
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Alasan Penolakan / Permintaan Perbaikan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div>
                            <label class="form-label">Catatan untuk Kepala LPPM</label>
                            <textarea class="form-control" wire:model="approvalNotes" rows="3"
                                placeholder="Masukkan alasan atau instruksi perbaikan..."></textarea>
                            @error('approvalNotes') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary me-auto"
                            data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-danger shadow-sm"
                            wire:click="rejectInstitutionalReport('monev', '{{ $academicYear }}')">
                            Simpan & Tolak
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>