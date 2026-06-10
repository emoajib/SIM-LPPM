{{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
<!DOCTYPE html>
<html>

<head>
    <title>Laporan Penugasan & Reviewer</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        @page {
            margin: 2cm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
            line-height: 1.4;
            color: #000;
            text-align: left;
        }
        .kop-surat {
            border-bottom: 2pt solid #000;
            padding-bottom: 2px;
            margin-bottom: 5px;
            position: relative;
        }
        .kop-surat-inner {
            border-bottom: 0.5pt solid #000;
            padding-bottom: 5px;
        }
        .logo {
            position: absolute;
            left: 0;
            top: 0;
            width: 55px;
        }
        .header-text {
            text-align: center;
            margin-left: 60px;
        }
        .inst-name {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .lppm-name {
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .inst-address {
            font-size: 7.5pt;
            color: #333;
        }
        .report-title-container {
            text-align: center;
            margin: 10px 0;
        }
        .report-title {
            font-size: 10pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .report-subtitle {
            font-size: 8pt;
            margin-top: 3px;
        }
        .section-header {
            font-size: 9pt;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 5px;
            text-transform: uppercase;
            border-bottom: 1px solid #ddd;
            padding-bottom: 2px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0 15px 0;
        }
        table.data-table th {
            background-color: #f2f2f2;
            border: 0.5pt solid #000;
            padding: 4px 6px;
            font-weight: bold;
            text-align: center;
            font-size: 8pt;
        }
        table.data-table td {
            border: 0.5pt solid #000;
            padding: 4px 6px;
            vertical-align: top;
            font-size: 7.5pt;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        /* Summary Metrics Table */
        table.metrics-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.metrics-table td {
            border: 0.5pt solid #ccc;
            padding: 6px;
            background-color: #f9f9f9;
            text-align: center;
            width: 25%;
        }
        .metric-value {
            font-size: 12pt;
            font-weight: bold;
            color: #206bc4;
        }
        .metric-label {
            font-size: 7.5pt;
            text-transform: uppercase;
            color: #666;
            margin-top: 2px;
        }

        .signature-wrapper {
            margin-top: 20px;
            page-break-inside: avoid;
        }
        table.signature-table {
            width: 100%;
            border: none;
        }
        table.signature-table td {
            border: none;
            padding: 0;
            vertical-align: top;
            text-align: center;
        }
        .sign-block {
            display: inline-block;
            text-align: center;
        }
        .sign-name {
            font-weight: bold;
            text-decoration: underline;
            margin-top: 50px;
        }
        .footer {
            position: fixed;
            bottom: -1cm;
            left: 0;
            right: 0;
            font-size: 7pt;
            text-align: center;
            color: #888;
        }
    </style>
</head>

<body>
    <!-- KOP SURAT -->
    <div class="kop-surat">
        <div class="kop-surat-inner">
            <img src="{{ get_logo_base64() }}" class="logo">
            <div class="header-text">
                <div class="inst-name">INSTITUT TEKNOLOGI DAN SAINS NAHDLATUL ULAMA PEKALONGAN</div>
                <div class="lppm-name">LEMBAGA PENELITIAN DAN PENGABDIAN KEPADA MASYARAKAT (LPPM)</div>
                <div class="inst-address">
                    Jl. Karangdowo No. 9, Kedungwuni, Kab. Pekalongan, Jawa Tengah 51173<br>
                    Email: lppm@itsnupekalongan.ac.id | Website: https://lppm.itsnupekalongan.ac.id
                </div>
            </div>
        </div>
    </div>

    <!-- TITLE -->
    <div class="report-title-container">
        <div class="report-title">LAPORAN PENUGASAN & EVALUASI REVIEWER</div>
        <div class="report-subtitle">Tahun Anggaran / Periode: <strong>{{ $period }}</strong></div>
    </div>

    <!-- SUMMARY METRICS -->
    <table class="metrics-table">
        <tr>
            <td>
                <div class="metric-value">{{ $summaryStats['total_proposals'] }}</div>
                <div class="metric-label">Total Proposal</div>
            </td>
            <td>
                <div class="metric-value">{{ $summaryStats['assigned'] }}</div>
                <div class="metric-label">Terplot Reviewer</div>
            </td>
            <td>
                <div class="metric-value">{{ $summaryStats['progress_percent'] }}%</div>
                <div class="metric-label">Review Selesai</div>
            </td>
            <td>
                <div class="metric-value">{{ $summaryStats['avg_score'] }}</div>
                <div class="metric-label">Rata-rata Skor</div>
            </td>
        </tr>
    </table>

    <!-- SECTION I: IKHTISAR PENUGASAN & NILAI REKAP -->
    @php
        $requiredReviewers = (int) \App\Models\Setting::get('reviewer_count_required', 2);
        $titleWidth = 46 - ($requiredReviewers * 8);
    @endphp
    <div class="section-header">I. Rekapitulasi Penugasan & Hasil Penilaian Proposal</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: {{ $titleWidth }}%;">Judul Proposal & Pengaju</th>
                <th style="width: 10%;">Jenis</th>
                <th style="width: 25%;">Reviewer Ditugaskan</th>
                @for ($i = 0; $i < $requiredReviewers; $i++)
                    <th style="width: 8%; text-align: center;">Skor Rev {{ $i + 1 }}</th>
                @endfor
                <th style="width: 7%; text-align: center;">Rata-rata</th>
            </tr>
        </thead>
        <tbody>
            @forelse($proposals as $index => $proposal)
                @php
                    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
                    $reviewersList = $proposal->reviewers;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <div class="font-bold">{{ $proposal->title }}</div>
                        <div style="color: #666; margin-top: 2px;">Dosen: {{ $proposal->submitter->name }} ({{ $proposal->submitter->identity->faculty->name ?? '-' }})</div>
                    </td>
                    <td class="text-center">{{ $proposal->detailable_type === 'App\Models\Research' ? 'Penelitian' : 'PKM' }}</td>
                    <td>
                        @forelse($proposal->reviewers as $rev)
                            <div style="margin-bottom: 2px;">• {{ $rev->user->name }}</div>
                        @empty
                            <div style="color: red; font-style: italic;">Belum Diplot</div>
                        @endforelse
                    </td>
                    @for ($i = 0; $i < $requiredReviewers; $i++)
                        @php
                            $r = $reviewersList[$i] ?? null;
                            $rScore = $r && $r->isCompleted() ? ($r->latestLog()->total_score ?? '-') : '-';
                        @endphp
                        <td class="text-center">{{ $rScore }}</td>
                    @endfor
                    <td class="text-center font-bold" style="color: blue;">{{ $proposal->score ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 5 + $requiredReviewers }}" class="text-center" style="padding: 15px; color: #888; font-style: italic;">
                        Tidak ada proposal pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="page-break-after: always;"></div>

    <!-- SECTION II: BEBAN KERJA REVIEWER -->
    <div class="section-header" style="margin-top: 0;">II. Distribusi Beban Kerja Reviewer</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 35%;">Nama Reviewer</th>
                <th style="width: 30%;">Fakultas / Instansi</th>
                <th style="width: 10%; text-align: center;">Total Ditugaskan</th>
                <th style="width: 10%; text-align: center;">Selesai (Completed)</th>
                <th style="width: 10%; text-align: center;">Tertunda (Pending)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reviewers as $index => $rev)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-bold">{{ $rev->name }}</td>
                    <td>{{ $rev->identity->faculty->name ?? '-' }}</td>
                    <td class="text-center font-bold">{{ $rev->total_assigned }}</td>
                    <td class="text-center" style="color: green;">{{ $rev->completed_count }}</td>
                    <td class="text-center" style="color: orange;">{{ $rev->pending_count }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 15px; color: #888; font-style: italic;">
                        Tidak ada data reviewer terdaftar.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- SIGNATURES -->
    <div class="signature-wrapper">
        <table class="signature-table">
            <tr>
                <td width="40%">
                    <div class="sign-block">
                        <div>Pekalongan, {{ $institutionalReport?->approved_at?->translatedFormat('d F Y') ?? now()->translatedFormat('d F Y') }}</div>
                        <div>Mengetahui,</div>
                        <div style="font-weight: bold; margin-bottom: 5px;">Rektor ITSNU Pekalongan</div>
                        
                        @if($institutionalReport && $institutionalReport->status->value === 'approved' && $institutionalReport->signature_path)
                            <div style="margin: 10px 0;">
                                <img src="{{ generate_qr_code_data_uri(URL::signedRoute('reports.verify', ['institutionalReport' => $institutionalReport->id]), 120) }}" width="70">
                            </div>
                            <div style="font-size: 7pt; color: #1a56db; font-weight: bold; margin-bottom: 5px;">DIGITALLY SIGNED</div>
                        @else
                            <div style="height: 70px;"></div>
                        @endif

                        <div class="sign-name">
                            {{ format_name($rektor?->identity?->title_prefix, $rektor?->name ?? 'Rektor', $rektor?->identity?->title_suffix) }}
                        </div>
                        <div class="sign-nip">NPP. {{ $rektor?->identity?->identity_id ?? '-' }}</div>
                    </div>
                </td>
                <td width="20%"></td>
                <td width="40%">
                    <div class="sign-block">
                        <div>Pekalongan, {{ $institutionalReport?->submitted_at?->translatedFormat('d F Y') ?? now()->translatedFormat('d F Y') }}</div>
                        <div>Dibuat oleh,</div>
                        <div style="font-weight: bold; margin-bottom: 5px;">Kepala LPPM ITSNU Pekalongan</div>

                        @if($institutionalReport && in_array($institutionalReport->status->value, ['submitted', 'approved']))
                            <div style="margin: 10px 0;">
                                <img src="{{ generate_qr_code_data_uri(URL::signedRoute('reports.verify', ['institutionalReport' => $institutionalReport->id]), 120) }}" width="70">
                            </div>
                            <div style="font-size: 7pt; color: #1a56db; font-weight: bold; margin-bottom: 5px;">DIGITALLY SIGNED</div>
                        @else
                            <div style="height: 70px;"></div>
                        @endif

                        <div class="sign-name">
                            {{ format_name($lppmHead?->identity?->title_prefix, $lppmHead?->name ?? 'Kepala LPPM', $lppmHead?->identity?->title_suffix) }}
                        </div>
                        <div class="sign-nip">NPP. {{ $lppmHead?->identity?->identity_id ?? '-' }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        SIM-LPPM ITSNU Pekalongan | Dicetak oleh: {{ auth()->user()->name ?? 'Administrator' }} | Halaman 2 dari 2
    </div>
</body>

</html>
