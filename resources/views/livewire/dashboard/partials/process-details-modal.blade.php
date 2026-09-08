{{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
@if($activeProcessType)
    <div class="modal modal-blur fade show d-block" tabindex="-1" role="dialog" aria-modal="true" style="background: rgba(15, 23, 42, 0.65); z-index: 1060;" wire:keydown.escape="closeProcessModal">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                <!-- Modal Header -->
                <div class="modal-header bg-surface py-3 px-4 border-bottom d-flex align-items-center">
                    <div class="avatar bg-{{ $this->processModalColor }}-lt text-{{ $this->processModalColor }} avatar-md me-3 shadow-sm border-0">
                        <i class="{{ $this->processModalIcon }} fs-2"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold text-dark mb-0">
                            {{ $this->processModalTitle }}
                        </h4>
                        <div class="text-muted small mt-1">
                            {{ $this->processModalDescription }}
                        </div>
                    </div>
                    <button type="button" class="btn-close ms-auto" wire:click="closeProcessModal" aria-label="Close"></button>
                </div>

                <!-- Modal Sub-header (Filter & Search) -->
                <div class="modal-body p-3 bg-light border-bottom">
                    <div class="row g-2 align-items-center justify-content-between">
                        <!-- Type Filter Pills -->
                        <div class="col-12 col-md-auto">
                            <div class="btn-group btn-group-sm w-100" role="group">
                                <button type="button" class="btn {{ $processTypeFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary bg-white' }}" wire:click="$set('processTypeFilter', 'all')">
                                    Semua Jenis
                                </button>
                                <button type="button" class="btn {{ $processTypeFilter === 'research' ? 'btn-primary' : 'btn-outline-secondary bg-white' }}" wire:click="$set('processTypeFilter', 'research')">
                                    <i class="ti ti-flask me-1"></i>Penelitian
                                </button>
                                <button type="button" class="btn {{ $processTypeFilter === 'community_service' ? 'btn-primary' : 'btn-outline-secondary bg-white' }}" wire:click="$set('processTypeFilter', 'community_service')">
                                    <i class="ti ti-users me-1"></i>Pengabdian (PKM)
                                </button>
                            </div>
                        </div>

                        <!-- Instant Live Search -->
                        <div class="col-12 col-md-4">
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <i class="ti ti-search"></i>
                                </span>
                                <input type="text" class="form-control form-control-sm bg-white" placeholder="Cari judul usulan / pengusul..." wire:model.live.debounce.300ms="processSearch">
                                @if(!empty($processSearch))
                                    <span class="input-icon-addon pe-2" style="cursor: pointer;" wire:click="$set('processSearch', '')">
                                        <i class="ti ti-x text-muted"></i>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Body: Scrollable Table -->
                <div class="modal-body p-0" style="max-height: 560px; overflow-y: auto;">
                    <div class="table-responsive">
                        <table class="table table-vcenter table-hover card-table mb-0">
                            <thead class="bg-surface text-muted" style="position: sticky; top: 0; z-index: 10; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                <tr>
                                    <th class="ps-4" style="width: 42%;">Judul & Pengusul</th>
                                    <th style="width: 20%;">Jenis & Skema</th>
                                    <th style="width: 18%;">Status Tahapan</th>
                                    <th style="width: 20%;" class="text-end pe-4">Detail & Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($this->processModalData as $item)
                                    @php
                                        $isResearch = str_contains($item->detailable_type, 'Research');
                                        $showRoute = $isResearch 
                                            ? route('research.proposal.show', $item->id) 
                                            : route('community-service.proposal.show', $item->id);
                                        $schemeName = $isResearch 
                                            ? ($item->researchScheme->name ?? 'Tanpa Skema') 
                                            : ($item->communityServiceScheme->name ?? 'Tanpa Skema');
                                    @endphp
                                    <tr wire:key="proc-item-{{ $item->id }}">
                                        <!-- Judul & Pengusul -->
                                        <td class="ps-4">
                                            <a href="{{ $showRoute }}" target="_blank" class="fw-bold text-dark text-decoration-none lh-sm d-block mb-1 hover-primary" title="{{ $item->title }}">
                                                {{ $item->title }}
                                                <i class="ti ti-external-link text-muted ms-1 fs-4"></i>
                                            </a>
                                            <div class="d-flex align-items-center text-muted small mt-1">
                                                <div class="avatar avatar-xs me-2 border-0 shadow-sm {{ $isResearch ? 'bg-primary-lt text-primary' : 'bg-azure-lt text-azure' }}">
                                                    {{ $item->submitter?->initials() }}
                                                </div>
                                                <div>
                                                    <span class="fw-medium text-dark">{{ $item->submitter?->name ?? 'Tanpa Nama' }}</span>
                                                    <span class="text-secondary ms-1">
                                                        • {{ $item->submitter?->identity?->studyProgram?->name ?? 'Prodi -' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Jenis & Skema -->
                                        <td>
                                            @if($isResearch)
                                                <span class="badge bg-primary-lt fw-bold px-2 py-1 mb-1">
                                                    <i class="ti ti-flask me-1"></i>Penelitian
                                                </span>
                                            @else
                                                <span class="badge bg-azure-lt fw-bold px-2 py-1 mb-1">
                                                    <i class="ti ti-users me-1"></i>Pengabdian
                                                </span>
                                            @endif
                                            <div class="small text-muted text-truncate" style="max-width: 180px;" title="{{ $schemeName }}">
                                                {{ $schemeName }}
                                            </div>
                                        </td>

                                        <!-- Status Tahapan Sesuai Konteks Proses -->
                                        <td>
                                            @if($activeProcessType === 'laporan_akhir')
                                                @if($item->latestFinalReport)
                                                    @php
                                                        $repStatus = $item->latestFinalReport->status;
                                                    @endphp
                                                    @if($repStatus === \App\Enums\ReportStatus::APPROVED)
                                                        <span class="badge bg-success text-white fw-bold px-2 py-1">
                                                            <i class="ti ti-check me-1"></i>Disetujui LPPM
                                                        </span>
                                                    @elseif($repStatus === \App\Enums\ReportStatus::APPROVED_BY_DEKAN)
                                                        <span class="badge bg-purple text-white fw-bold px-2 py-1">
                                                            <i class="ti ti-clock-check me-1"></i>Disetujui Dekan
                                                        </span>
                                                    @elseif($repStatus === \App\Enums\ReportStatus::SUBMITTED)
                                                        <span class="badge bg-primary text-white fw-bold px-2 py-1">
                                                            <i class="ti ti-send me-1"></i>Diajukan
                                                        </span>
                                                    @elseif($repStatus === \App\Enums\ReportStatus::REJECTED)
                                                        <span class="badge bg-danger text-white fw-bold px-2 py-1">
                                                            <i class="ti ti-alert-circle me-1"></i>Revisi Laporan
                                                        </span>
                                                    @elseif($repStatus === \App\Enums\ReportStatus::DRAFT)
                                                        <span class="badge bg-warning text-white fw-bold px-2 py-1">
                                                            <i class="ti ti-edit me-1"></i>Draf Laporan
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="badge bg-secondary-lt fw-bold px-2 py-1">
                                                        <i class="ti ti-hourglass me-1"></i>Belum Lapor
                                                    </span>
                                                @endif

                                            @elseif($activeProcessType === 'catatan_harian_keuangan')
                                                @if($item->logbook_approved_at)
                                                    <span class="badge bg-success text-white fw-bold px-2 py-1">
                                                        <i class="ti ti-check me-1"></i>LPJ Disahkan
                                                    </span>
                                                @elseif($item->daily_notes_count > 0 || $item->hasMedia('financial_report_scan') || $item->hasMedia('logbook_scan'))
                                                    <span class="badge bg-warning text-white fw-bold px-2 py-1">
                                                        <i class="ti ti-clock me-1"></i>Menunggu Pengesahan
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary-lt fw-bold px-2 py-1">
                                                        <i class="ti ti-minus me-1"></i>Belum Ada Catatan
                                                    </span>
                                                @endif

                                            @elseif($activeProcessType === 'perbaikan_usulan')
                                                @if($item->status->value === \App\Enums\ProposalStatus::REVISION_SUBMITTED->value)
                                                    <span class="badge bg-purple text-white fw-bold px-2 py-1">
                                                        <i class="ti ti-send me-1"></i>Revisi Diajukan
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning text-white fw-bold px-2 py-1">
                                                        <i class="ti ti-alert-triangle me-1"></i>Perlu Revisi Dosen
                                                    </span>
                                                @endif

                                            @else
                                                <span class="badge bg-{{ $item->status->color() }}-lt fw-bold px-2 py-1">
                                                    <span class="badge bg-{{ $item->status->color() }} me-1"></span>
                                                    {{ $item->status->label() }}
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Detail & Aksi -->
                                        <td class="text-end pe-4">
                                            @if($activeProcessType === 'catatan_harian_keuangan')
                                                <div class="small fw-bold text-dark">
                                                    Rp {{ number_format((int)($item->daily_notes_sum_amount ?? 0), 0, ',', '.') }}
                                                </div>
                                                <div class="text-muted small" style="font-size: 0.75rem;">
                                                    Pagu: Rp {{ number_format((int)($item->budget_items_sum_total_price ?? 0), 0, ',', '.') }} ({{ $item->daily_notes_count }} nota)
                                                </div>
                                                <div class="mt-1">
                                                    @php
                                                        $dailyNoteRoute = $isResearch
                                                            ? route('research.daily-note.show', $item->id)
                                                            : route('community-service.daily-note.show', $item->id);
                                                    @endphp
                                                    <a href="{{ $dailyNoteRoute }}" target="_blank" class="btn btn-outline-indigo btn-sm py-0 px-2" style="font-size: 0.75rem;">
                                                        <i class="ti ti-receipt me-1"></i>Buka Logbook
                                                    </a>
                                                </div>

                                            @elseif($activeProcessType === 'laporan_akhir')
                                                @if($item->latestFinalReport)
                                                    <div class="small text-muted mb-1" style="font-size: 0.75rem;">
                                                        Diperbarui: {{ $item->latestFinalReport->updated_at->format('d/m/Y') }}
                                                    </div>
                                                    @php
                                                        $finalReportRoute = $isResearch
                                                            ? route('research.final-report.show', $item->id)
                                                            : route('community-service.final-report.show', $item->id);
                                                    @endphp
                                                    <a href="{{ $finalReportRoute }}" target="_blank" class="btn btn-outline-success btn-sm py-0 px-2" style="font-size: 0.75rem;">
                                                        <i class="ti ti-file-text me-1"></i>Buka Laporan
                                                    </a>
                                                @else
                                                    <span class="text-muted small d-block">Belum ada berkas</span>
                                                    <a href="{{ $showRoute }}" target="_blank" class="btn btn-outline-secondary btn-sm py-0 px-2 mt-1" style="font-size: 0.75rem;">
                                                        <i class="ti ti-eye me-1"></i>Detail Usulan
                                                    </a>
                                                @endif

                                            @elseif($activeProcessType === 'perbaikan_usulan')
                                                <div class="small text-muted mb-1" style="font-size: 0.75rem;">
                                                    Update: {{ $item->updated_at->format('d/m/Y H:i') }}
                                                </div>
                                                @php
                                                    $revisionRoute = $isResearch
                                                        ? route('research.proposal-revision.show', $item->id)
                                                        : route('community-service.proposal-revision.show', $item->id);
                                                @endphp
                                                <a href="{{ $revisionRoute }}" target="_blank" class="btn btn-outline-warning btn-sm py-0 px-2" style="font-size: 0.75rem;">
                                                    <i class="ti ti-edit me-1"></i>Buka Revisi
                                                </a>

                                            @else
                                                <div class="small text-muted mb-1" style="font-size: 0.75rem;">
                                                    Diajukan: {{ $item->created_at->format('d/m/Y') }}
                                                </div>
                                                <a href="{{ $showRoute }}" target="_blank" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 0.75rem;">
                                                    <i class="ti ti-eye me-1"></i>Buka Proposal
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="ti ti-inbox fs-1 d-block mb-2 text-secondary"></i>
                                            <div class="fw-semibold">Tidak ada usulan yang sesuai dengan filter</div>
                                            <div class="small mt-1">Coba gunakan kata kunci pencarian lain atau pilih tab jenis yang berbeda.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer bg-surface py-2 px-4 border-top d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Menampilkan <strong>{{ $this->processModalData->count() }}</strong> usulan penelitian & PKM
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm px-4" wire:click="closeProcessModal">
                        <i class="ti ti-x me-1"></i>Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
