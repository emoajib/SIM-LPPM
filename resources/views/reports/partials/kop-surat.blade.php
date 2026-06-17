@php
    $inst = get_institution_config();
    $logoPos = $pdfConfig['logo_position'] ?? 'left';
    $logoSrc = get_logo_base64();
@endphp
<div class="kop-surat">
    <div class="kop-surat-inner">
        @if(($pdfConfig['show_logo'] ?? true) && $logoSrc)
            @if($logoPos === 'center')
                <img src="{{ $logoSrc }}" class="logo" style="display:block; margin:0 auto; width:{{ $pdfConfig['logo_size'] ?? 65 }}px;">
            @elseif($logoPos === 'right')
                <img src="{{ $logoSrc }}" class="logo" style="position:absolute; right:0; top:0; left:auto; width:{{ $pdfConfig['logo_size'] ?? 65 }}px;">
            @else
                <img src="{{ $logoSrc }}" class="logo" style="position:absolute; left:0; top:0; width:{{ $pdfConfig['logo_size'] ?? 65 }}px;">
            @endif
        @endif
        <div class="header-text" style="margin-{{ $logoPos === 'right' ? 'right' : 'left' }}: {{ $logoPos === 'center' ? '0' : '70px' }};">
            <div class="inst-name">{{ strtoupper($inst['full_name']) }}</div>
            <div class="lppm-name">{{ strtoupper($inst['lppm_full_name']) }}</div>
            <div class="inst-address">
                {{ $inst['address_line1'] }}, {{ $inst['address_line2'] }}<br>
                Email: {{ $inst['email_public'] }} | Website: {{ $inst['website'] }}
            </div>
        </div>
    </div>
</div>
