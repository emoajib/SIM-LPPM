<table class="signature-table">
    <tr>
        <td width="50%" style="border:none;"></td>
        <td class="signature-box right" style="border:none;">
            Ditetapkan di : Pekalongan<br>
            Pada tanggal : {{ $letter->published_at ? $letter->published_at->translatedFormat('d F Y') : '....................' }}
            <br><br>
            Kepala LPPM<br>
            ITSNU Pekalongan
            
            <div class="qr-code">
                @if($letter->signature_mode === 'tte' && $letter->status === 'published')
                    <img src="{{ $qrDataUri }}" alt="QR Code" style="width: 80px; height: 80px;">
                @else
                    <br><br><br><br><br>
                @endif
            </div>

            <strong>{{ $metadata['signer_name'] ?? 'Aria Mulyapradana, S.Psi., M.A.' }}</strong><br>
            NIDN. {{ $metadata['signer_nidn'] ?? '' }}
        </td>
    </tr>
</table>
