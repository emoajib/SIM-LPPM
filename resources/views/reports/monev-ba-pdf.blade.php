<!DOCTYPE html>
<html>
<head>
    <title>Berita Acara Monev</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        @page { 
            size: A4;
            margin: 4cm 3cm 3cm 4cm; /* Top Right Bottom Left - Standar Tata Naskah Dinas */
        }
        html, body { 
            margin: 0; 
            padding: 0; 
            border: 0; 
            font-family: "Arial", "Helvetica", sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #000;
        }
        body { 
            text-align: justify; 
            text-justify: inter-word;
        }
        .kop-surat { 
            border-bottom: 3pt double #000; 
            padding-bottom: 10px; 
            margin-bottom: 40px; 
            text-align: center; 
            position: relative; 
            width: 100%;
        }
        .logo { 
            position: absolute; 
            left: -10px; 
            top: -20px; 
            width: 100px; 
        }
        .header-text {
            margin-left: 90px;
        }
        .inst-name { 
            font-size: 14pt; 
            font-weight: bold; 
            line-height: 1.2;
            text-transform: uppercase;
        }
        .lppm-name { 
            font-size: 13pt; 
            font-weight: bold; 
            margin-top: 5px; 
            text-transform: uppercase;
        }
        .inst-address { 
            font-size: 9pt; 
            color: #000; 
            margin-top: 8px; 
            line-height: 1.3;
        }
        
        .title { 
            text-align: center; 
            font-weight: bold; 
            text-decoration: underline; 
            font-size: 12pt; 
            margin-bottom: 50px; 
            text-transform: uppercase; 
            line-height: 1.6;
        }
        
        .content-section { margin-bottom: 30px; }
        
        .proposal-info td {
            padding: 8px 0;
            vertical-align: top;
        }
        
        table.borang-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 25px; 
            margin-bottom: 25px; 
        }
        table.borang-table th, table.borang-table td { 
            border: 1pt solid #000; 
            padding: 15px 12px; 
            vertical-align: top; 
        }
        table.borang-table th { 
            background-color: #f2f2f2; 
            text-align: center; 
            font-weight: bold; 
            font-size: 11pt;
            text-transform: uppercase;
        }
        
        .signature-section { 
            margin-top: 80px; 
            width: 100%; 
            page-break-inside: avoid; 
        }
        .signature-table { width: 100%; border: none; }
        .signature-table td { width: 50%; border: none; text-align: center; vertical-align: top; padding: 20px 5px; }
        .sign-space { min-height: 120px; }
        .sign-qr { width: 90px; height: 90px; margin: 15px auto; }
        .sign-name { font-weight: bold; text-decoration: underline; margin-top: 15px; display: block; }
        
        .footer-info {
            position: fixed;
            bottom: -2cm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8.5pt;
            color: #555;
            font-style: italic;
            border-top: 1pt solid #ddd;
            padding-top: 8px;
        }
        
        strong { font-weight: bold; }

        @include('reports.partials.report-base-styles')
    </style>
