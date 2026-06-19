@php $inst = get_institution_config(); @endphp
<table class="signature-table">
    <tr>
        <td width="50%" style="border:none;"></td>
        <td class="signature-box right" style="border:none;">
            Ditetapkan di : {{ $inst['city'] }}<br>
            Pada tanggal : {{ $letter->published_at ? $letter->published_at->translatedFormat('d F Y') : '....................' }}
            <br><br>
            {{ $inst['lppm_head_position'] }}<br>
            {{ $inst['lppm_short'] }} {{ $inst['name'] }}
            
            <div class="qr-code">
                @if($letter->signature_mode?->value === 'tte' && $letter->status?->value === 'published')
                    <img src="{{ $qrDataUri }}" alt="QR Code" style="width: 80px; height: 80px;">
                @else
                    <br><br><br><br><br>
                @endif
            </div>

            <strong>{{ $metadata['signer_name'] ?? $inst['lppm_head_name'] }}</strong><br>
            NIDN. {{ $metadata['signer_nidn'] ?? $inst['lppm_head_nidn'] }}
        </td>
    </tr>
</table>
