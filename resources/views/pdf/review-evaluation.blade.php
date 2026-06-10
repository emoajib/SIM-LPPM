<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Lembar Penilaian Reviewer - {{ $proposal->id }}</title>
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            border: 0;
        }

        @page {
            margin: 3cm 3cm 3cm 4cm;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000;
            text-align: justify;
        }

        .header-table {
            width: 100%;
            border-bottom: 2px solid #000;
            margin-bottom: 5px;
            padding-bottom: 5px;
        }

        .logo {
            width: 60px;
        }

        .header-text {
            text-align: left;
            padding-left: 10px;
        }

        .header-text div {
            font-weight: bold;
            font-size: 11pt;
        }

        .no-border,
        .no-border td,
        .no-border th {
            border: none !important;
        }

        .main-title {
            text-align: center;
            margin: 15px 0;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            font-size: 12pt;
        }

        .info-table {
            width: 100%;
            margin-bottom: 15px;
        }

        .info-table td {
            padding: 1px 0;
            vertical-align: top;
            border: none !important;
        }

        .info-table td:first-child {
            width: 180px;
        }

        .info-table td:nth-child(2) {
            width: 10px;
        }

        .scoring-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .scoring-table th,
        .scoring-table td {
            border: 1px solid #000;
            padding: 3px 5px;
            font-size: 11pt;
            line-height: 1.5;
        }

        .scoring-table th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .fw-bold {
            font-weight: bold;
        }

        .footer {
            margin-top: 20px;
            width: 100%;
            page-break-inside: avoid;
        }

        .signature-box {
            float: right;
            width: 250px;
            text-align: left;
        }

        .signature-space {
            height: 50px;
        }

        .page-break {
            page-break-after: always;
        }

    </style>
</head>

<body>
    <table class="header-table no-border">
        <tr>
            <td class="logo" style="width: 60px;">
                @if (file_exists(public_path('logo.png')))
                    <img src="{{ public_path('logo.png') }}" alt="Logo" style="width: 50px;">
                @endif
            </td>
            <td class="header-text">
                <div>Lembaga Penelitian dan Pengabdian kepada Masyarakat (LPPM)</div>
                <div>Institut Teknologi dan Sains Nahdlatul Ulama (ITSNU) Pekalongan</div>
                <div style="font-weight: normal; font-size: 8pt;">Jl. Karangdowo No. 9, Karangdowo, Kec. Kedungwuni,
                    Kab. Pekalongan, Jawa Tengah 51173</div>
                <div style="font-weight: normal; font-size: 8pt;">Email: lppmitsnupkl@gmail.com | Website:
                    https://lppm.itsnupekalongan.ac.id/</div>
            </td>
        </tr>
    </table>

    <div class="main-title">
        LEMBAR PENILAIAN PROPOSAL {{ $type === 'research' ? 'PENELITIAN' : 'PENGABDIAN MASYARAKAT' }}<br>
        TAHUN ANGGARAN {{ date('Y') }}
    </div>

    <table class="info-table no-border">
        <tr>
            <td>Fakultas / Program Studi</td>
            <td>:</td>
            <td>{{ $proposal->submitter->identity?->faculty?->name ?? '-' }} /
                {{ $proposal->submitter->identity?->studyProgram?->name ?? '-' }}
            </td>
        </tr>
        <tr>
            <td>Judul {{ $type === 'research' ? 'Penelitian' : 'PkM' }}</td>
            <td>:</td>
            <td><strong>{{ $proposal->title }}</strong></td>
        </tr>
        <tr>
            <td>Ketua {{ $type === 'research' ? 'Peneliti' : 'PkM' }}</td>
            <td>:</td>
            <td>{{ $proposal->submitter->name }}</td>
        </tr>
        <tr>
            <td>Jumlah Anggota</td>
            <td>:</td>
            <td>{{ $proposal->teamMembers->count() }} Orang</td>
        </tr>
        <tr>
            <td>Jangka Waktu</td>
            <td>:</td>
            <td>{{ $proposal->duration_in_years }} Tahun</td>
        </tr>
        <tr>
            <td>Biaya Usulan</td>
            <td>:</td>
            <td>
                @php
                    $dana = ($proposal->sbk_value && $proposal->sbk_value > 0)
                        ? $proposal->sbk_value
                        : ($proposal->budgetItems->sum('total_price') ?? 0);
                @endphp
                Rp {{ number_format($dana, 0, ',', '.') }}
            </td>
        </tr>
    </table>

    <table class="scoring-table">
        <thead>
            <tr>
                <th width="25">No</th>
                <th width="100">Kriteria</th>
                <th>Catatan / Justifikasi Reviewer</th>
                <th width="50">Bobot (%)</th>
                <th width="40">Skor</th>
                <th width="70">Nilai</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($scores as $index => $score)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-justify" style="line-height: 1.4;">{{ $score->criteria->criteria }}</td>
                    <td style="text-align: justify;">{{ $score->acuan }}</td>
                    <td class="text-center">{{ number_format($score->weight_snapshot, 0) }}%</td>
                    <td class="text-center">{{ $score->score }}</td>
                    <td class="text-right fw-bold">{{ number_format($score->value, 0) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="fw-bold" style="background-color: #f2f2f2;">
                <td colspan="3" class="text-right">TOTAL NILAI</td>
                <td class="text-center">{{ number_format($scores->sum('weight_snapshot'), 0) }}%</td>
                <td class="text-center">{{ $scores->sum('score') }}</td>
                <td class="text-right">{{ number_format($totalScore, 0) }}</td>
            </tr>
        </tfoot>
    </table>

    <div style="font-size: 8pt; margin-bottom: 10px; border: 1px solid #000; padding: 4px;">
        <strong>Keterangan Skor:</strong> 1=Sangat Kurang, 2=Kurang, 3=Cukup Baik, 4=Baik, 5=Sangat Baik.
        <strong>Passing Grade:</strong> 300.
        <br><strong>Rekomendasi:</strong>
        {{ strtoupper($assignment->recommendation === 'approved' ? 'DITERIMA' : ($assignment->recommendation === 'rejected' ? 'DITOLAK' : 'PERLU REVISI')) }}
    </div>

    <div class="fw-bold" style="margin-bottom: 3px;">Komentar / Saran Reviewer:</div>
    <div
        style="border: 1px solid #000; padding: 5px 10px; min-height: 80px; margin-bottom: 15px; text-align: justify; font-size: 8pt;">
        {{ $assignment->review_notes }}
    </div>

    <div class="footer">
        <div class="signature-box">
            <p>Pekalongan, {{ $assignment->completed_at?->translatedFormat('d F Y') ?? now()->translatedFormat('d F Y') }}</p>
            <p>Reviewer,</p>
            @if($qrUrl ?? null)
                <div style="margin: 6px 0;">
                    <img src="{{ generate_qr_code_data_uri($qrUrl, 160) }}" alt="QR Verifikasi" style="width: 70px; height: 70px;">
                </div>
                <div style="font-size: 8pt; color: #333; margin-top: 2px; margin-bottom: 10px;">
                    Terverifikasi sistem (QR)<br>
                    Ditandatangani: {{ $assignment->completed_at?->format('d/m/Y H:i') ?? '-' }}
                </div>
            @else
                <div class="signature-space"></div>
            @endif
            <p><strong>({{ format_name($assignment->user->identity?->title_prefix, $assignment->user->name, $assignment->user->identity?->title_suffix) }})</strong></p>
            <p>NIDN. {{ $assignment->user->identity?->identity_id ?? '..........................' }}</p>
        </div>
    </div>
</body>

</html>