</head>
<body>
    <div class="kop-surat">
        @if($pdfConfig['show_logo'] ?? true)
            {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
            <img src="{{ public_path('logo.png') }}" class="logo">
        @endif
        <div class="header-text">
            <div class="inst-name">INSTITUT TEKNOLOGI DAN SAINS NAHDLATUL ULAMA PEKALONGAN</div>
            <div class="lppm-name">LEMBAGA PENELITIAN DAN PENGABDIAN KEPADA MASYARAKAT</div>
            <div class="inst-address">Jl. Karangdowo No. 9, Kedungwuni, Kab. Pekalongan 51173</div>
        </div>
    </div>

    <div class="title">
        @if($review->proposal->detailable_type === \App\Models\Research::class)
            BERITA ACARA MONITORING DAN EVALUASI INTERNAL<br>PROGRAM PENELITIAN TAHUN ANGGARAN {{ $review->academic_year ?? date('Y') }}
        @else
            BERITA ACARA MONITORING DAN EVALUASI<br>PROGRAM PENGABDIAN KEPADA MASYARAKAT ITSNU PEKALONGAN
        @endif
    </div>

    <div class="content-section">
        <p>Pada hari ini, <strong>{{ $review->reviewed_at?->translatedFormat('l') ?? '..........' }}</strong>, tanggal <strong>{{ $review->reviewed_at?->translatedFormat('d F Y') ?? '..........' }}</strong>, bertempat di ITSNU Pekalongan, telah dilaksanakan Monitoring dan Evaluasi Internal untuk kemajuan pelaksanaan program sebagai berikut:</p>
    </div>

    <div class="content-section">
        <table class="proposal-info" style="width: 100%; border: none; border-collapse: collapse;">
            <tr>
                <td style="width: 180px; font-weight: bold;">Judul {{ $review->proposal->detailable_type === \App\Models\Research::class ? 'Penelitian' : 'Kegiatan' }}</td>
                <td>: <strong>{{ clean_proposal_title($review->proposal->title) }}</strong></td>
            </tr>
            @if($review->proposal->detailable_type === \App\Models\Research::class)
                <tr>
                    <td style="font-weight: bold;">Bidang Penelitian</td>
                    <td>: {{ $review->proposal?->focusArea?->name ?? '-' }}</td>
                </tr>
            @endif
            <tr>
                <td style="font-weight: bold;">Skema</td>
                <td>: {{ $review->proposal->researchScheme?->name ?? $review->proposal->communityServiceScheme?->name ?? '-' }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Ketua {{ $review->proposal->detailable_type === \App\Models\Research::class ? 'Peneliti' : 'Pelaksana' }}</td>
                <td>: {{ $review->proposal->submitter->name }} (NIDN: {{ $review->proposal->submitter->identity?->identity_id ?? '-' }})</td>
            </tr>
            @if($review->proposal->detailable_type === \App\Models\Research::class)
                <tr>
                    <td style="font-weight: bold;">Jabatan Fungsional</td>
                    <td>: {{ $review->proposal->submitter->identity?->functional_position ?? '-' }}</td>
                </tr>
            @else
                <tr>
                    <td style="font-weight: bold;">Dana Disetujui</td>
                    <td>: Rp {{ number_format($review->proposal?->sbk_value ?? 0, 0, ',', '.') }}</td>
                </tr>
            @endif
        </table>
    </div>

    <div class="content-section" style="margin-top: 20px;">
        <h4 style="margin-bottom: 5px; font-size: 11pt; border-bottom: 0.5pt solid #000; padding-bottom: 2px;">I. RINGKASAN KEMAJUAN PELAKSANAAN</h4>
        @if($activeReport)
            <div style="text-align: justify; margin-bottom: 15px;">
                {{ $activeReport->summary_update }}
            </div>
            
            <h5 style="margin-bottom: 5px; font-size: 10pt;">Capaian Luaran (Wajib & Tambahan):</h5>
            <table style="width: 100%; border-collapse: collapse; font-size: 10pt;">
                <thead>
                    <tr style="background-color: #f2f2f2;">
                        <th style="border: 0.5pt solid #000; padding: 5px; width: 30%;">Jenis Luaran</th>
                        <th style="border: 0.5pt solid #000; padding: 5px; width: 50%;">Judul / Nama Produk / Detail</th>
                        <th style="border: 0.5pt solid #000; padding: 5px; width: 20%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activeReport->mandatoryOutputs as $output)
                        <tr>
                            <td style="border: 0.5pt solid #000; padding: 5px;">{{ $output->proposalOutput?->type ?: 'Luaran Wajib' }}</td>
                            <td style="border: 0.5pt solid #000; padding: 5px;">{{ $output->article_title ?: ($output->product_name ?: ($output->book_title ?: '-')) }}</td>
                            <td style="border: 0.5pt solid #000; padding: 5px; text-align: center;">{{ ucfirst($output->status_type) }}</td>
                        </tr>
                    @empty
                        {{-- No mandatory outputs recorded --}}
                    @endforelse
                    
                    @foreach($activeReport->additionalOutputs as $output)
                        <tr>
                            <td style="border: 0.5pt solid #000; padding: 5px;">{{ $output->proposalOutput?->type ?: 'Luaran Tambahan' }}</td>
                            <td style="border: 0.5pt solid #000; padding: 5px;">{{ $output->article_title ?: ($output->product_name ?: ($output->book_title ?: '-')) }}</td>
                            <td style="border: 0.5pt solid #000; padding: 5px; text-align: center;">{{ ucfirst($output->status) }}</td>
                        </tr>
                    @endforeach
                    
                    @if($activeReport->mandatoryOutputs->isEmpty() && $activeReport->additionalOutputs->isEmpty())
                        <tr>
                            <td colspan="3" style="border: 0.5pt solid #000; padding: 10px; text-align: center; font-style: italic;">Belum ada luaran yang dilaporkan.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        @else
            <div style="border: 0.5pt dashed #999; padding: 10px; text-align: center; color: #666; font-style: italic;">
                Laporan kemajuan belum diunggah oleh peneliti/pelaksana untuk periode ini.
            </div>
        @endif
    </div>

    <h4 style="margin-bottom: 10px; margin-top: 25px; font-size: 11pt; border-bottom: 0.5pt solid #000; padding-bottom: 2px;">II. HASIL EVALUASI KOMPONEN</h4>
    <table class="borang-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 45%;">Kriteria Penilaian</th>
                <th style="width: 10%;">Bobot</th>
                <th style="width: 10%;">Skor</th>
                <th style="width: 30%;">Catatan / Justifikasi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($criteria as $item)
                @php 
                    $fieldKey = \Illuminate\Support\Str::snake($item->criteria);
                    $scoreVal = $review->borang_data[$fieldKey . '_score'] ?? 0;
                    $notesVal = $review->borang_data[$fieldKey . '_notes'] ?? '-';
                @endphp
                <tr>
                    <td style="text-align: center;">{{ $loop->iteration }}</td>
                    <td style="text-align: justify;">{{ $item->criteria }}</td>
                    <td style="text-align: center;">{{ $item->weight }}%</td>
                    <td style="text-align: center;">{{ $scoreVal }}</td>
                    <td style="text-align: justify; font-size: 10pt;">{{ $notesVal }}</td>
                </tr>
            @endforeach
            <tr style="background-color: #f9f9f9; font-weight: bold;">
                <td colspan="3" style="text-align: right;">SKOR TOTAL KESELURUHAN</td>
                <td style="text-align: center;">{{ $review->score }}</td>
                <td style="background-color: #fff;"></td>
            </tr>
        </tbody>
    </table>

    <div class="content-section">
        <h4 style="margin-bottom: 8px; font-size: 11pt;">II. REKOMENDASI DAN CATATAN:</h4>
        <div style="border: 0.5pt solid #000; padding: 10px; min-height: 60px; text-align: justify;">
            <strong>Rekomendasi:</strong> {{ strtoupper(str_replace('_', ' ', $review->status)) }}<br><br>
            <strong>Catatan Tambahan:</strong><br>
            {{ $review->notes ?? 'Reviewer tidak memberikan catatan tambahan.' }}
        </div>
    </div>

    <div class="signature-section">
        {{-- Row 1: Reviewer & Admin --}}
        <table class="signature-table">
            <tr>
                <td width="50%">
                    <p style="margin-bottom: 5px;">Monev Reviewer,</p>
                    <div class="sign-space">
                        @if($review->reviewed_at)
                            @if($qrReviewerUrl ?? null)
                                <img src="{{ generate_qr_code_data_uri($qrReviewerUrl, 160) }}" class="sign-qr">
                            @endif
                            <div style="font-size: 8pt; color: #333; margin-top: 2px;">
                                Terverifikasi sistem (QR)<br>
                                {{ $review->reviewed_at->format('d/m/Y H:i') }}
                            </div>
                        @else
                            <div style="height: 100px;"></div>
                        @endif
                    </div>
                    <div class="sign-name">{{ $review->reviewer->name }}</div>
                    <div style="font-size: 9pt;">NIDN: {{ $review->reviewer->identity?->identity_id ?? '-' }}</div>
                </td>
                <td width="50%">
                    <p style="margin-bottom: 5px;">Mengetahui,<br>Admin LPPM ITSNU Pekalongan</p>
                    <div class="sign-space">
                        @if($review->finalized_by_lppm_at)
                            @if($qrAdminUrl ?? null)
                                <img src="{{ generate_qr_code_data_uri($qrAdminUrl, 160) }}" class="sign-qr">
                            @endif
                            <div style="font-size: 8pt; color: #333; margin-top: 2px;">
                                Terverifikasi sistem (QR)<br>
                                {{ $review->finalized_by_lppm_at->format('d/m/Y H:i') }}
                            </div>
                        @else
                            <div style="height: 100px;"></div>
                        @endif
                    </div>
                    @php $admin = \App\Models\User::find($review->finalized_by_lppm_by); @endphp
                    <div class="sign-name">{{ $admin->name ?? '.......................' }}</div>
                    <div style="font-size: 9pt;">NIDN: {{ $admin->identity?->identity_id ?? '-' }}</div>
                </td>
            </tr>
        </table>

        {{-- Row 2: Kepala LPPM (Centered) --}}
        <table class="signature-table" style="margin-top: 20px;">
            <tr>
                <td style="text-align: center;">
                    <p style="margin-bottom: 5px;">Mengesahkan,<br>Kepala LPPM ITSNU Pekalongan</p>
                    <div class="sign-space">
                        @if($review->approved_by_kepala_at)
                            @if($qrKepalaUrl ?? null)
                                <img src="{{ generate_qr_code_data_uri($qrKepalaUrl, 180) }}" class="sign-qr" style="width: 90px; height: 90px;">
                            @endif
                            <div style="font-size: 8pt; color: #333; margin-top: 2px;">
                                Terverifikasi sistem (QR)<br>
                                {{ $review->approved_by_kepala_at->format('d/m/Y H:i') }}
                            </div>
                        @else
                             <div style="height: 110px;"></div>
                        @endif
                    </div>

                    @php $kepala = \App\Models\User::role('kepala lppm')->first(); @endphp
                    <div class="sign-name">{{ $kepala->name ?? '...........................................' }}</div>
                    <div style="font-size: 9pt;">NIDN: {{ $kepala->identity?->identity_id ?? '-' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer-info">
        Dokumen ini dihasilkan secara otomatis oleh SIM-LPPM ITSNU Pekalongan pada {{ ($generatedAt ?? now())->format('d/m/Y H:i') }}
    </div>
</body>
</html>
