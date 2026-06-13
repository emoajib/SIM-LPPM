<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Surat Permohonan Izin - {{ $letter->letter_number ?? 'Draft' }}</title>
    <style>
        @page {
            margin: 2cm 2cm 2cm 2.5cm;
        }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #000;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #000;
            margin-bottom: 15px;
            padding-bottom: 5px;
        }
        .logo {
            width: 80px;
        }
        .header-text {
            text-align: center;
        }
        .header-text .univ {
            font-size: 14pt;
            font-weight: bold;
        }
        .header-text .dept {
            font-size: 13pt;
            font-weight: bold;
        }
        .header-text .address {
            font-size: 9pt;
            font-style: italic;
        }
        .header-text .contact {
            font-size: 9pt;
        }
        .meta-table {
            width: 100%;
            margin-top: 10px;
        }
        .meta-table td {
            border: none;
            padding: 1px;
            vertical-align: top;
        }
        .content {
            margin-top: 20px;
            text-align: justify;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .table-data th, .table-data td {
            border: 1px solid #000;
            padding: 5px 8px;
            text-align: left;
            font-size: 10.5pt;
        }
        .table-data th {
            text-align: center;
            background-color: #f2f2f2;
        }
        .signature-table {
            width: 100%;
            margin-top: 30px;
        }
        .signature-box.right {
            text-align: left;
            padding-left: 10%;
        }
        .qr-code {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td width="15%" style="border:none;">
                <img src="{{ public_path('logo.png') }}" class="logo">
            </td>
            <td width="85%" class="header-text" style="border:none;">
                <div class="dept">LEMBAGA PENELITIAN DAN PENGABDIAN MASYARAKAT</div>
                <div class="univ">ITSNU PEKALONGAN</div>
                <div class="address">Jl. Karangdowo No. 9 Kedungwuni Kab. Pekalongan Kode Pos 51173</div>
                <div class="contact">Telp/Fax. (0285) 7831614 email: lppmitsnupkl@gmail.com #SantriHighTech</div>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td width="10%">Nomor</td>
            <td width="2%">:</td>
            <td width="48%">{{ $letter->letter_number ?? '...........................................' }}</td>
            <td width="40%" align="right">{{ $letter->published_at ? $letter->published_at->translatedFormat('d F Y') : date('d F Y') }}</td>
        </tr>
        <tr>
            <td>Lampiran</td>
            <td>:</td>
            <td>{{ $metadata['lampiran'] ?? '-' }}</td>
            <td></td>
        </tr>
        <tr>
            <td>Perihal</td>
            <td>:</td>
            <td><strong>{{ $metadata['perihal'] ?? 'Permohonan Izin Pengabdian kepada Masyarakat' }}</strong></td>
            <td></td>
        </tr>
    </table>

    <div class="content">
        <p>Kepada Yth.<br>
        <strong>{{ $metadata['destination_name'] ?? 'Pimpinan Mitra' }}</strong><br>
        di tempat</p>

        <p>Assalamu’alaikum Wr.Wb.</p>

        <p>Puji dan syukur kita panjatkan kehadirat Allah SWT, dan sholawat serta salam semoga tetap tercurah pada junjungan Nabi Agung Muhammad SAW, sahabat dan para pengikutnya aamiin.</p>

        <p>Dalam rangka melaksanakan Tridharma Perguruan Tinggi yang berupa {{ $metadata['activity_type'] ?? 'Pengabdian kepada Masyarakat' }} maka kami dari ITSNU Pekalongan bermaksud melaksanakan kegiatan tersebut dengan tema <strong>“{{ $metadata['title'] }}”</strong> di tempat yang Bapak/Ibu pimpin, dengan nama personil sebagai berikut:</p>

        <table class="table-data">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Nama</th>
                    <th width="20%">Jabatan</th>
                    <th width="25%">NIDN/NIM</th>
                </tr>
            </thead>
            <tbody>
                @foreach($team as $index => $member)
                <tr>
                    <td align="center">{{ $index + 1 }}</td>
                    <td>{{ $member['name'] }}</td>
                    <td>{{ $member['role'] }}</td>
                    <td>{{ $member['identifier'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <p>Adapun kegiatan {{ $metadata['activity_type'] ?? 'Pengabdian kepada Masyarakat' }} ini insyaAllah akan kami laksanakan pada:</p>

        <table class="no-border" style="width: 100%; margin-left: 20px;">
            <tr>
                <td width="120px" style="border:none;">Hari/Tanggal</td>
                <td width="10px" style="border:none;">:</td>
                <td style="border:none;">{{ $metadata['date_string'] }}</td>
            </tr>
            <tr>
                <td style="border:none;">Waktu</td>
                <td style="border:none;">:</td>
                <td style="border:none;">{{ $metadata['time_string'] }}</td>
            </tr>
            <tr>
                <td style="border:none;">Tempat</td>
                <td style="border:none;">:</td>
                <td style="border:none;">{{ $metadata['location'] }}</td>
            </tr>
        </table>

        <p>Demikian surat permohonan ini kami sampaikan dan atas perhatian Bapak/Ibu kami ucapkan terima kasih.</p>

        <p>Wassalamu’alaikum Wr.Wb.</p>
    </div>

    <table class="signature-table">
        <tr>
            <td width="50%" style="border:none;"></td>
            <td class="signature-box right" style="border:none;">
                Kepala LPPM<br>
                ITSNU Pekalongan
                
                <div class="qr-code">
                    @if($letter->signature_mode === 'tte' && $letter->status === 'published')
                        {{-- In real implementation, this would be a QR code image --}}
                        <div style="border: 1px solid #ccc; width: 80px; height: 80px; text-align: center; line-height: 80px; font-size: 8pt;">QR CODE</div>
                    @else
                        <br><br><br>
                    @endif
                </div>

                <strong>{{ $metadata['signer_name'] ?? 'Aria Mulyapradana, S.Psi., M.A.' }}</strong><br>
                NIDN. {{ $metadata['signer_nidn'] ?? '0612118401' }}
            </td>
        </tr>
    </table>

    <div style="margin-top: 20px; font-size: 9pt;">
        Tembusan:<br>
        @if(isset($metadata['tembusan']) && is_array($metadata['tembusan']))
            @foreach($metadata['tembusan'] as $index => $item)
                {{ $index + 1 }}. {{ $item }}<br>
            @endforeach
        @else
            1. Arsip
        @endif
    </div>
</body>
</html>
