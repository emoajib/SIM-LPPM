<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Surat Tugas - {{ $letter->letter_number ?? 'Draft' }}</title>
    <style>
        @page {
            margin: 0.5cm 2cm 0.5cm 2cm;
        }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 11pt;
            line-height: 1;
            color: #000;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #000;
            margin-bottom: 15px;
            padding-bottom: 5px;
        }
        .logo {
            width: 90px;
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
        .title {
            text-align: center;
            font-weight: bold;
            text-decoration: underline;
            margin-top: 20px;
            margin-bottom: 0;
            font-size: 12pt;
        }
        .number {
            text-align: center;
            margin-bottom: 20px;
        }
        .bismillah {
            text-align: center;
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 11pt;
        }
        .content {
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
        .signature-box {
            width: 50%;
            text-align: left;
        }
        .signature-box.right {
            text-align: left;
            padding-left: 10%;
        }
        .qr-code {
            margin: 10px 0;
        }
        .travel-table {
            width: 100%;
            border-top: 1px solid #000;
            margin-top: 40px;
            padding-top: 10px;
            font-size: 9pt;
        }
        .travel-table td {
            border: none;
            padding: 2px;
            vertical-align: top;
        }
        .line-dots {
            border-bottom: 1px dotted #000;
            display: inline-block;
            width: 150px;
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

    <div class="title">SURAT TUGAS</div>
    <div class="number">Nomor: {{ $letter->letter_number ?? '...........................................' }}</div>

    <div class="bismillah">BISMILLAHIRRAHMANIRRAHIM</div>

    <div class="content">
        <p>Yang bertanda tangan di bawah ini:</p>
        <table class="no-border" style="width: 100%; margin-left: 20px;">
            <tr>
                <td width="100px" style="border:none;">Nama</td>
                <td width="10px" style="border:none;">:</td>
                <td style="border:none;"><strong>{{ $metadata['signer_name'] ?? '' }}</strong></td>
            </tr>
            <tr>
                <td style="border:none;">Jabatan</td>
                <td style="border:none;">:</td>
                <td style="border:none;">{{ $metadata['signer_position'] ?? '' }}</td>
            </tr>
            <tr>
                <td style="border:none;">Alamat</td>
                <td style="border:none;">:</td>
                <td style="border:none;">{{ $metadata['signer_address'] ?? '' }}</td>
            </tr>
        </table>

        <p>Dengan ini memberikan tugas kepada perwakilan ITSNU Pekalongan dengan nama sebagai berikut:</p>

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

        <p>Untuk melakukan kegiatan <strong>{{ $metadata['activity_type'] ?? 'Pengabdian kepada Masyarakat' }}</strong> tentang “{{ $metadata['title'] }}” pada:</p>

        <table class="no-border" style="width: 100%; margin-left: 20px;">
            <tr>
                <td width="120px" style="border:none;">Hari, Tanggal</td>
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

        <p>Demikian Surat Tugas ini dikeluarkan agar dapat digunakan sebagaimana mestinya.</p>
    </div>

    <table class="signature-table">
        <tr>
            <td width="50%" style="border:none;"></td>
            <td class="signature-box right" style="border:none;">
                Ditetapkan di : Pekalongan<br>
                Pada tanggal : {{ $letter->published_at ? $letter->published_at->translatedFormat('d F Y') : '....................' }}
                <br><br>
                Kepala LPPM<br>
                ITSNU Pekalongan
                
                <div class="qr-code">
                    @if($letter->signature_mode === 'tte' && $letter->status === 'published')
                        <img src="{{ $qrDataUri }}" alt="QR Code" style="width: 80px; height: 80px;">
                    @else
                        <br><br><br><br><br>
                    @endif
                </div>

                <strong>{{ $metadata['signer_name'] ?? 'Aria Mulyapradana, S.Psi., M.A.' }}</strong><br>
                NIDN. {{ $metadata['signer_nidn'] ?? '' }}
            </td>
        </tr>
    </table>

    <table class="travel-table">
        <tr>
            <td width="50%">
                Tiba di : …………………………<br>
                Pada hari & Tgl : …………………………<br>
                Koordinator<br><br><br><br>
                (……………………………………………..)
            </td>
            <td>
                Tiba kembali di : ITSNU Pekalongan<br>
                Pada hari & Tgl : ……………………<br>
                Pejabat yang berwenang ditunjuk<br><br><br><br>
                (……………………………………………..)
            </td>
        </tr>
    </table>
</body>
</html>
