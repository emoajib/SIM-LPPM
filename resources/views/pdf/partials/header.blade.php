<table class="header-table">
    <tr>
        <td width="15%" style="border:none;">
            @php $pdfConfig ??= get_pdf_config('letter'); @endphp
            @if(($pdfConfig['show_logo'] ?? true))
                <img src="{{ get_logo_base64() }}" class="logo" style="width: {{ $pdfConfig['logo_size'] ?? 110 }}px;">
            @else
                <img src="{{ public_path('logo.png') }}" class="logo">
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
