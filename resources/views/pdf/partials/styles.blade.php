@php $pdfConfig ??= get_pdf_config('letter'); @endphp
<style>
    /* Vetted by AI - Manual Review Required by Senior Engineer/Manager */
    @page {
        size: {{ $pdfConfig['paper_size'] ?? 'a4' }} {{ $pdfConfig['orientation'] ?? 'portrait' }};
        margin: {{ $pdfConfig['custom_margins'] ?: $pdfConfig['page_margin'] }};
    }
    body {
        font-family: {!! $pdfConfig['font_family'] !!}, sans-serif;
        font-size: {{ $pdfConfig['body_font_size'] ?? 10 }}pt;
        line-height: {{ $pdfConfig['line_height'] ?? 1.4 }};
        color: #000;
        margin: 0;
        padding: 0;
    }
    .page-break {
        page-break-after: always;
    }
    .header-table {
        width: 100%;
        border-bottom: 2px solid #000;
        margin-bottom: 5px;
        padding-bottom: 0px;
        margin-top: -20px;
        border-collapse: collapse;
    }
    .header-table td {
        padding: 0;
        vertical-align: middle;
    }
    .logo {
        width: 100px;
    }
    .header-text {
        text-align: center;
    }
    .header-text .univ {
        font-size: 14pt;
        font-weight: bold;
        margin: 0;
        text-transform: uppercase;
    }
    .header-text .dept {
        font-size: 12pt;
        font-weight: bold;
        margin: 0;
        text-transform: uppercase;
    }
    .header-text .address {
        font-size: 8pt;
        font-style: italic;
        margin: 0;
    }
    .header-text .contact {
        font-size: 8pt;
        margin: 0;
    }
    .title {
        text-align: center;
        font-weight: bold;
        text-decoration: underline;
        margin-top: 20px;
        margin-bottom: 10px;
        font-size: 12pt;
        text-transform: uppercase;
    }
    .content {
        text-align: justify;
    }
    .table-data {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
        table-layout: fixed;
    }
    .table-data th, .table-data td {
        border: 1px solid #000;
        padding: 4px 8px;
        text-align: left;
        font-size: 9.5pt;
        word-wrap: break-word;
    }
    .table-data th {
        text-align: center;
        background-color: #f5f5f5;
        font-weight: bold;
    }
    .no-border, .no-border td, .no-border th {
        border: none !important;
        padding: 2px 0 !important;
    }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-justify { text-align: justify; }
    .font-bold { font-weight: bold; }
    
    .signature-table {
        width: 100%;
        margin-top: 30px;
    }
    .sig-spacer {
        height: 80px;
    }
    .footer-note {
        font-size: 8pt;
        font-style: italic;
        color: #666;
        margin-top: 20px;
    }
    .footer-institutional {
        position: fixed;
        bottom: -20px;
        left: 0px;
        right: 0px;
        height: 35px;
        text-align: center;
        font-size: 8pt;
        border-top: 0.5pt solid #ccc;
        padding-top: 4px;
        color: #666;
    }
    .footer-institutional .page-number:after {
        content: counter(page);
    }
</style>
