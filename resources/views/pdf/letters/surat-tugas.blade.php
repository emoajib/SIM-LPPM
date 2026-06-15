<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Surat Tugas - {{ $letter->letter_number ?? 'Draft' }}</title>
    @include('pdf.partials.styles')
</head>
<body>
    @include('pdf.partials.header')

    <div class="title">SURAT TUGAS</div>
    <div class="number">Nomor: {{ $letter->letter_number ?? '...........................................' }}</div>

    <div class="bismillah">BISMILLAHIRRAHMANIRRAHIM</div>

    <div class="content">
        <p>{!! nl2br(e(\App\Models\Setting::get('pdf_content_surat-tugas_intro', 'Yang bertanda tangan di bawah ini:'))) !!}</p>
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
                    <th width="25%">NIDN/NUPTK/NIM</th>
                </tr>
            </thead>
            <tbody>
                @foreach($team as $index => $member)
                <tr>
                    <td align="center">{{ $index + 1 }}</td>
                    <td>{{ $member['name'] }}</td>
                    <td style="text-align: center;">{{ $member['role'] }}</td>
                    <td style="text-align: center;">{{ $member['identifier'] }}</td>
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

        <p>{!! nl2br(e(\App\Models\Setting::get('pdf_content_surat-tugas_outro', 'Demikian Surat Tugas ini dikeluarkan agar dapat digunakan sebagaimana mestinya.'))) !!}</p>
    </div>

    @include('pdf.letters.partials.letter-signature')

    <table class="travel-table">
        <tr>
            <td width="50%">
                Tiba di : …………………………<br>
                Pada hari & Tgl : …………………………<br>
                Koordinator<br>
                <div class="sig-spacer"></div>
                (………………………)<br>
                Nama : ……………………………………………
            </td>
            <td>
                Tiba kembali di : ITSNU Pekalongan<br>
                Pada hari & Tgl : ……………………<br>
                Pejabat yang berwenang ditunjuk<br>
                <div class="sig-spacer"></div>
                (………………………)<br>
                Nama : ……………………………………………
            </td>
        </tr>
    </table>
</body>
</html>
