<!DOCTYPE html>
<html>

<head>
    <title>Laporan Monitoring dan Evaluasi</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <style>
        @page {
            margin: 3cm 3cm 3cm 4cm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
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
            width: 65px;
        }
        .header-text {
            text-align: center;
            margin-left: 70px;
        }
        .inst-name {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .lppm-name {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .inst-address {
            font-size: 8pt;
            color: #333;
        }
        .report-title-container {
            text-align: center;
            margin: 15px 0;
        }
        .report-title {
            font-size: 11pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .report-subtitle {
            font-size: 9pt;
            margin-top: 5px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        table.data-table th {
            background-color: #f2f2f2;
            border: 0.5pt solid #000;
            padding: 5px;
            font-weight: bold;
            text-align: center;
            font-size: 8.5pt;
        }
        table.data-table td {
            border: 0.5pt solid #000;
            padding: 5px;
            vertical-align: top;
            font-size: 8.5pt;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .signature-wrapper {
            margin-top: 30px;
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
            margin-top: 60px;
        }
        .digital-signature {
            margin: 5px 0;
        }
        .digital-signature img {
            width: 60px;
        }
        .footer {
            position: fixed;
            bottom: -2cm;
            left: 0;
            right: 0;
            font-size: 7pt;
            text-align: center;
            color: #888;
        }
        @include('reports.partials.report-base-styles')
    </style>
</head>
<body>
    <div class="kop-surat">
        <div class="kop-surat-inner">
            @if($pdfConfig['show_logo'] ?? true)
                {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
                <img src="{{ get_logo_base64() }}" class="logo">
            @endif
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

    <div class="report-title-container">
        <div class="report-title">LAPORAN REKAPITULASI MONITORING DAN EVALUASI (MONEV)</div>
        <div class="report-subtitle">Tahun Akademik: <strong>{{ $period }}</strong> | Semester: <strong>{{ ucfirst($semester) }}</strong></div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 35%;">Judul Penelitian / Pengabdian</th>
                <th style="width: 20%;">Pengusul</th>
                <th style="width: 15%;">Reviewer</th>
                <th style="width: 10%;">Skor</th>
                <th style="width: 15%;">Status Akhir</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reviews as $index => $review)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="cell-title">{{ $review->proposal->title }}</td>
                    <td>
                        <div style="font-weight: bold;">{{ $review->proposal->submitter->name }}</div>
                        <div class="text-muted">NIDN: {{ $review->proposal->submitter->identity?->identity_id ?? '-' }}</div>
                    </td>
                    <td class="text-center">{{ $review->reviewer->name }}</td>
                    <td class="text-center fw-bold">{{ $review->score ?? '-' }}</td>
                    <td class="text-center">
                        @if($review->status)
                            <span class="status-ok">{{ $review->status }}</span>
                        @else
                            <span class="text-muted">Belum Dinilai</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 25px; color: #888; font-style: italic;">
                        Tidak ada data monev untuk periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature-wrapper">
        @php
            $rektorSig = $institutionalReport && $institutionalReport->status === \App\Enums\InstitutionalReportStatus::APPROVED
                ? $institutionalReport->signatures()->where('variant', 'approved')->where('action', 'approved')->where('signed_role', 'rektor')->first()
                : null;
            $lppmSig = $institutionalReport && in_array($institutionalReport->status->value, ['submitted', 'approved'])
                ? $institutionalReport->signatures()->where('variant', $institutionalReport->status->value === 'approved' ? 'approved' : 'submitted')->where('action', 'submitted')->where('signed_role', 'kepala_lppm')->first()
                : null;
        @endphp
        <table class="signature-table">
            <tr>
                <td width="33%">
                    <div class="sign-block">
                        <div>Pekalongan, {{ now()->translatedFormat('d F Y') }}</div>
                        <div>Mengetahui,</div>
                        <div style="font-weight: bold;">Rektor ITSNU Pekalongan</div>
                        @if($rektorSig)
                            <div class="digital-signature" style="display: inline-block; text-align: center; margin: 15px auto 5px;">
                                <img src="{{ generate_qr_code_data_uri(\Illuminate\Support\Facades\URL::signedRoute('signatures.verify', ['documentSignature' => $rektorSig->id]), 60) }}"
                                    alt="QR Code" style="width: 50px; height: 50px;">
                                <div class="signature-label" style="font-size: 7pt; margin-top: 3px;">DIGITALLY SIGNED</div>
                                <div class="text-muted" style="font-size: 6pt;">Ditandatangani: {{ $institutionalReport->approved_at?->translatedFormat('d F Y H:i') ?? '-' }}</div>
                            </div>
                        @else
                            <div style="margin-bottom: 60px;"></div>
                        @endif
                    <div class="sign-name">
                        {{ format_name($rektor?->identity?->title_prefix, $rektor?->name ?? 'Rektor', $rektor?->identity?->title_suffix) }}
                    </div>
                    <div class="sign-nip">NPP. {{ $rektor?->identity?->identity_id ?? '-' }}</div>
                </td>
                <td width="34%"></td>
                <td width="33%" class="text-center">
                    <div class="sign-date" style="margin-bottom: 4px;">Pekalongan,
                        {{ now()->translatedFormat('d F Y') }}
                    </div>
                    <div style="margin-bottom: 4px;">Dibuat oleh,</div>
                    <div style="margin-bottom: 4px; font-weight: bold;">Kepala LPPM ITSNU Pekalongan</div>
                        @if($lppmSig)
                            <div class="digital-signature" style="display: inline-block; text-align: center; margin: 15px auto 5px; border-color: #059669; color: #059669;">
                                <img src="{{ generate_qr_code_data_uri(\Illuminate\Support\Facades\URL::signedRoute('signatures.verify', ['documentSignature' => $lppmSig->id]), 60) }}"
                                    alt="QR Code" style="width: 50px; height: 50px;">
                                <div class="signature-label" style="font-size: 7pt; margin-top: 3px; color: #059669;">VERIFIED BY LPPM</div>
                                <div class="text-muted" style="font-size: 6pt;">Ditandatangani: {{ $institutionalReport->submitted_at?->translatedFormat('d F Y H:i') ?? '-' }}</div>
                            </div>
                        @else
                        <div style="margin-bottom: 60px;"></div>
                    @endif
                    <div class="sign-name">
                        {{ format_name($lppmHead?->identity?->title_prefix, $lppmHead?->name ?? 'Kepala LPPM', $lppmHead?->identity?->title_suffix) }}
                    </div>
                    <div class="sign-nip">NPP. {{ $lppmHead?->identity?->identity_id ?? '-' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Halaman 1 dari 1 | SIM-LPPM ITSNU Pekalongan | Dicetak oleh: {{ auth()->user()->name ?? 'Administrator' }}
    </div>
</body>

</html>
