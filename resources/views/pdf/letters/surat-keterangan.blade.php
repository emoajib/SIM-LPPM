<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Surat Keterangan - {{ $letter->letter_number ?? 'Draft' }}</title>
    @include('pdf.letters.partials.letter-styles')
</head>
<body>
    @include('pdf.letters.partials.letter-header')

    <div class="title">SURAT KETERANGAN</div>
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
        </table>

        <p>Dengan ini menerangkan bahwa:</p>

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

        <p>Telah melaksanakan kegiatan <strong>{{ $metadata['activity_type'] ?? 'Pengabdian kepada Masyarakat' }}</strong> tentang “{{ $metadata['title'] }}” pada:</p>

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

        <p>Demikian Surat Keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</p>
    </div>

    @include('pdf.letters.partials.letter-signature')
</body>
</html>
