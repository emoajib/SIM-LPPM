<div>
    <x-slot:pageHeader>
        {{-- Custom actions can go here if needed --}}
    </x-slot:pageHeader>

    {{-- Filter Bar --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Cari Laporan</label>
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <circle cx="10" cy="10" r="7" />
                                <line x1="21" y1="21" x2="15" y2="15" />
                            </svg>
                        </span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control"
                            placeholder="Cari catatan atau pengaju...">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Jenis Laporan</label>
                    <select wire:model.live="type" class="form-select">
                        <option value="all">Semua Jenis</option>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select wire:model.live="status" class="form-select">
                        <option value="all">Semua Status</option>
                        @foreach(\App\Enums\InstitutionalReportStatus::cases() as $s)
                            <option value="{{ $s->value }}">{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tahun</label>
                    <select wire:model.live="year" class="form-select">
                        <option value="all">Semua Tahun</option>
                        @foreach($availableYears as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button
                        wire:click="$set('search', ''); $set('type', 'all'); $set('status', 'all'); $set('year', 'all');"
                        class="btn btn-outline-secondary w-100">
                        Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Table --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        <th>Laporan</th>
                        <th>Tahun</th>
                        <th>Diajukan Oleh</th>
                        <th>Tgl. Pengajuan</th>
                        <th>Status</th>
                        <th>Disetujui Oleh</th>
                        <th class="w-1">Opsi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $report)
                        <tr>
                            <td>
                                <div class="font-weight-bold">{{ $types[$report->type] ?? ucfirst($report->type) }}</div>
                                <div class="text-muted small">
                                    @if($report->notes)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline me-1" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M3 20l1.3 -3.9a9 8 0 1 1 3.4 2.9l-4.7 1" />
                                            <line x1="12" y1="12" x2="12" y2="12.01" />
                                            <line x1="12" y1="9" x2="12" y2="9.01" />
                                            <line x1="12" y1="15" x2="12" y2="15.01" />
                                        </svg>
                                        {{ str()->limit($report->notes, 30) }}
                                    @else
                                        Tidak ada catatan
                                    @endif
                                </div>
                            </td>
                            <td>{{ $report->year }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-xs me-2"
                                        style="background-image: url({{ $report->submitter?->profile_picture }})"></span>
                                    <div class="small">{{ $report->submitter?->name ?? '-' }}</div>
                                </div>
                            </td>
                            <td>
                                {{ $report->submitted_at ? $report->submitted_at->translatedFormat('d M Y H:i') : '-' }}
                            </td>
                            <td>
                                <span class="badge bg-{{ $report->status->color() }}-lt">
                                    {{ $report->status->label() }}
                                </span>
                            </td>
                            <td>
                                @if($report->approver)
                                    <div class="small fw-bold">{{ $report->approver->name }}</div>
                                    <div class="text-muted smaller">{{ $report->approved_at?->translatedFormat('d/m/Y') }}</div>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    {{-- Review Link based on type --}}
                                    @php
                                        $route = match ($report->type) {
                                            'research' => 'reports.research',
                                            'pkm' => 'reports.pkm',
                                            'output' => 'reports.outputs',
                                            'partner' => 'reports.partners',
                                            'iku' => 'reports.iku',
                                            'monev' => 'kepala-lppm.monev.recap',
                                            'reviewer' => 'reports.reviewer',
                                            default => null
                                        };
                                        $params = ['period' => $report->year];
                                        if ($report->type === 'output')
                                            $params = ['period' => $report->year];
                                        
                                        if ($report->type === 'monev') {
                                            $params = [
                                                'academic_year' => $report->year,
                                                'semester' => $report->metadata['semester'] ?? 'all'
                                            ];
                                        }

                                        // Handle metadata filters if present
                                        $metadata = $report->metadata ?? [];
                                        $params = array_merge($params, $metadata);
                                    @endphp

                                    @if($route)
                                        <a href="{{ route($route, $params) }}" class="btn btn-icon btn-white"
                                            title="Lihat Detail & Filter" data-bs-toggle="tooltip">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <circle cx="12" cy="12" r="2" />
                                                <path
                                                    d="M22 12c-2.667 4.667 -6 7 -10 7s-7.333 -2.333 -10 -7c2.667 -4.667 6 -7 10 -7s7.333 2.333 10 7" />
                                            </svg>
                                        </a>
                                    @endif

                                    @php
                                        $pdfRoute = match ($report->type) {
                                            'research' => 'reports.research.pdf',
                                            'pkm' => 'reports.pkm.pdf',
                                            'output' => 'reports.output.pdf',
                                            'partner' => 'reports.partner.pdf',
                                            'iku' => 'admin.iku.export-pdf',
                                            'monev' => 'reports.monev.pdf',
                                            'reviewer' => 'reports.reviewer.pdf',
                                            default => null
                                        };
                                        
                                        $excelRoute = match ($report->type) {
                                            'research' => 'reports.research.excel',
                                            'pkm' => 'reports.pkm.excel',
                                            'output' => 'reports.output.excel',
                                            'partner' => 'reports.partner.excel',
                                            'iku' => 'admin.iku.export-excel',
                                            'monev' => 'export.monev.recap',
                                            'reviewer' => 'reports.reviewer.excel',
                                            default => null
                                        };
                                    @endphp

                                    @if($pdfRoute)
                                        <a href="{{ route($pdfRoute, array_merge($params, ['preview' => 1])) }}"
                                            target="_blank" class="btn btn-outline-primary btn-sm shadow-sm"
                                            title="Tinjau PDF" data-bs-toggle="tooltip">
                                            <x-lucide-eye class="icon me-1" />
                                            Tinjau PDF
                                        </a>
                                    @endif

                                    @if($report->status === \App\Enums\InstitutionalReportStatus::APPROVED || $report->status === \App\Enums\InstitutionalReportStatus::SUBMITTED)
                                        @if($pdfRoute)
                                            <a href="{{ route($pdfRoute, array_merge($params, ['export' => 'pdf'])) }}"
                                                class="btn btn-primary btn-sm shadow-sm" title="Unduh PDF Resmi"
                                                data-bs-toggle="tooltip" data-navigate-ignore="true">
                                                <x-lucide-printer class="icon me-1" />
                                                Unduh PDF
                                            </a>
                                        @endif
                                        
                                        @if($excelRoute)
                                            <a href="{{ route($excelRoute, $params) }}"
                                                class="btn btn-success btn-sm shadow-sm" title="Unduh Excel"
                                                data-bs-toggle="tooltip" data-navigate-ignore="true">
                                                <x-lucide-file-spreadsheet class="icon me-1" />
                                                Unduh Excel
                                            </a>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="empty">
                                    <div class="empty-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="icon icon-tabler icon-tabler-database-off" width="24" height="24"
                                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path
                                                d="M12.983 8.978c3.955 -.182 7.017 -1.446 7.017 -2.978c0 -1.657 -3.582 -3 -8 -3c-1.661 0 -3.204 .19 -4.483 .515m-2.783 1.228c-.471 .382 -.734 .808 -.734 1.257c0 1.22 1.944 2.271 4.734 2.74" />
                                            <path
                                                d="M4 6v6c0 1.657 3.582 3 8 3c.986 0 1.93 -.067 2.802 -.19m3.187 -.825c1.187 -.478 2.011 -1.201 2.011 -1.985v-6" />
                                            <path
                                                d="M4 12v6c0 1.657 3.582 3 8 3c.396 0 .783 -.01 1.158 -.03m3.842 -1.18c1.841 -.58 3 -1.42 3 -2.39v-6" />
                                            <path d="M3 3l18 18" />
                                        </svg>
                                    </div>
                                    <p class="empty-title">Tidak ada laporan ditemukan</p>
                                    <p class="empty-subtitle text-muted">
                                        Belum ada pengajuan laporan institusi atau sesuaikan filter pencarian Anda.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($reports->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $reports->links() }}
            </div>
        @endif
    </div>
</div>