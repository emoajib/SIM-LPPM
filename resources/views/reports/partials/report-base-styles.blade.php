@php
    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    $pdfConfig ??= get_pdf_config('report');
@endphp
body {
    font-family: {{ $pdfConfig['font_family'] }};
    font-size: {{ $pdfConfig['body_font_size'] }}pt;
    @if($pdfConfig['compact'])
    line-height: 1.1;
    @endif
}
@page {
    @if($pdfConfig['compact'])
    margin: 1.5cm 1cm;
    @else
    margin: {{ $pdfConfig['page_margin'] }};
    @endif
}
