@php
    $pdfConfig ??= get_pdf_config('letter');
    $logoPos = $pdfConfig['logo_position'] ?? 'left';
    $logoSrc = get_logo_base64();
    $logoSize = $pdfConfig['logo_size'] ?? 110;
    $inst = get_institution_config();
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
            <div class="dept">{{ strtoupper($inst['lppm_name']) }}</div>
            <div class="univ">{{ strtoupper($inst['name']) }}</div>
            <div class="address">{{ $inst['address'] }}</div>
            <div class="contact">Telp/Fax. {{ $inst['phone'] }} email: {{ $inst['email'] }} {{ $inst['motto'] }}</div>
        </td>
    </tr>
</table>
@else
<table class="header-table">
    <tr>
        @if($logoPos === 'right')
        <td width="85%" class="header-text" style="border:none;">
            <div class="dept">{{ strtoupper($inst['lppm_name']) }}</div>
            <div class="univ">{{ strtoupper($inst['name']) }}</div>
            <div class="address">{{ $inst['address'] }}</div>
            <div class="contact">Telp/Fax. {{ $inst['phone'] }} email: {{ $inst['email'] }} {{ $inst['motto'] }}</div>
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
            <div class="dept">{{ strtoupper($inst['lppm_name']) }}</div>
            <div class="univ">{{ strtoupper($inst['name']) }}</div>
            <div class="address">{{ $inst['address'] }}</div>
            <div class="contact">Telp/Fax. {{ $inst['phone'] }} email: {{ $inst['email'] }} {{ $inst['motto'] }}</div>
        </td>
        @endif
    </tr>
</table>
@endif
