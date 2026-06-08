<div>
    <x-slot:pageHeader>
        {{-- Header empty as requested --}}
    </x-slot:pageHeader>

    <x-slot:pageActions>
        <div class="btn-list">
            @php
                $exportParams = ['search' => $search, 'typeFilter' => $typeFilter, 'periodFilter' => $periodFilter];
            @endphp
            <a href="{{ route('reports.partner.pdf', array_merge($exportParams, ['preview' => 1])) }}"
                class="btn btn-outline-info shadow-sm" target="_blank" title="Tinjau PDF">
                <i class="ti ti-eye me-2"></i>
                <span>{{ __('Tinjau PDF') }}</span>
            </a>
            <a href="{{ route('reports.partner.excel', $exportParams) }}" class="btn btn-outline-success shadow-sm"
                data-navigate-ignore="true" title="Unduh Excel">
                <i class="ti ti-table me-2"></i>
                <span>{{ __('Unduh Excel') }}</span>
            </a>
            <a href="{{ route('reports.partner.pdf', $exportParams) }}" class="btn btn-outline-danger shadow-sm"
                data-navigate-ignore="true" title="Unduh PDF">
                <i class="ti ti-file-type-pdf me-2"></i>
                <span>{{ __('Unduh PDF') }}</span>
            </a>
        </div>
    </x-slot:pageActions>

    {{-- ①② container-xl: Filter Bar (dipindahkan ke atas) + Kartu Validasi Institusi --}}
    {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
    <div class="container-xl mt-3">

        {{-- ① Filter Bar — sekarang di atas (sebelum kartu validasi), mengikuti pola research.blade.php --}}
        <div class="card mb-3 shadow-sm border-0 glass-card">
            <div class="card-body p-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-primary"></i>
                            </span>
                            <input type="text" wire:model.live.debounce.300ms="search"
                                class="form-control" placeholder="Nama, institusi, atau email...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select wire:model.live="typeFilter" class="form-select">
                            <option value="">— Semua Jenis —</option>
                            @foreach($this->partnerTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select wire:model.live="periodFilter" class="form-select">
                            <option value="">— Semua Tahun —</option>
                            @foreach($this->periods as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- ⑤ Reset button selalu tampil di col-auto ms-auto (konsisten dengan research.blade.php) --}}
                    <div class="col-auto ms-auto">
                        <button class="btn btn-icon btn-white shadow-sm" wire:click="resetFilters" title="Reset Filter">
                            <i class="ti ti-refresh text-secondary"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ② Kartu Validasi Institusi (Kerjasama) — dipindahkan setelah filter --}}
        @if(active_role() === 'kepala lppm' || active_role() === 'rektor')
            <div class="card mb-3 border-primary shadow-sm glass-card">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="d-flex align-items-center mb-1">
                            <h3 class="card-title h3 mb-0 me-2 text-primary">Validasi Dokumen Institusi (Kerjasama)</h3>
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
                                Rekapitulasi kerjasama mitra periode {{ $periodFilter ?: date('Y') }} belum diajukan ke Rektor.
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
                        <!-- Draft Preview Icon (Support System) -->
                        <a href="{{ route('reports.partner.pdf', array_merge(['search' => $search, 'typeFilter' => $typeFilter, 'periodFilter' => $periodFilter], ['preview' => 1])) }}"
                            target="_blank" class="btn btn-outline-primary shadow-sm" title="Tinjau Draft PDF">
                            <i class="ti ti-eye me-2"></i> Tinjau PDF
                        </a>

                        @if(active_role() === 'kepala lppm' && (!$institutionalReport || in_array($institutionalReport->status, [\App\Enums\InstitutionalReportStatus::DRAFT, \App\Enums\InstitutionalReportStatus::REJECTED])))
                            <button class="btn btn-primary shadow-sm"
                                wire:click="submitInstitutionalReport('partner', {{ $periodFilter ?: date('Y') }})"
                                wire:loading.attr="disabled">
                                <i class="ti ti-send me-2"></i>
                                Ajukan ke Rektor
                            </button>
                        @endif

                        @if(active_role() === 'rektor' && ($institutionalReport?->status === \App\Enums\InstitutionalReportStatus::SUBMITTED))
                            <button class="btn btn-outline-danger shadow-sm" data-bs-toggle="modal"
                                data-bs-target="#modal-reject-institutional">
                                <i class="ti ti-x me-2"></i>
                                Minta Perbaikan
                            </button>
                            <button class="btn btn-success shadow-sm"
                                wire:click="approveInstitutionalReport('partner', {{ $periodFilter ?: date('Y') }})"
                                wire:loading.attr="disabled">
                                <i class="ti ti-circle-check me-2"></i>
                                Setujui &amp; Tanda Tangani
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- ③ Summary KPI Cards — desain seragam dengan research.blade.php (progress bar berwarna, tanpa border-start) --}}
    <div class="row row-deck mb-4">
        @foreach($this->summary as $card)
            <div class="col-sm-6 col-xl-3">
                <div class="card card-stacked shadow-sm border-0">
                    <div class="d-flex align-items-center gap-3 card-body">
                        <div class="p-3 {{ explode(' ', $card['variant'])[0] }} bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center"
                            style="width: 56px; height: 56px;">
                            {{-- ④ Fix typo icon: 'ti-chart-dotsfs-2' → 'ti-chart-dots fs-1' --}}
                            @switch($card['icon'])
                                @case('handshake')       <i class="ti ti-users-group fs-1"></i>       @break
                                @case('file-check')      <i class="ti ti-file-certificate fs-1"></i>  @break
                                @case('users')           <i class="ti ti-user-check fs-1"></i>        @break
                                @case('currency-dollar') <i class="ti ti-coins fs-1"></i>             @break
                                @default                 <i class="ti ti-chart-dots fs-1"></i>
                            @endswitch
                        </div>
                        <div>
                            <div class="text-secondary small fw-medium mb-1">{{ $card['label'] }}</div>
                            <h2 class="mb-0 fw-bold">{{ $card['value'] }}</h2>
                        </div>
                    </div>
                    <div class="progress progress-xs card-progress">
                        <div class="progress-bar {{ explode(' ', $card['variant'])[0] }}" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ④ Tabel Mitra + Panel Detail --}}
    <div class="row g-3">
        {{-- Tabel Mitra --}}
        <div class="{{ $selectedPartnerId ? 'col-lg-7' : 'col-12' }}">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">
                        <x-lucide-handshake class="icon me-2" />
                        Daftar Mitra Kerjasama
                    </h3>
                    <span class="badge bg-blue-lt">{{ $partners->total() }} mitra</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover">
                        <thead>
                            <tr>
                                <th>Mitra</th>
                                <th class="text-center">Jenis</th>
                                <th class="text-center">Proposal</th>
                                <th class="text-center">Disetujui</th>
                                <th class="text-center">Dana (Rp)</th>
                                <th class="text-center">MOU / PKS</th>
                                <th class="text-center">Surat Kesediaan</th>
                                <th class="text-center">Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($partners as $partner)
                                <tr class="{{ $selectedPartnerId === $partner->id ? 'table-active' : '' }}"
                                    wire:key="row-{{ $partner->id }}">
                                    <td>
                                        <div class="fw-medium">{{ $partner->name }}</div>
                                        @if($partner->institution)
                                            <div class="text-muted small">{{ $partner->institution }}</div>
                                        @endif
                                        @if($partner->email)
                                            <div class="text-muted small">
                                                <x-lucide-mail class="icon-sm me-1" />
                                                {{ $partner->email }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($partner->type)
                                            <span class="badge bg-azure-lt">{{ $partner->type }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-bold">{{ $partner->proposals_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($partner->approved_count > 0)
                                            <span class="badge bg-green-lt text-green">{{ $partner->approved_count }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($partner->total_budget > 0)
                                            <span class="text-green fw-semibold">{{ number_format($partner->total_budget, 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($partner->hasMedia('mou_pks'))
                                            @php $media = $partner->getFirstMedia('mou_pks'); @endphp
                                            <a data-navigate-ignore="true" href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                                download="{{ $media->file_name ?? $media->name ?? 'download' }}" target="_blank"
                                                class="badge bg-green-lt text-green text-decoration-none" title="Lihat MOU/PKS">
                                                <x-lucide-file-check class="icon-sm me-1" />
                                                Ada
                                            </a>
                                        @else
                                            <span class="badge bg-yellow-lt text-yellow">Belum</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        {{-- Surat Kesediaan (Indikator saja) --}}
                                        @if($partner->hasMedia('commitment_letter'))
                                            <span class="badge bg-green-lt text-green" title="Tersedia di rincian proposal">
                                                <x-lucide-check-circle class="icon-sm me-1" />
                                                Ada
                                            </span>
                                        @else
                                            <span class="badge bg-muted-lt text-muted" title="Belum ada surat kesediaan">
                                                -
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button"
                                            wire:click="selectPartner('{{ $partner->id }}')"
                                            class="btn btn-sm {{ $selectedPartnerId === $partner->id ? 'btn-primary' : 'btn-outline-primary' }}"
                                            title="Lihat Detail Proposal">
                                            <x-lucide-eye class="icon" />
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <x-lucide-search class="icon mb-2" />
                                        <div>Tidak ada mitra yang cocok dengan filter.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    {{ $partners->links() }}
                </div>
            </div>
        </div>

        {{-- Panel Detail Proposal Mitra --}}
        @if($selectedPartnerId && $detailProposals !== null)
            @php $selectedPartner = $partners->firstWhere('id', $selectedPartnerId) ?? \App\Models\Partner::with('media')->find($selectedPartnerId); @endphp
            <div class="col-lg-5" wire:key="detail-panel">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="card-title mb-0">{{ $selectedPartner?->name }}</h4>
                            <div class="text-muted small">{{ $selectedPartner?->institution }}</div>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            @if($selectedPartner?->hasMedia('mou_pks'))
                                @php $media = $selectedPartner->getFirstMedia('mou_pks'); @endphp
                                <a data-navigate-ignore="true" href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                    download="{{ $media->file_name ?? $media->name ?? 'download' }}" target="_blank"
                                    class="btn btn-sm btn-outline-primary" title="Unduh MOU/PKS">
                                    <x-lucide-file-text class="icon" /> MOU/PKS
                                </a>
                            @endif
                            {{-- ⑦ Tambah title tooltip pada tombol tutup panel --}}
                            <button type="button" wire:click="$set('selectedPartnerId', null)"
                                class="btn btn-sm btn-ghost-secondary"
                                title="Tutup panel detail">
                                <x-lucide-x class="icon" />
                            </button>
                        </div>
                    </div>

                    {{-- Info Mitra --}}
                    <div class="card-body border-bottom pb-3">
                        <div class="row g-2 text-sm">
                            @if($selectedPartner?->type)
                                <div class="col-6">
                                    <div class="text-muted small">Jenis</div>
                                    <div class="fw-medium">{{ $selectedPartner->type }}</div>
                                </div>
                            @endif
                            @if($selectedPartner?->country)
                                <div class="col-6">
                                    <div class="text-muted small">Negara</div>
                                    <div class="fw-medium">{{ $selectedPartner->country }}</div>
                                </div>
                            @endif
                            @if($selectedPartner?->email)
                                <div class="col-12">
                                    <div class="text-muted small">Email</div>
                                    <div class="fw-medium">{{ $selectedPartner->email }}</div>
                                </div>
                            @endif
                            @if($selectedPartner?->address)
                                <div class="col-12">
                                    <div class="text-muted small">Alamat</div>
                                    <div class="fw-medium">{{ $selectedPartner->address }}</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- ⑥ Section divider: ganti card-header salah konteks dengan div separator proper --}}
                    <div class="border-top px-3 py-2 bg-light-lt d-flex align-items-center">
                        <span class="small fw-semibold text-muted text-uppercase">Proposal Terkait</span>
                        <span class="badge bg-blue-lt ms-2">{{ $detailProposals->count() }}</span>
                    </div>

                    {{-- Daftar Proposal --}}
                    <div class="list-group list-group-flush" style="max-height: 450px; overflow-y: auto;">
                        @forelse($detailProposals as $proposal)
                            @php
                                $isResearch = $proposal->detailable_type === 'App\Models\Research';
                                $typeLabel  = $isResearch ? 'Penelitian' : 'PKM';
                                $typeColor  = $isResearch ? 'bg-blue-lt text-blue' : 'bg-green-lt text-green';
                            @endphp
                            <div class="list-group-item" wire:key="p-{{ $proposal->id }}">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <a href="{{ $isResearch
                                            ? route('research.proposal.show', $proposal->id)
                                            : route('community-service.proposal.show', $proposal->id) }}"
                                           class="text-body d-block fw-medium text-decoration-none">
                                            {{ Str::limit($proposal->title, 60) }}
                                        </a>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <span class="badge {{ $typeColor }}">{{ $typeLabel }}</span>
                                            <span class="text-muted small">{{ $proposal->start_year }}</span>
                                            <span class="text-muted small">{{ $proposal->submitter?->name }}</span>

                                            {{-- Tombol Surat Kesediaan Spesifik Proposal --}}
                                            @if($selectedPartner->hasCommitmentForProposal($proposal->id))
                                                <a href="{{ $selectedPartner->getCommitmentUrlForProposal($proposal->id) }}"
                                                   target="_blank"
                                                   class="btn btn-sm btn-icon btn-ghost-success ms-auto"
                                                   title="Unduh Surat Kesediaan Mitra (Proposal Ini)">
                                                    <x-lucide-file-check class="icon-sm" />
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        @php
                                            $statusColors = [
                                                'draft'           => 'bg-secondary-lt',
                                                'submitted'       => 'bg-blue-lt',
                                                'approved'        => 'bg-green-lt text-green',
                                                'revision'        => 'bg-yellow-lt text-yellow',
                                                'rejected'        => 'bg-red-lt text-red',
                                                'completed'       => 'bg-teal-lt text-teal',
                                                'under_review'    => 'bg-purple-lt',
                                            ];
                                            $statusColor = $statusColors[$proposal->status->value ?? ''] ?? 'bg-secondary-lt';
                                        @endphp
                                        <span class="badge {{ $statusColor }}">
                                            {{ $proposal->status->label() }}
                                        </span>
                                        @if($proposal->sbk_value > 0)
                                            <div class="text-muted small text-end mt-1">
                                                Rp {{ number_format($proposal->sbk_value, 0, ',', '.') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="list-group-item text-center text-muted py-4">
                                <x-lucide-inbox class="icon mb-2" />
                                <div>Belum ada proposal terkait mitra ini.</div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if(active_role() === 'rektor')
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
                            wire:click="rejectInstitutionalReport('partner', '{{ $periodFilter ?: date('Y') }}')">
                            Simpan &amp; Tolak
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
