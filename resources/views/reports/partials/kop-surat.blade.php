{{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
@php
    $inst = get_institution_config();
    $logoPos = $pdfConfig['logo_position'] ?? 'left';
    $logoSrc = get_logo_base64();
    $logoSize = $pdfConfig['logo_size'] ?? 65;
@endphp
<div class="kop-surat" style="border-bottom: 2pt solid #000; padding-bottom: 1px; margin-bottom: 3px; margin-top: -20px;">
    <div class="kop-surat-inner" style="border-bottom: 0.5pt solid #000; padding-bottom: 0px;">
        <table style="width: 100%; border-collapse: collapse; border: none; margin: 0; padding: 0;">
            <tr>
                @if($logoPos === 'center')
                    <td style="border: none; text-align: center; padding: 0; vertical-align: middle;">
                        @if(($pdfConfig['show_logo'] ?? true) && $logoSrc)
                            <img src="{{ $logoSrc }}" style="width: {{ $logoSize }}px; margin-bottom: 5px; border: none;">
                        @endif
                        <div class="header-text" style="text-align: center;">
                            <div class="inst-name" style="font-size: 12pt; font-weight: bold; margin-bottom: 2px; text-transform: uppercase;">{{ $inst['full_name'] }}</div>
                            <div class="lppm-name" style="font-size: 10pt; font-weight: bold; margin-bottom: 2px; text-transform: uppercase;">{{ $inst['lppm_full_name'] }}</div>
                            <div class="inst-address" style="font-size: 8pt; color: #333; line-height: 1.3;">
                                {{ $inst['address_line1'] }}, {{ $inst['address_line2'] }}<br>
                                Email: {{ $inst['email_public'] }} | Website: {{ $inst['website'] }}
                            </div>
                        </div>
                    </td>
                @elseif($logoPos === 'right')
                    <td width="85%" style="border: none; text-align: center; padding: 0; vertical-align: middle;">
                        <div class="header-text" style="text-align: center;">
                            <div class="inst-name" style="font-size: 12pt; font-weight: bold; margin-bottom: 2px; text-transform: uppercase;">{{ $inst['full_name'] }}</div>
                            <div class="lppm-name" style="font-size: 10pt; font-weight: bold; margin-bottom: 2px; text-transform: uppercase;">{{ $inst['lppm_full_name'] }}</div>
                            <div class="inst-address" style="font-size: 8pt; color: #333; line-height: 1.3;">
                                {{ $inst['address_line1'] }}, {{ $inst['address_line2'] }}<br>
                                Email: {{ $inst['email_public'] }} | Website: {{ $inst['website'] }}
                            </div>
                        </div>
                    </td>
                    <td width="15%" style="border: none; text-align: right; padding: 0; vertical-align: middle;">
                        @if(($pdfConfig['show_logo'] ?? true) && $logoSrc)
                            <img src="{{ $logoSrc }}" style="width: {{ $logoSize }}px; border: none; display: inline-block; vertical-align: middle; margin-top: 0px; margin-bottom: 0px;">
                        @endif
                    </td>
                @else
                    <td width="15%" style="border: none; text-align: left; padding: 0; vertical-align: middle;">
                        @if(($pdfConfig['show_logo'] ?? true) && $logoSrc)
                            <img src="{{ $logoSrc }}" style="width: {{ $logoSize }}px; border: none; display: inline-block; vertical-align: middle; margin-top: 0px; margin-bottom: 0px;">
                        @endif
                    </td>
                    <td width="85%" style="border: none; text-align: center; padding: 0; vertical-align: middle;">
                        <div class="header-text" style="text-align: center;">
                            <div class="inst-name" style="font-size: 12pt; font-weight: bold; margin-bottom: 2px; text-transform: uppercase;">{{ $inst['full_name'] }}</div>
                            <div class="lppm-name" style="font-size: 10pt; font-weight: bold; margin-bottom: 2px; text-transform: uppercase;">{{ $inst['lppm_full_name'] }}</div>
                            <div class="inst-address" style="font-size: 8pt; color: #333; line-height: 1.3;">
                                {{ $inst['address_line1'] }}, {{ $inst['address_line2'] }}<br>
                                Email: {{ $inst['email_public'] }} | Website: {{ $inst['website'] }}
                            </div>
                        </div>
                    </td>
                @endif
            </tr>
        </table>
    </div>
</div>
