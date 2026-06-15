<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Capaian IKU - {{ $period }}</title>
        <style>
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
            width: {{ $pdfConfig['logo_size'] ?? 65 }}px;
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
    @include('reports.partials.kop-surat')

    <div class="report-title-container">
        <div class="report-title">LAPORAN CAPAIAN INDIKATOR KINERJA UTAMA (IKU)</div>
        <div class="report-subtitle">Tahun Anggaran: <strong>{{ $period }}</strong></div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 15%;">Kode IKU</th>
                <th style="width: 40%;">Indikator Kinerja</th>
                <th style="width: 15%;">Target</th>
                <th style="width: 15%;">Capaian</th>
                <th style="width: 10%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @foreach($ikuMetrics as $key => $metric)
                <tr>
                    <td class="text-center">{{ $no++ }}</td>
                    <td class="text-center font-bold">{{ strtoupper($key) }}</td>
                    <td>
                        <div class="font-bold">{{ $metric['name'] }}</div>
                        <div style="font-size: 10px; color: #666;">{{ $metric['description'] }}</div>
                    </td>
                    <td class="text-center">{{ $metric['target'] }}%</td>
                    <td class="text-center font-bold">{{ round($metric['achievement'], 2) }}%</td>
                    <td class="text-center">
                        @if($metric['achievement'] >= $metric['target'])
                            <span style="color: green; font-weight: bold;">TERCAPAI</span>
                        @else
                            <span style="color: red; font-weight: bold;">PENDING</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 12px; padding: 8px 10px; border-left: 3pt solid #1a4a8e; background: #f8f9ff; font-size: 9pt;">
        <div style="font-weight: bold; color: #1a4a8e; margin-bottom: 3px;">Keterangan:</div>
        <div style="color: #333; line-height: 1.4;">
            Laporan ini disusun berdasarkan Keputusan Menteri Pendidikan, Kebudayaan, Riset, dan Teknologi
            Nomor 358/M/KEP/2025. Data diambil secara otomatis dari sistem informasi LPPM ITSNU Pekalongan.
        </div>
    </div>

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
                        <div class="sign-name" style="font-size: 8pt; margin-top: 5px;">
                            {{ format_name($rektor?->identity?->title_prefix, $rektor?->name ?? 'Rektor', $rektor?->identity?->title_suffix) }}
                        </div>
                        <div class="sign-nip" style="font-size: 7pt;">NPP. {{ $rektor?->identity?->identity_id ?? '-' }}</div>
                    </div>
                </td>
                <td width="34%"></td>
                <td width="33%">
                    <div class="sign-block">
                        <div>Pekalongan, {{ now()->translatedFormat('d F Y') }}</div>
                        <div>Dibuat oleh,</div>
                        <div style="font-weight: bold;">Kepala LPPM ITSNU Pekalongan</div>
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
                        <div class="sign-name" style="font-size: 8pt; margin-top: 5px;">
                            {{ format_name($lppmHead?->identity?->title_prefix, $lppmHead?->name ?? 'Kepala LPPM', $lppmHead?->identity?->title_suffix) }}
                        </div>
                        <div class="sign-nip" style="font-size: 7pt;">NPP. {{ $lppmHead?->identity?->identity_id ?? '-' }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Dicetak otomatis oleh SIM LPPM ITSNU Pekalongan pada {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>

</html>
