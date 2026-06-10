{{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Lembar Penilaian Reviewer - {{ $proposal->id }}</title>
    <style>
        {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
        @page {
            margin: 2cm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9.5pt;
            line-height: 1.4;
            color: #000;
            text-align: justify;
        }

        .header-table {
            width: 100%;
            border-bottom: 3.5pt double #000;
            margin-bottom: 15px;
            padding-bottom: 8px;
        }

        .header-table td {
            border: none !important;
            vertical-align: middle;
            padding: 0 !important;
        }

        .logo {
            width: 65px;
        }

        .header-text {
            text-align: center;
        }

        .no-border,
        .no-border td,
        .no-border th {
            border: none !important;
            padding: 2px !important;
        }

        .main-title {
            text-align: center;
            margin: 15px 0;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11pt;
            line-height: 1.3;
        }

        .info-table {
            width: 100%;
            margin-bottom: 15px;
        }

        .info-table.no-border td {
            padding: 3.5px 0 !important;
            vertical-align: top;
            border: none !important;
            font-size: 9.5pt;
        }

        .scoring-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .scoring-table th,
        .scoring-table td {
            border: 0.5pt solid #000;
            padding: 6px;
            font-size: 9pt;
            line-height: 1.3;
            vertical-align: top;
        }

        .scoring-table th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .text-justify {
            text-align: justify;
        }

        .fw-bold {
            font-weight: bold;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
    <table class="header-table no-border">
        <tr>
            <td style="width: 70px; text-align: left; vertical-align: middle; border: none !important; padding: 0 !important;">
                @if (get_logo_base64())
                    <img src="{{ get_logo_base64() }}" alt="Logo" style="width: 60px; height: auto;">
                @endif
            </td>
            <td class="header-text" style="border: none !important; padding: 0 !important; vertical-align: middle; text-align: center;">
                <div style="font-size: 11pt; font-weight: bold; text-transform: uppercase; line-height: 1.25; letter-spacing: 0.2px;">
                    Lembaga Penelitian dan Pengabdian kepada Masyarakat (LPPM)
                </div>
                <div style="font-size: 12.5pt; font-weight: bold; text-transform: uppercase; line-height: 1.25; margin-top: 2px; letter-spacing: 0.2px;">
                    Institut Teknologi dan Sains Nahdlatul Ulama
                </div>
                <div style="font-size: 12.5pt; font-weight: bold; text-transform: uppercase; line-height: 1.25; letter-spacing: 0.2px;">
                    (ITSNU) Pekalongan
                </div>
                <div style="font-size: 8pt; font-weight: normal; line-height: 1.3; margin-top: 6px; color: #111;">
                    Jl. Karangdowo No. 9, Karangdowo, Kec. Kedungwuni, Kab. Pekalongan, Jawa Tengah 51173
                </div>
                <div style="font-size: 8pt; font-weight: normal; line-height: 1.3; color: #111;">
                    Email: lppmitsnupkl@gmail.com | Website: https://lppm.itsnupekalongan.ac.id/
                </div>
            </td>
            <td style="width: 70px; border: none !important; padding: 0 !important; vertical-align: middle;"></td>
        </tr>
    </table>

    <div class="main-title">
        LEMBAR PENILAIAN PROPOSAL {{ $type === 'research' ? 'PENELITIAN' : 'PENGABDIAN MASYARAKAT' }}<br>
        TAHUN ANGGARAN {{ $proposal->start_year ?? date('Y') }}
    </div>

    <table class="info-table no-border">
        <tr>
            <td style="width: 25%;">Fakultas / Program Studi</td>
            <td style="width: 3%;">:</td>
            <td style="width: 72%;">{{ $proposal->submitter->identity?->faculty?->name ?? '-' }} /
                {{ $proposal->submitter->identity?->studyProgram?->name ?? '-' }}
            </td>
        </tr>
        <tr>
            <td>Judul {{ $type === 'research' ? 'Penelitian' : 'PkM' }}</td>
            <td>:</td>
            <td><strong>{{ clean_proposal_title($proposal->title) }}</strong></td>
        </tr>
        <tr>
            <td>Ketua {{ $type === 'research' ? 'Peneliti' : 'PkM' }}</td>
            <td>:</td>
            <td>{{ format_name($proposal->submitter->identity?->title_prefix, $proposal->submitter->name, $proposal->submitter->identity?->title_suffix) }}</td>
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
                <th style="width: 5%; padding: 6px 4px;">No</th>
                <th style="width: 25%; padding: 6px 4px;">Kriteria Penilaian</th>
                <th style="width: 45%; padding: 6px 4px;">Catatan / Justifikasi Reviewer</th>
                <th style="width: 10%; padding: 6px 4px;">Bobot (%)</th>
                <th style="width: 7%; padding: 6px 4px;">Skor</th>
                <th style="width: 8%; padding: 6px 4px;">Nilai</th>
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

    <div style="font-size: 8.5pt; margin-top: 15px; margin-bottom: 15px; border: 0.5pt solid #000; padding: 8px 12px; background-color: #fafafa; line-height: 1.4;">
        <strong>Keterangan Skor:</strong> 1 = Sangat Kurang, 2 = Kurang, 3 = Cukup Baik, 4 = Baik, 5 = Sangat Baik. <br>
        <strong>Passing Grade:</strong> 300 &nbsp;|&nbsp; 
        <strong>Total Nilai:</strong> {{ number_format($totalScore, 0) }} &nbsp;|&nbsp; 
        <strong>Rekomendasi:</strong>
        <span class="fw-bold" style="color: {{ $assignment->recommendation === 'approved' ? '#1a4d2e' : ($assignment->recommendation === 'rejected' ? '#7f1d1d' : '#854d0e') }}">
            {{ strtoupper($assignment->recommendation === 'approved' ? 'DITERIMA' : ($assignment->recommendation === 'rejected' ? 'DITOLAK' : 'PERLU REVISI')) }}
        </span>
    </div>

    <div class="fw-bold" style="font-size: 9.5pt; margin-bottom: 5px;">Komentar / Saran Reviewer:</div>
    <div style="border: 0.5pt solid #000; padding: 10px; min-height: 80px; margin-bottom: 20px; text-align: justify; font-size: 9pt; line-height: 1.4; background-color: #ffffff;">
        {!! nl2br(e($assignment->review_notes)) !!}
    </div>

    <table class="no-border" style="width: 100%; margin-top: 25px; page-break-inside: avoid;">
        <tr>
            <td style="width: 60%; border: none !important;"></td>
            <td style="width: 40%; border: none !important; text-align: left; vertical-align: top; padding: 0 !important;">
                <p style="margin: 0; padding: 1.5px 0;">Pekalongan, {{ $assignment->completed_at?->translatedFormat('d F Y') ?? now()->translatedFormat('d F Y') }}</p>
                <p style="margin: 0; padding: 1.5px 0;">Reviewer,</p>
                @if($qrUrl ?? null)
                    <div style="margin: 6px 0; margin-left: 10px;">
                        <img src="{{ generate_qr_code_data_uri($qrUrl, 160) }}" alt="QR Verifikasi" style="width: 65px; height: 65px;">
                    </div>
                    <div style="font-size: 7.5pt; color: #333; margin-top: 1px; margin-bottom: 8px; line-height: 1.2;">
                        Terverifikasi sistem (QR)<br>
                        Ditandatangani: {{ $assignment->completed_at?->format('d/m/Y H:i') ?? '-' }}
                    </div>
                @else
                    <div style="height: 50px;"></div>
                @endif
                <p style="margin: 0; padding: 1.5px 0; margin-top: 5px;"><strong>({{ format_name($assignment->user->identity?->title_prefix, $assignment->user->name, $assignment->user->identity?->title_suffix) }})</strong></p>
                <p style="margin: 0; padding: 1.5px 0;">NIDN. {{ $assignment->user->identity?->identity_id ?? '..........................' }}</p>
            </td>
        </tr>
    </table>
</body>

</html>
