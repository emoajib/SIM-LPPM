<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 font-weight-bold mb-0 text-primary">{{ $pageTitle }}</h2>
            <p class="text-muted mb-0">{{ $pageSubtitle }}</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="dropdown">
                <button class="btn btn-white shadow-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <x-lucide-calendar class="icon me-2 text-primary" />
                    Tahun: {{ $period }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                    @foreach($this->periods as $availablePeriod)
                        <li>
                            <button class="dropdown-item @if($availablePeriod == $period) active @endif"
                                wire:click="setPeriod('{{ $availablePeriod }}')">
                                {{ $availablePeriod }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
            <button wire:click="previewPdf" wire:loading.attr="disabled" class="btn btn-outline-primary shadow-sm">
                <i class="ti ti-eye me-2" wire:loading.remove wire:target="previewPdf"></i>
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" wire:loading
                    wire:target="previewPdf"></span>
                <span>Tinjau PDF</span>
            </button>
            <a href="{{ route('admin.iku.export-pdf', ['period' => $period]) }}" class="btn btn-primary shadow-sm"
                data-navigate-ignore="true" target="_blank">
                <x-lucide-printer class="icon me-2" />
                Cetak PDF
            </a>
            <a href="{{ route('admin.iku.export-excel', ['period' => $period]) }}" class="btn btn-success shadow-sm"
                data-navigate-ignore="true" target="_blank">
                <x-lucide-file-spreadsheet class="icon me-2" />
                Export Excel
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        @foreach($this->ikuMetrics as $key => $metric)
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 overflow-hidden" style="border-radius: 16px;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div class="badge px-3 py-2 rounded-pill bg-soft-primary text-primary text-uppercase font-weight-bold"
                                style="font-size: 0.7rem;">
                                {{ $metric['code'] }}
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                @if(isset($metric['is_manual']) && $metric['is_manual'])
                                    <span class="badge bg-warning-lt px-2" title="Manual Input Aktif">
                                        <x-lucide-edit-3 class="icon icon-sm me-1" />
                                        Manual
                                    </span>
                                @endif
                                @if($metric['achievement'] >= $metric['target'])
                                    <div class="d-flex align-items-center text-success small font-weight-bold">
                                        <x-lucide-check-circle class="icon icon-sm me-1" />
                                        Mencapai Target
                                    </div>
                                @else
                                    <div class="d-flex align-items-center text-warning small font-weight-bold">
                                        <x-lucide-alert-circle class="icon icon-sm me-1" />
                                        Berjalan
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Interactive Gauge Widget -->
                        <div class="gauge-container mb-4">
                            <div class="gauge-wrapper">
                                <div class="gauge-body"></div>
                                <div class="gauge-fill"
                                    style="--gauge-deg: {{ min($metric['achievement'] * 1.8, 180) }}deg; 
                                                   --gauge-color: {{ $metric['achievement'] >= $metric['target'] ? '#10b981' : '#2563eb' }};">
                                </div>
                                <div class="gauge-target-line"
                                    style="--target-deg: {{ min($metric['target'] * 1.8, 180) }}deg;">
                                    <div class="gauge-target-marker"></div>
                                </div>
                                <div class="gauge-cover">
                                    <span class="gauge-value">{{ number_format($metric['achievement'], 1) }}%</span>
                                    <span class="gauge-label">Capaian</span>
                                </div>
                            </div>
                        </div>

                        <h4 class="h6 font-weight-semi-bold text-dark mb-2 text-center">{{ $metric['name'] }}</h4>
                        <p class="small text-muted mb-4 text-center" style="height: 3em; overflow: hidden;">
                            {{ $metric['description'] }}
                        </p>

                        <div class="progress mb-2 d-none" style="height: 6px; border-radius: 10px; background: #f1f5f9;">
                            <div class="progress-bar @if($metric['achievement'] >= $metric['target']) bg-success @else bg-primary @endif"
                                role="progressbar"
                                style="width: {{ min($metric['achievement'], 100) }}%; border-radius: 10px;"
                                aria-valuenow="{{ $metric['achievement'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>

                        <div class="d-flex justify-content-between small mb-3">
                            <span class="text-muted">Progres: {{ round($metric['achievement'], 1) }}%</span>
                            <span class="font-weight-bold text-dark">Target: {{ $metric['target'] }}%</span>
                        </div>

                        <button wire:click="selectIku('{{ $key }}')"
                            class="btn @if($selectedIku == $key) btn-primary @else btn-outline-primary @endif btn-sm w-100 rounded-pill">
                            <x-lucide-list class="icon icon-sm me-1" />
                            Lihat Rincian
                        </button>
                    </div>

                    @if($metric['achievement'] >= $metric['target'])
                        <div class="bg-success py-1 text-center text-white small" style="font-size: 0.65rem;">
                            TARGET TERCAPAI
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if($selectedIku)
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
            <div class="card-header bg-white border-0 p-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="font-weight-bold mb-0">
                    <x-lucide-table class="icon text-primary me-2" />
                    Rincian Data: {{ strtoupper($selectedIku) }} - {{ $this->ikuMetrics[$selectedIku]['name'] }}
                </h5>
                <button wire:click="selectIku(null)" class="btn btn-sm btn-light rounded-circle" title="Tutup" aria-label="Tutup">
                    <x-lucide-x class="icon" />
                </button>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            @if(strtoupper($selectedIku) == 'IKU03')
                                <tr>
                                    <th>Nama Mahasiswa</th>
                                    <th>Program Studi</th>
                                    <th>Kegiatan</th>
                                    <th>Status</th>
                                </tr>
                            @elseif(strtoupper($selectedIku) == 'IKU04')
                                <tr>
                                    <th>Nama Dosen</th>
                                    <th>NIDN/NIK</th>
                                    <th>Scopus ID</th>
                                    <th>SINTA ID</th>
                                    <th>WoS ID</th>
                                </tr>
                            @elseif(strtoupper($selectedIku) == 'IKU05')
                                <tr>
                                    <th>Judul Kegiatan</th>
                                    <th>Ketua Pengusul</th>
                                    <th>Nama Mitra</th>
                                    <th class="text-center">Bobot Skor</th>
                                </tr>
                            @elseif(strtoupper($selectedIku) == 'IKU06')
                                <tr>
                                    <th>Judul Artikel</th>
                                    <th>Nama Jurnal / Penerbit</th>
                                    <th>Peringkat (Q/S)</th>
                                    <th>Pengindeks</th>
                                    <th class="text-center">Bobot</th>
                                </tr>
                            @elseif(strtoupper($selectedIku) == 'IKU07')
                                <tr>
                                    <th>Judul Kegiatan</th>
                                    <th>Ketua Pengusul</th>
                                    <th>Target SDGs</th>
                                </tr>
                            @elseif(strtoupper($selectedIku) == 'IKU08')
                                <tr>
                                    <th>Nama Dosen</th>
                                    <th>Keterlibatan Kebijakan / Rekognisi Pakar</th>
                                </tr>
                            @else
                                <tr>
                                    <th colspan="5">Data rincian tidak tersedia untuk IKU ini (Hubungi Admin)</th>
                                </tr>
                            @endif
                        </thead>
                        <tbody>
                            @forelse($this->selectedMetricDetails as $row)
                                @if(strtoupper($selectedIku) == 'IKU03')
                                    <tr>
                                        <td>{{ $row['name'] ?? 'N/A' }}</td>
                                        <td>{{ $row['prodi'] ?? 'N/A' }}</td>
                                        <td>{{ $row['activity'] ?? 'N/A' }}</td>
                                        <td><span class="badge bg-success-lt">Aktif</span></td>
                                    </tr>
                                @elseif(strtoupper($selectedIku) == 'IKU04')
                                    <tr>
                                        <td class="font-weight-semi-bold">{{ $row['name'] }}</td>
                                        <td>{{ $row['id_number'] }}</td>
                                        <td><span class="text-muted">{{ $row['scopus'] ?? '-' }}</span></td>
                                        <td><span class="text-muted">{{ $row['sinta'] ?? '-' }}</span></td>
                                        <td><span class="text-muted">{{ $row['wos'] ?? '-' }}</span></td>
                                    </tr>
                                @elseif(strtoupper($selectedIku) == 'IKU05')
                                    <tr>
                                        <td class="font-weight-semi-bold">{{ $row['title'] }}</td>
                                        <td>{{ $row['submitter'] }}</td>
                                        <td>{{ $row['partners'] }}</td>
                                        <td class="text-center"><span
                                                class="badge bg-soft-primary text-primary">{{ $row['weight'] }}</span></td>
                                    </tr>
                                @elseif(strtoupper($selectedIku) == 'IKU06')
                                    <tr>
                                        <td class="font-weight-semi-bold">{{ $row['title'] }}</td>
                                        <td>{{ $row['journal'] }}</td>
                                        <td><span class="badge bg-info text-white">{{ $row['rank'] }}</span></td>
                                        <td>{{ $row['indexing'] }}</td>
                                        <td class="text-center"><span
                                                class="badge bg-soft-primary text-primary">{{ $row['weight'] }}</span></td>
                                    </tr>
                                @elseif(strtoupper($selectedIku) == 'IKU07')
                                    <tr>
                                        <td class="font-weight-semi-bold">{{ $row['title'] }}</td>
                                        <td>{{ $row['submitter'] }}</td>
                                        <td><span class="small">{{ $row['sdgs'] }}</span></td>
                                    </tr>
                                @elseif(strtoupper($selectedIku) == 'IKU08')
                                    <tr>
                                        <td class="font-weight-semi-bold">{{ $row['name'] }}</td>
                                        <td>{{ $row['policies'] }}</td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4 text-muted small">Tidak ada data rincian untuk
                                        periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- Info Section (Premium Look) -->
    <div class="card border-0 shadow-sm"
        style="border-radius: 16px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #e2e8f0;">
        <div class="card-body p-5">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h2 class="h3 font-weight-bold text-white mb-3">Sinkronisasi Data IKU & SINTA</h2>
                    <p class="lead mb-4" style="opacity: 0.8;">Modul ini mengintegrasikan data manajemen hibah LPPM
                        dengan Indikator Kinerja Utama Perguruan Tinggi secara real-time. Memastikan setiap luaran
                        publikasi (Scopus/SINTA) dan kerjasama terdeteksi secara otomatis.</p>
                    <div class="d-flex gap-4">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-primary p-2 me-3 d-flex align-items-center justify-content-center"
                                style="width: 40px; height: 40px;">
                                <x-lucide-refresh-ccw class="icon text-white" style="width: 20px;" />
                            </div>
                            <div>
                                <h5 class="mb-0 text-white" style="font-size: 0.9rem;">Auto-Sync</h5>
                                <span class="small" style="opacity: 0.6;">Dari Data Hibah</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-success p-2 me-3 d-flex align-items-center justify-content-center"
                                style="width: 40px; height: 40px;">
                                <x-lucide-shield-check class="icon text-white" style="width: 20px;" />
                            </div>
                            <div>
                                <h5 class="mb-0 text-white" style="font-size: 0.9rem;">Kepmen 358/2025</h5>
                                <span class="small" style="opacity: 0.6;">Standar Kepatuhan</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 d-none d-lg-block text-center">
                    <div class="display-1 text-white opacity-25">
                        <x-lucide-gauge style="width: 150px; height: 150px;" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .icon {
            width: 1.25rem;
            height: 1.25rem;
        }

        .icon-sm {
            width: 1rem;
            height: 1rem;
        }

        .bg-soft-primary {
            background-color: rgba(37, 99, 235, 0.1);
        }

        .font-weight-semi-bold {
            font-weight: 600;
        }

        .font-weight-bold {
            font-weight: 700;
        }

        /* Gauge Speedometer Styles */
        .gauge-container {
            width: 100%;
            max-width: 160px;
            margin: 0 auto;
            position: relative;
        }

        .gauge-wrapper {
            position: relative;
            width: 100%;
            padding-bottom: 50%;
            overflow: hidden;
        }

        .gauge-body {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 200%;
            border-radius: 50%;
            background: #f1f5f9;
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .gauge-fill {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 200%;
            border-radius: 50%;
            background: conic-gradient(from 180deg at 50% 50%,
                    var(--gauge-color, #2563eb) 0deg,
                    var(--gauge-color, #2563eb) var(--gauge-deg, 0deg),
                    transparent var(--gauge-deg, 0deg));
            transform: rotate(0deg);
            transition: all 1s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .gauge-cover {
            position: absolute;
            top: 15%;
            left: 15%;
            width: 70%;
            height: 140%;
            background: #fff;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding-top: 15%;
            z-index: 2;
        }

        .gauge-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: #1e293b;
            line-height: 1;
        }

        .gauge-label {
            font-size: 0.65rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
        }

        .gauge-target-line {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 200%;
            z-index: 5;
            pointer-events: none;
        }

        .gauge-target-marker {
            position: absolute;
            top: 0;
            left: 50%;
            width: 2px;
            height: 10%;
            background: #ef4444;
            transform-origin: 50% 500%;
            transform: translateX(-50%) rotate(calc(var(--target-deg, 0deg) - 90deg));
            z-index: 10;
        }
    </style>
</div>