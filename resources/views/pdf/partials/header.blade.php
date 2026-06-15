@php
    $pdfConfig ??= get_pdf_config('letter');
    $logoPos = $pdfConfig['logo_position'] ?? 'left';
    $logoSrc = get_logo_base64();
    $logoSize = $pdfConfig['logo_size'] ?? 110;
@endphp
@if($logoPos === 'center')
<table class="header-table">
    <tr>
        <td style="border:none; text-align:center;">
            @if(($pdfConfig['show_logo'] ?? true) && $logoSrc)
                <img src="{{ $logoSrc }}" class="logo" style="width: {{ $logoSize }}px;">
            @endif
        </td>
    </tr>
    <tr>
        <td class="header-text" style="border:none;">
            <div class="dept">LEMBAGA PENELITIAN DAN PENGABDIAN MASYARAKAT</div>
            <div class="univ">ITSNU PEKALONGAN</div>
            <div class="address">Jl. Karangdowo No. 9 Kedungwuni Kab. Pekalongan Kode Pos 51173</div>
            <div class="contact">Telp/Fax. (0285) 7831614 email: lppmitsnupkl@gmail.com #SantriHighTech</div>
        </td>
    </tr>
</table>
@else
<table class="header-table">
    <tr>
        @if($logoPos === 'right')
        <td width="85%" class="header-text" style="border:none;">
            <div class="dept">LEMBAGA PENELITIAN DAN PENGABDIAN MASYARAKAT</div>
            <div class="univ">ITSNU PEKALONGAN</div>
            <div class="address">Jl. Karangdowo No. 9 Kedungwuni Kab. Pekalongan Kode Pos 51173</div>
            <div class="contact">Telp/Fax. (0285) 7831614 email: lppmitsnupkl@gmail.com #SantriHighTech</div>
        </td>
        <td width="15%" style="border:none; text-align:right;">
            @if(($pdfConfig['show_logo'] ?? true) && $logoSrc)
                <img src="{{ $logoSrc }}" class="logo" style="width: {{ $logoSize }}px;">
            @endif
        </td>
        @else
        <td width="15%" style="border:none;">
            @if(($pdfConfig['show_logo'] ?? true) && $logoSrc)
                <img src="{{ $logoSrc }}" class="logo" style="width: {{ $logoSize }}px;">
            @endif
        </td>
        <td width="85%" class="header-text" style="border:none;">
            <div class="dept">LEMBAGA PENELITIAN DAN PENGABDIAN MASYARAKAT</div>
            <div class="univ">ITSNU PEKALONGAN</div>
            <div class="address">Jl. Karangdowo No. 9 Kedungwuni Kab. Pekalongan Kode Pos 51173</div>
            <div class="contact">Telp/Fax. (0285) 7831614 email: lppmitsnupkl@gmail.com #SantriHighTech</div>
        </td>
        @endif
    </tr>
</table>
@endif
