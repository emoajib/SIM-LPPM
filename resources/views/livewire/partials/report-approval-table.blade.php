{{-- Shared partial: tabel laporan akhir untuk Kaprodi / Admin LPPM / dll.
     Vetted by AI - Manual Review Required by Senior Engineer/Manager

     Variabel yang diharapkan:
     - $role: string — 'kaprodi' | 'admin_lppm' (untuk future role-specific UI)
--}}
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="card-table table table-vcenter table-hover">
            <thead class="bg-light">
                <tr>
                    <th class="ps-3">Judul Proposal</th>
                    <th>Jenis</th>
                    <th>Pengusul / Prodi</th>
                    <th class="text-center">Status</th>
                    <th>Tgl Update</th>
                    <th class="w-1 text-center pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @if ($statusFilter === 'belum_laporan')
                    {{-- Mode: tampilkan Proposal (belum ada ProgressReport) --}}
                    @forelse ($this->reports as $proposal)
                        <tr wire:key="proposal-{{ $proposal->id }}">
                            <td class="text-wrap ps-3">
                                <div class="text-reset fw-bold">{{ $proposal->title }}</div>
                                <div class="mt-1">
                                    <span class="badge bg-warning-lt text-uppercase" style="font-size: 0.65rem;">Belum Laporan</span>
                                    @if ($proposal->researchScheme)
                                        <span class="badge bg-secondary-lt ms-1" style="font-size: 0.65rem;">{{ $proposal->researchScheme->name }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if ($proposal->detailable_type === 'App\Models\Research')
                                    <span class="badge bg-blue-lt">Penelitian</span>
                                @else
                                    <span class="badge bg-green-lt">Pengabdian</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $proposal->submitter->name }}</div>
                                <div class="small text-secondary">
                                    {{ $proposal->submitter->identity?->studyProgram?->name ?? '—' }}
                                    @if(isset($role) && $role === 'admin_lppm')
                                        ({{ $proposal->submitter->identity?->faculty?->name ?? '—' }})
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-warning text-white fw-bold px-2 py-1 shadow-sm">
                                    <i class="ti ti-alert-triangle me-1"></i>Belum Laporan
                                </span>
                            </td>
                            <td>
                                <div class="text-secondary small">—</div>
                            </td>
                            <td class="text-center pe-3">
                                {{-- Tombol Ingatkan / Pantau Proposal --}}
                                @php
                                    $proposalRoute = $proposal->detailable_type === 'App\Models\Research'
                                        ? route('research.proposal.show', $proposal)
                                        : route('community-service.proposal.show', $proposal);
                                @endphp
                                <a href="{{ $proposalRoute }}" class="btn btn-sm btn-outline-warning" wire:navigate.hover>
                                    <x-lucide-eye class="icon me-1" /> Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-5 text-center text-muted">
                                <div class="empty bg-transparent">
                                    <div class="empty-icon text-muted opacity-25"><i class="ti ti-circle-check fs-1"></i></div>
                                    <p class="empty-title">Semua proposal sudah mengajukan laporan akhir.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                @else
                    {{-- Mode normal: tampilkan ProgressReport --}}
                    @forelse ($this->reports as $report)
                        <tr wire:key="report-{{ $report->id }}">
                            <td class="text-wrap ps-3">
                                <div class="text-reset fw-bold">{{ $report->proposal->title }}</div>
                                <div class="mt-1">
                                    <x-tabler.badge variant="outline" class="text-uppercase" style="font-size: 0.65rem;">Laporan Akhir</x-tabler.badge>
                                    @if ($report->proposal->researchScheme)
                                        <span class="badge bg-secondary-lt ms-1" style="font-size: 0.65rem;">{{ $report->proposal->researchScheme->name }}</span>
                                    @elseif ($report->proposal->communityServiceScheme)
                                        <span class="badge bg-secondary-lt ms-1" style="font-size: 0.65rem;">{{ $report->proposal->communityServiceScheme->name }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if ($report->proposal->detailable_type === 'App\Models\Research')
                                    <span class="badge bg-blue-lt">Penelitian</span>
                                @else
                                    <span class="badge bg-green-lt">Pengabdian</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $report->proposal->submitter->name }}</div>
                                <div class="small text-secondary">
                                    {{ $report->proposal->submitter->identity?->studyProgram?->name ?? '—' }}
                                    @if(isset($role) && $role === 'admin_lppm')
                                        ({{ $report->proposal->submitter->identity?->faculty?->name ?? '—' }})
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                @if($report->status === \App\Enums\ReportStatus::APPROVED_BY_DEKAN)
                                    <span class="badge bg-purple text-white fw-bold px-2 py-1 shadow-sm">
                                        <i class="ti ti-check me-1"></i>Siap Disahkan LPPM
                                    </span>
                                @elseif($report->status === \App\Enums\ReportStatus::SUBMITTED)
                                    <span class="badge bg-info text-white fw-bold px-2 py-1 shadow-sm">
                                        <i class="ti ti-clock me-1"></i>Menunggu Dekan
                                    </span>
                                @elseif($report->status === \App\Enums\ReportStatus::APPROVED)
                                    <span class="badge bg-success text-white fw-bold px-2 py-1 shadow-sm">
                                        <i class="ti ti-check-double me-1"></i>Sudah Disahkan
                                    </span>
                                @elseif($report->status === \App\Enums\ReportStatus::REJECTED)
                                    <span class="badge bg-danger text-white fw-bold px-2 py-1 shadow-sm">
                                        <i class="ti ti-alert-circle me-1"></i>Perlu Revisi
                                    </span>
                                @else
                                    <span class="badge bg-secondary text-white fw-bold px-2 py-1">
                                        {{ $report->status?->label() ?? '—' }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="text-secondary small">{{ $report->updated_at?->format('d M Y H:i') ?? '—' }}</div>
                            </td>
                            <td class="text-center pe-3">
                                @php
                                    $showRoute = $report->proposal->detailable_type === 'App\Models\Research'
                                        ? route('research.final-report.show', $report->proposal)
                                        : route('community-service.final-report.show', $report->proposal);
                                @endphp
                                <a href="{{ $showRoute }}" class="btn btn-sm btn-primary" wire:navigate.hover>
                                    <x-lucide-eye class="icon me-1" /> Tinjau
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-5 text-center text-muted">
                                <div class="empty bg-transparent">
                                    <div class="empty-icon text-muted opacity-25"><i class="ti ti-file-off fs-1"></i></div>
                                    <p class="empty-title">Tidak ada laporan akhir yang sesuai filter.</p>
                                    <p class="empty-subtitle text-muted">Silakan ubah kata kunci atau filter status pelaporan.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                @endif
            </tbody>
        </table>
    </div>

    @if ($this->reports->hasPages())
        <div class="d-flex align-items-center card-footer border-0">
            {{ $this->reports->links() }}
        </div>
    @endif
</div>
