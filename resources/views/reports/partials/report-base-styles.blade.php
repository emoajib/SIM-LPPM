@php
    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    $pdfConfig ??= get_pdf_config('report');
@endphp
body {
    font-family: {{ $pdfConfig['font_family'] }};
    font-size: {{ $pdfConfig['body_font_size'] }}pt;
    line-height: {{ $pdfConfig['line_height'] }};
}
@page {
    size: {{ $pdfConfig['paper_size'] ?? 'a4' }} portrait;
    margin: {{ $pdfConfig['custom_margins'] ?: $pdfConfig['page_margin'] }};
}
