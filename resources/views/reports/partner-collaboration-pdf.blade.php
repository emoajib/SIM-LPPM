<!DOCTYPE html>
<html>

<head>
    <title>Laporan Kerjasama Mitra</title>
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
            width: 65px;
        }
        .header-text {
            text-align: center;
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
            @php $logoPos = $pdfConfig['logo_position'] ?? 'left'; $logoSrc = get_logo_base64(); @endphp
            @if(($pdfConfig['show_logo'] ?? true) && $logoSrc)
                @if($logoPos === 'center')
                    <img src="{{ $logoSrc }}" class="logo" style="display:block; margin:0 auto; width:65px;">
                @elseif($logoPos === 'right')
                    <img src="{{ $logoSrc }}" class="logo" style="position:absolute; right:0; top:0; left:auto; width:65px;">
                @else
                    <img src="{{ $logoSrc }}" class="logo" style="position:absolute; left:0; top:0; width:65px;">
                @endif
            @endif
            <div class="header-text" style="margin-{{ $logoPos === 'right' ? 'right' : 'left' }}: {{ $logoPos === 'center' ? '0' : '70px' }};">
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
        <div class="report-title">LAPORAN KERJASAMA MITRA</div>
        <div class="report-subtitle">
            Periode: <strong>{{ $periodFilter ?: 'Semua Tahun' }}</strong>
            @if($typeFilter) | Jenis Mitra: <strong>{{ $typeFilter }}</strong> @endif
        </div>
    </div>

    <div class="summary-box">
        <div class="summary-title">Ringkasan Laporan:</div>
        <div style="font-size: 9pt;">
            Total Mitra yang tercatat dalam laporan ini adalah <strong>{{ count($partners) }}</strong>
            institusi/individu partner.
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 24%;">Nama Mitra &amp; Institusi</th>
                <th style="width: 12%;">Jenis Mitra</th>
                <th style="width: 18%;">Kontak / Email</th>
                <th style="width: 10%;">Jml. Usulan</th>
                <th style="width: 10%;">Disetujui</th>
                <th style="width: 14%;">Total Dana (Rp)</th>
                <th style="width: 8%;">Dok MOU</th>
            </tr>
        </thead>
        <tbody>
            @forelse($partners as $index => $partner)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <div class="fw-bold">{{ $partner->name }}</div>
                        <div class="text-muted">{{ $partner->institution }}</div>
                    </td>
                    <td class="text-center">{{ $partner->type }}</td>
                    <td class="text-center">{{ $partner->email ?: '-' }}</td>
                    <td class="text-center">{{ $partner->proposals_count }}</td>
                    <td class="text-center fw-bold">{{ $partner->approved_count }}</td>
                    <td class="text-right fw-bold">
                        Rp {{ number_format($partner->total_budget ?? 0, 0, ',', '.') }}
                    </td>
                    <td class="text-center">
                        @if($partner->hasMedia('mou_pks'))
                            <span style="color: green; font-weight: bold;">✔</span>
                        @else
                            <span style="color: #ccc;">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 30px; color: #999;">
                        Tidak ada data mitra ditemukan.
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
                <td width="33%" class="text-center">
                    <div class="sign-date" style="margin-bottom: 4px;">Pekalongan,
                        {{ now()->translatedFormat('d F Y') }}
                    </div>
                    <div style="margin-bottom: 4px;">Mengetahui,</div>
                    <div style="margin-bottom: 4px; font-weight: bold;">Rektor ITSNU Pekalongan</div>
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
        SIM-LPPM ITSNU Pekalongan | Dicetak oleh: {{ auth()->user()->name ?? 'Administrator' }} pada
        {{ now()->format('d/m/Y H:i') }}
    </div>
</body>

</html>
