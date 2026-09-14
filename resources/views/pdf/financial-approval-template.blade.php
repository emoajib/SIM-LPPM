{{--
    Template: Lembar Pengesahan Laporan Keuangan (LPJ) Khusus TTD Basah
    Vetted by AI - Manual Review Required by Senior Engineer/Manager
--}}
<!DOCTYPE html>
<html>
@php
    if (!isset($submitterFullName)) {
        $submitterIdentity = $proposal->submitter->identity ?? null;
        $submitterFullName = format_name(
            $submitterIdentity?->title_prefix ?? '',
            $proposal->submitter->name,
            $submitterIdentity?->title_suffix ?? ''
        );
    }
    $totalRealized = (float) $proposal->dailyNotes->sum('amount');
    $pdfConfig ??= get_pdf_config('letter', 'logbook');
    $lineHeight = $pdfConfig['line_height'] ?? 1.5;
@endphp
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Lembar Pengesahan LPJ - {{ $proposal->id }}</title>
    @include('pdf.partials.styles')
    <style>
        @page {
            margin-top: 20mm;
            margin-bottom: 25mm;
            margin-left: 25mm;
            margin-right: 25mm;
        }
        body {
            font-family: 'times-roman', Times, serif;
            font-size: 11pt;
            line-height: {{ $lineHeight }};
            color: #000;
        }
        .document-title {
            text-align: center;
            margin-top: 20px;
            margin-bottom: 30px;
            font-weight: bold;
            font-size: 13pt;
            text-transform: uppercase;
            text-decoration: underline;
        }
        table.no-border {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.no-border td {
            border: none;
            padding: 6px 4px;
            font-size: 11pt;
            vertical-align: top;
        }
    </style>
</head>
<body>
    @include('pdf.partials.header')

    <div class="document-title">
        HALAMAN PENGESAHAN LAPORAN KEUANGAN (LPJ)
    </div>

    <table class="no-border" style="margin-bottom: 30px;">
        <tr>
            <td style="width: 28%;">Judul Program</td>
            <td style="width: 2%;">:</td>
            <td style="font-weight: bold;">{{ clean_proposal_title($proposal->title) }}</td>
        </tr>
        <tr>
            <td>Nomor Kontrak</td>
            <td>:</td>
            <td style="font-weight: bold;">{{ $proposal->contract_number ?? '-' }}</td>
        </tr>
        <tr>
            <td>Ketua Pelaksana</td>
            <td>:</td>
            <td>{{ $submitterFullName }} (NIDN: {{ $proposal->submitter->identity?->identity_id ?? '-' }})</td>
        </tr>
        <tr>
            <td>Total Realisasi Biaya</td>
            <td>:</td>
            <td style="font-weight: bold;">Rp {{ number_format($totalRealized, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div style="margin-top: 40px; page-break-inside: avoid;">
        <table class="no-border" style="width: 100%;">
            <tr>
                <td width="50%" class="text-center" style="vertical-align: top; border: none;">
                    Menyetujui,<br>
                    Kepala LPPM ITSNU Pekalongan
                </td>
                <td width="50%" class="text-center" style="vertical-align: top; border: none;">
                    Pekalongan, {{ now()->format('d F Y') }}<br>
                    Ketua {{ $proposal->detailable_type === 'App\Models\Research' ? 'Peneliti' : 'Pelaksana' }}
                </td>
            </tr>
            <tr>
                <td class="text-center" style="height: 110px; vertical-align: bottom; border: none; padding-bottom: 5px;">
                    <div style="height: 75px;"></div>
                    @php $kepala = \App\Models\User::role('kepala lppm')->first(); @endphp
                    <strong><u>{{ $lppmHeadName ?? ($kepala->name ?? '.......................') }}</u></strong><br>
                    NIDN. {{ $lppmHeadId ?? ($kepala->identity?->identity_id ?? '-') }}
                </td>
                <td class="text-center" style="height: 110px; vertical-align: bottom; border: none; padding-bottom: 5px;">
                    <div style="height: 75px;"></div>
                    <strong><u>{{ $submitterFullName }}</u></strong><br>
                    NIDN. {{ $proposal->submitter->identity?->identity_id ?? '-' }}
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 50px; font-size: 8.5pt; color: #555; border-top: 0.5pt dashed #999; padding-top: 8px;">
        <em>Catatan: Lembar pengesahan ini dicetak khusus untuk pembubuhan tanda tangan & cap basah. Setelah ditandatangani dan dicap, mohon scan dalam bentuk file PDF dan unggah kembali pada sistem SIM LPPM.</em>
    </div>
</body>
</html>
