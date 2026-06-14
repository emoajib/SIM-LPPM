<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Surat Permohonan Izin - {{ $letter->letter_number ?? 'Draft' }}</title>
    @include('pdf.letters.partials.letter-styles')
</head>
<body>
    @include('pdf.letters.partials.letter-header')

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

    @include('pdf.letters.partials.letter-signature')

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
