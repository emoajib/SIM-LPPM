{{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
<!DOCTYPE html>
<html>

<head>
    <title>Laporan Penugasan & Reviewer</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7pt;
            line-height: 1.3;
            color: #000;
            text-align: left;
        }
        .kop-surat {
            border-bottom: 1.5pt solid #000;
            padding-bottom: 1px;
            margin-bottom: 3px;
            position: relative;
        }
        .kop-surat-inner {
            border-bottom: 0.5pt solid #000;
            padding-bottom: 3px;
        }
        .logo {
            width: {{ $pdfConfig['logo_size'] ?? 45 }}px;
        }
        .header-text {
            text-align: center;
        }
        .inst-name {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 1px;
        }
        .lppm-name {
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 1px;
        }
        .inst-address {
            font-size: 6.5pt;
            color: #333;
            line-height: 1.2;
        }
        .report-title-container {
            text-align: center;
            margin: 6px 0 4px 0;
        }
        .report-title {
            font-size: 9pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .report-subtitle {
            font-size: 7pt;
            margin-top: 2px;
        }
        .section-header {
            font-size: 8pt;
            font-weight: bold;
            margin-top: 10px;
            margin-bottom: 3px;
            text-transform: uppercase;
            border-bottom: 1px solid #ddd;
            padding-bottom: 1px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 3px 0 10px 0;
        }
        table.data-table th {
            background-color: #f2f2f2;
            border: 0.5pt solid #000;
            padding: 2px 3px;
            font-weight: bold;
            text-align: center;
            font-size: 6.5pt;
        }
        table.data-table td {
            border: 0.5pt solid #000;
            padding: 2px 3px;
            vertical-align: top;
            font-size: 6.5pt;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        /* Summary Metrics Table */
        table.metrics-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        table.metrics-table td {
            border: 0.5pt solid #ccc;
            padding: 4px;
            background-color: #f9f9f9;
            text-align: center;
            width: 25%;
        }
        .metric-value {
            font-size: 10pt;
            font-weight: bold;
            color: #206bc4;
        }
        .metric-label {
            font-size: 6pt;
            text-transform: uppercase;
            color: #666;
            margin-top: 1px;
        }

        .signature-wrapper {
            margin-top: 15px;
            page-break-inside: avoid;
        }
        table.signature-table {
            width: 100%;
            border: none;
        }
        table.signature-table td {
            border: none;
            padding: 0 10px;
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
            margin-top: 40px;
            font-size: 7pt;
        }
        .sign-nip {
            font-size: 6pt;
            margin-top: 2px;
        }
        .footer {
            position: fixed;
            bottom: -1.2cm;
            left: 0;
            right: 0;
            font-size: 6pt;
            text-align: center;
            color: #888;
        }
        
        /* Prevent table row break across pages */
        tr {
            page-break-inside: avoid;
        }
        
        /* Page break before section II */
        .page-break-before {
            page-break-before: always;
        }

        /* Attachment Header CSS */
        .header-table { width: 100%; border-bottom: 2px solid #000; margin-bottom: 15px; padding-bottom: 5px; border-collapse: collapse; }
        .header-table td { padding: 0; vertical-align: middle; }
        .header-text .univ { font-size: 14pt; font-weight: bold; margin: 0; text-transform: uppercase; }
        .header-text .dept { font-size: 12pt; font-weight: bold; margin: 0; text-transform: uppercase; }
        .header-text .address { font-size: 8pt; font-style: italic; margin: 0; }
        .header-text .contact { font-size: 8pt; margin: 0; }

        @include('reports.partials.report-base-styles')
    </style>
</head>

<body>
    <!-- KOP SURAT -->
    <div class="kop-surat">
        <div class="kop-surat-inner">
            @php $logoPos = $pdfConfig['logo_position'] ?? 'left'; $logoSrc = get_logo_base64(); @endphp
            @if(($pdfConfig['show_logo'] ?? true) && $logoSrc)
                @if($logoPos === 'center')
                    <img src="{{ $logoSrc }}" class="logo" style="display:block; margin:0 auto; width:{{ $pdfConfig['logo_size'] ?? 45 }}px;">
                @elseif($logoPos === 'right')
                    <img src="{{ $logoSrc }}" class="logo" style="position:absolute; right:0; top:0; left:auto; width:{{ $pdfConfig['logo_size'] ?? 45 }}px;">
                @else
                    <img src="{{ $logoSrc }}" class="logo" style="position:absolute; left:0; top:0; width:{{ $pdfConfig['logo_size'] ?? 45 }}px;">
                @endif
            @endif
            <div class="header-text" style="margin-{{ $logoPos === 'right' ? 'right' : 'left' }}: {{ $logoPos === 'center' ? '0' : (($pdfConfig['logo_size'] ?? 45) + 15) . 'px' }};">
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
        <div class="report-subtitle">Tahun Anggaran / Periode: <strong>{{ $period }}</strong>@if($semester && $semester !== 'all') | Semester: <strong>{{ ucfirst($semester) }}</strong>@endif</div>
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
        $requiredReviewers = (int) \App\Models\Setting::get('reviewer_count_required', 1);
        // Calculate column widths dynamically to sum to 100%
        // Fixed: No(3%) + Jenis(8%) + Rata-rata(7%) = 18%
        // Variable: Reviewer column (15%) + Score columns (6% each)
        $fixedWidth = 18; // No + Jenis + Rata-rata
        $scoreColumnWidth = 6; // each score column
        $reviewerColumnWidth = 15; // reviewer names column
        $titleWidth = 100 - $fixedWidth - $reviewerColumnWidth - ($requiredReviewers * $scoreColumnWidth);
    @endphp
    <div class="section-header">I. Rekapitulasi Penugasan & Hasil Penilaian Proposal</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th style="width: {{ $titleWidth }}%;">Judul Proposal & Pengaju</th>
                <th style="width: 8%;">Jenis</th>
                <th style="width: {{ $reviewerColumnWidth }}%;">Reviewer Ditugaskan</th>
                @for ($i = 0; $i < $requiredReviewers; $i++)
                    <th style="width: {{ $scoreColumnWidth }}%; text-align: center;">Skor Rev {{ $i + 1 }}</th>
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
                        <div style="color: #666; margin-top: 2px; font-size: 6pt;">Dosen: {{ format_name($proposal->submitter->identity?->title_prefix, $proposal->submitter->name, $proposal->submitter->identity?->title_suffix) }} ({{ $proposal->submitter->identity->faculty->name ?? '-' }})</div>
                    </td>
                    <td class="text-center">{{ $proposal->detailable_type === 'App\Models\Research' ? 'Penelitian' : 'PKM' }}</td>
                    <td>
                        @forelse($proposal->reviewers as $rev)
                            <div style="margin-bottom: 1px; font-size: 6pt;">• {{ format_name($rev->user->identity?->title_prefix, $rev->user->name, $rev->user->identity?->title_suffix) }}</div>
                        @empty
                            <div style="color: red; font-style: italic; font-size: 6pt;">Belum Diplot</div>
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
                <th style="width: 4%;">No</th>
                <th style="width: 28%;">Nama Reviewer</th>
                <th style="width: 28%;">Fakultas / Instansi</th>
                <th style="width: 12%; text-align: center;">Total Ditugaskan</th>
                <th style="width: 12%; text-align: center;">Selesai (Completed)</th>
                <th style="width: 12%; text-align: center;">Tertunda (Pending)</th>
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
                <td width="45%">
                    <div class="sign-block">
                        <div style="font-size: 6pt; margin-bottom: 3px;">Pekalongan, {{ $institutionalReport?->approved_at?->translatedFormat('d F Y') ?? now()->translatedFormat('d F Y') }}</div>
                        <div style="font-size: 6pt; margin-bottom: 2px;">Mengetahui,</div>
                        <div style="font-weight: bold; margin-bottom: 3px; font-size: 7pt;">Rektor ITSNU Pekalongan</div>
                        
                        @if($institutionalReport && $institutionalReport->status->value === 'approved')
                            @php
                                $rektorSig = $institutionalReport->signatures()
                                    ->where('variant', 'approved')
                                    ->where('action', 'approved')
                                    ->where('signed_role', 'rektor')
                                    ->first();
                                $qrRektorUrl = $rektorSig 
                                    ? URL::signedRoute('signatures.verify', ['documentSignature' => $rektorSig->id]) 
                                    : null;
                            @endphp
                            @if($qrRektorUrl)
                            <div class="digital-signature" style="display: inline-block; text-align: center; margin: 10px auto 5px;">
                                <img src="{{ generate_qr_code_data_uri($qrRektorUrl, 60) }}" alt="QR Code" style="width: 50px; height: 50px;">
                                <div style="font-size: 6pt; color: #1a56db; font-weight: bold; margin-top: 3px;">DIGITALLY SIGNED</div>
                            </div>
                            @endif
                        @else
                            <div style="margin-bottom: 45px;"></div>
                        @endif

                        <div class="sign-name" style="font-size: 8pt; margin-top: 5px;">
                            {{ format_name($rektor?->identity?->title_prefix, $rektor?->name ?? 'Rektor', $rektor?->identity?->title_suffix) }}
                        </div>
                        <div class="sign-nip" style="font-size: 7pt;">NIDN. {{ $rektor?->identity?->identity_id ?? '-' }}</div>
                    </div>
                </td>
                <td width="10%"></td>
                <td width="45%">
                    <div class="sign-block">
                        <div style="font-size: 6pt; margin-bottom: 3px;">Pekalongan, {{ $institutionalReport?->submitted_at?->translatedFormat('d F Y') ?? now()->translatedFormat('d F Y') }}</div>
                        <div style="font-size: 6pt; margin-bottom: 2px;">Dibuat oleh,</div>
                        <div style="font-weight: bold; margin-bottom: 3px; font-size: 7pt;">Kepala LPPM ITSNU Pekalongan</div>

                        @if($institutionalReport && in_array($institutionalReport->status->value, ['submitted', 'approved']))
                            @php
                                $lppmSig = $institutionalReport->signatures()
                                    ->where('variant', in_array($institutionalReport->status->value, ['approved']) ? 'approved' : 'submitted')
                                    ->where('action', 'submitted')
                                    ->where('signed_role', 'kepala_lppm')
                                    ->first();
                                $qrLppmUrl = $lppmSig 
                                    ? URL::signedRoute('signatures.verify', ['documentSignature' => $lppmSig->id]) 
                                    : null;
                            @endphp
                            @if($qrLppmUrl)
                            <div class="digital-signature" style="display: inline-block; text-align: center; margin: 10px auto 5px;">
                                <img src="{{ generate_qr_code_data_uri($qrLppmUrl, 60) }}" alt="QR Code" style="width: 50px; height: 50px;">
                                <div style="font-size: 6pt; color: #059669; font-weight: bold; margin-top: 3px;">VERIFIED BY LPPM</div>
                            </div>
                            @endif
                        @else
                            <div style="margin-bottom: 45px;"></div>
                        @endif

                        <div class="sign-name" style="font-size: 8pt; margin-top: 5px;">
                            {{ format_name($lppmHead?->identity?->title_prefix, $lppmHead?->name ?? 'Kepala LPPM', $lppmHead?->identity?->title_suffix) }}
                        </div>
                        <div class="sign-nip" style="font-size: 7pt;">NIDN. {{ $lppmHead?->identity?->identity_id ?? '-' }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        SIM-LPPM ITSNU Pekalongan | Dicetak oleh: {{ auth()->user()->name ?? 'Administrator' }} | {{ now()->format('d/m/Y H:i') }}
    </div>

    <!-- LAMPIRAN HASIL REVIEWER PER PROPOSAL -->
    @foreach($proposals as $proposal)
        @foreach($proposal->reviewers as $assignment)
            @if($assignment->isCompleted())
                @php
                    $signedAt = $assignment->completed_at ?? $assignment->updated_at ?? now();
                    $variant = 'round-'.((int) ($assignment->round ?? 1)).'-'.$signedAt->format('YmdHis');
                    
                    $signature = \App\Models\DocumentSignature::query()
                        ->where('document_type', $assignment->getMorphClass())
                        ->where('document_id', (string) $assignment->id)
                        ->where('variant', $variant)
                        ->where('action', 'reviewed')
                        ->where('signed_role', 'reviewer')
                        ->first();
                        
                    $qrUrl = $signature ? \Illuminate\Support\Facades\URL::signedRoute('signatures.verify', ['documentSignature' => $signature->id]) : null;
                    
                    $scores = $assignment->scores->where('round', $assignment->round);
                    $totalScore = $assignment->latestLog()->total_score ?? 0;
                    $type = $proposal->detailable_type === 'App\Models\Research' ? 'research' : 'community_service';
                @endphp
                <div style="page-break-before: always;"></div>
                @include('pdf.partials.review-evaluation-content', [
                    'assignment' => $assignment,
                    'proposal' => $proposal,
                    'type' => $type,
                    'scores' => $scores,
                    'totalScore' => $totalScore,
                    'qrUrl' => $qrUrl
                ])
            @endif
        @endforeach
    @endforeach
</body>

</html>
