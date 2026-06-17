<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pratinjau PDF</title>
    @include('pdf.partials.styles')
    <style>
        .preview-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .preview-table th, .preview-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        .preview-table th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    @php
        $logoSrc = get_logo_base64();
        $logoPos = \App\Models\Setting::get('pdf_logo_position', 'left');
        $inst = get_institution_config();
    @endphp
    @if($logoPos === 'center')
    <div class="header" style="text-align:center;">
        @if(\App\Models\Setting::get('pdf_show_logo', true) && $logoSrc)
            <img src="{{ $logoSrc }}" class="logo" style="display:block; margin:0 auto;">
        @endif
        <div class="header-text" style="text-align:center;">
            <h2>{{ strtoupper($inst['lppm_name']) }}</h2>
            <h1>{{ strtoupper($inst['full_name']) }}</h1>
            <p>{{ $inst['address'] }}</p>
            <p>Telepon: {{ $inst['phone'] }}, Email: {{ $inst['email'] }}, Website: {{ $inst['website_main'] }}</p>
        </div>
    </div>
    @elseif($logoPos === 'right')
    <div class="header" style="text-align:right;">
        <div class="header-text" style="text-align:center;">
            <h2>{{ strtoupper($inst['lppm_name']) }}</h2>
            <h1>{{ strtoupper($inst['full_name']) }}</h1>
            <p>{{ $inst['address'] }}</p>
            <p>Telepon: {{ $inst['phone'] }}, Email: {{ $inst['email'] }}, Website: {{ $inst['website_main'] }}</p>
        </div>
        @if(\App\Models\Setting::get('pdf_show_logo', true) && $logoSrc)
            <img src="{{ $logoSrc }}" class="logo" style="margin-left:auto;">
        @endif
    </div>
    @else
    <div class="header">
        @if(\App\Models\Setting::get('pdf_show_logo', true) && $logoSrc)
            <img src="{{ $logoSrc }}" class="logo" />
        @endif
        <div class="header-text">
            <h2>{{ strtoupper($inst['lppm_name']) }}</h2>
            <h1>{{ strtoupper($inst['full_name']) }}</h1>
            <p>{{ $inst['address'] }}</p>
            <p>Telepon: {{ $inst['phone'] }}, Email: {{ $inst['email'] }}, Website: {{ $inst['website_main'] }}</p>
        </div>
    </div>
    @endif
    <hr class="header-line">

    <div class="title">
        <h3 style="text-align:center; text-decoration:underline;">DOKUMEN PRATINJAU REAL-TIME</h3>
        <p style="text-align:center;">Modul: <strong>{{ $moduleLabel ?? 'Semua Pengaturan' }}</strong></p>
        <p style="text-align:center;">Nomor: 123/ITSNU/PRATINJAU/{{ date('Y') }}</p>
    </div>

    <div class="content">
        <p>Ini adalah dokumen pratinjau yang dihasilkan langsung oleh mesin pembuat PDF (DomPDF). Pengaturan tipografi, spasi, margin, dan ukuran kertas yang Anda atur akan terlihat persis seperti ini pada dokumen aslinya.</p>
        
        <table class="preview-table">
            <thead>
                <tr>
                    <th style="width:10%; text-align:center;">No</th>
                    <th style="width:40%;">Nama Pengaturan</th>
                    <th style="width:50%;">Nilai Saat Ini</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align:center;">1</td>
                    <td>Keluarga Font</td>
                    <td>{{ \App\Models\Setting::get('pdf_font_family', 'Times New Roman, Times, serif') }}</td>
                </tr>
                <tr>
                    <td style="text-align:center;">2</td>
                    <td>Ukuran Font Tubuh</td>
                    <td>{{ \App\Models\Setting::get('pdf_body_font_size', 11) }} pt</td>
                </tr>
                <tr>
                    <td style="text-align:center;">3</td>
                    <td>Ukuran Kertas</td>
                    <td>{{ strtoupper(\App\Models\Setting::get('pdf_paper_size', 'a4')) }}</td>
                </tr>
                <tr>
                    <td style="text-align:center;">4</td>
                    <td>Posisi Logo</td>
                    <td>{{ ucfirst(\App\Models\Setting::get('pdf_logo_position', 'left')) }}</td>
                </tr>
            </tbody>
        </table>

        <p>Paragraf kedua digunakan untuk melihat jarak antar paragraf (<em>Paragraph Spacing</em>) dan baris pertama yang menjorok ke dalam (<em>First Line Indent</em>). Silakan ubah pengaturan tersebut dan lihat perbedaannya secara instan pada dokumen ini.</p>
    </div>

    <div class="signature" style="margin-top:50px; text-align:right;">
        <p>Kasinta, {{ now()->translatedFormat('d F Y') }}</p>
        <p>Admin LPPM</p>
        <br><br><br>
        <p><strong><u>Nama Admin LPPM</u></strong></p>
        <p>NIP. 19800101 200501 1 001</p>
    </div>
</body>
</html>
