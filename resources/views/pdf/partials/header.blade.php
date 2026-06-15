@php
    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    $pdfConfig ??= get_pdf_config('letter');
    $logoPos  = $pdfConfig['logo_position'] ?? 'left';
    $logoSize = $pdfConfig['logo_size'] ?? 110;
    $showLogo = ($pdfConfig['show_logo'] ?? true) && file_exists(public_path('logo.png'));
@endphp

@if($logoPos === 'center')
    {{-- Logo di tengah atas teks kop --}}
    @if($showLogo)
        <div style="text-align: center; margin-bottom: 4px;">
            <img src="{{ public_path('logo.png') }}" style="width: {{ $logoSize }}px;">
        </div>
    @endif
    <table class="header-table">
        <tr>
            <td style="border:none; text-align: center;">
                <div class="dept">LEMBAGA PENELITIAN DAN PENGABDIAN MASYARAKAT</div>
                <div class="univ">ITSNU PEKALONGAN</div>
                <div class="address">Jl. Karangdowo No. 9 Kedungwuni Kab. Pekalongan Kode Pos 51173</div>
                <div class="contact">Telp/Fax. (0285) 7831614 email: lppmitsnupkl@gmail.com #SantriHighTech</div>
            </td>
        </tr>
    </table>
@elseif($logoPos === 'right')
    {{-- Logo di kanan, teks di kiri --}}
    <table class="header-table">
        <tr>
            <td width="85%" class="header-text" style="border:none;">
                <div class="dept">LEMBAGA PENELITIAN DAN PENGABDIAN MASYARAKAT</div>
                <div class="univ">ITSNU PEKALONGAN</div>
                <div class="address">Jl. Karangdowo No. 9 Kedungwuni Kab. Pekalongan Kode Pos 51173</div>
                <div class="contact">Telp/Fax. (0285) 7831614 email: lppmitsnupkl@gmail.com #SantriHighTech</div>
            </td>
            <td width="15%" style="border:none; text-align: right; vertical-align: bottom;">
                @if($showLogo)
                    <img src="{{ public_path('logo.png') }}" style="width: {{ $logoSize }}px;">
                @endif
            </td>
        </tr>
    </table>
@else
    {{-- Default: logo di kiri (layout asli, zero visual change) --}}
    <table class="header-table">
        <tr>
            <td width="15%" style="border:none;">
                @if($showLogo)
                    <img src="{{ public_path('logo.png') }}" class="logo" style="width: {{ $logoSize }}px;">
                @endif
            </td>
            <td width="85%" class="header-text" style="border:none;">
                <div class="dept">LEMBAGA PENELITIAN DAN PENGABDIAN MASYARAKAT</div>
                <div class="univ">ITSNU PEKALONGAN</div>
                <div class="address">Jl. Karangdowo No. 9 Kedungwuni Kab. Pekalongan Kode Pos 51173</div>
                <div class="contact">Telp/Fax. (0285) 7831614 email: lppmitsnupkl@gmail.com #SantriHighTech</div>
            </td>
        </tr>
    </table>
@endif
