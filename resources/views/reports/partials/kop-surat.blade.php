<div class="kop-surat">
    <div class="kop-surat-inner">
        @php $logoPos = $pdfConfig['logo_position'] ?? 'left'; $logoSrc = get_logo_base64(); @endphp
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
            <div class="inst-name">INSTITUT TEKNOLOGI DAN SAINS NAHDLATUL ULAMA PEKALONGAN</div>
            <div class="lppm-name">LEMBAGA PENELITIAN DAN PENGABDIAN KEPADA MASYARAKAT (LPPM)</div>
            <div class="inst-address">
                Jl. Karangdowo No. 9, Kedungwuni, Kab. Pekalongan, Jawa Tengah 51173<br>
                Email: lppm@itsnupekalongan.ac.id | Website: https://lppm.itsnupekalongan.ac.id
            </div>
        </div>
    </div>
</div>
