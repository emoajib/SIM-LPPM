@php $pdfConfig ??= get_pdf_config('letter'); @endphp
<style>
    /* Vetted by AI - Manual Review Required by Senior Engineer/Manager */
    @page {
        size: {{ $pdfConfig['paper_size'] ?? 'a4' }} portrait;
        margin: {{ $pdfConfig['custom_margins'] ?: $pdfConfig['page_margin'] }};
    }
    body {
        font-family: {!! $pdfConfig['font_family'] !!};
        font-size: {{ $pdfConfig['body_font_size'] }}pt;
        line-height: {{ $pdfConfig['line_height'] }};
        color: #000;
    }
    .header-table {
        width: 100%;
        border-bottom: 2px solid #000;
        margin-bottom: 8px;
        padding-bottom: 0px;
        border-collapse: collapse;
    }
    .header-table td {
        padding: 0;
        vertical-align: bottom;
    }
    .logo {
        width: 110px;
    }
    .header-text {
        text-align: center;
    }
    .header-text .univ {
        font-size: 14pt;
        font-weight: bold;
        margin: 0;
    }
    .header-text .dept {
        font-size: 13pt;
        font-weight: bold;
        margin: 0;
    }
    .header-text .address {
        font-size: 9pt;
        font-style: italic;
        margin: 0;
    }
    .header-text .contact {
        font-size: 9pt;
        margin: 0;
        margin-bottom: 3px; /* Memberikan jarak sedikit dari garis */
    }
    .title {
        text-align: center;
        font-weight: bold;
        text-decoration: underline;
        margin-top: 20px;
        margin-bottom: 0;
        font-size: 12pt;
    }
    .number {
        text-align: center;
        margin-bottom: 20px;
    }
    .bismillah {
        text-align: center;
        font-weight: bold;
        margin-bottom: 15px;
        font-size: 11pt;
    }
    .content {
        text-align: justify;
    }
    .meta-table {
        width: 100%;
        margin-top: 10px;
    }
    .meta-table td {
        border: none;
        padding: 1px;
        vertical-align: top;
    }
    .table-data {
        width: 100%;
        border-collapse: collapse;
        margin: 8px 0;
    }
    .table-data th, .table-data td {
        border: 1px solid #000;
        padding: 5px 8px;
        text-align: left;
        font-size: 10.5pt;
    }
    .table-data th {
        text-align: center;
        background-color: #f2f2f2;
    }
    .signature-table {
        width: 100%;
        margin-top: 20px;
    }
    .signature-box {
        width: 50%;
        text-align: left;
    }
    .signature-box.right {
        text-align: left;
        padding-left: 10%;
    }
    .content p {
        margin: {{ $pdfConfig['paragraph_spacing'] }}px 0;
        @if($pdfConfig['paragraph_indent'] > 0)
        text-indent: {{ $pdfConfig['paragraph_indent'] }}px;
        @endif
    }
    .qr-code {
        margin: 10px 0;
    }
    .travel-table {
        width: 100%;
        border-top: 1px solid #000;
        margin-top: 15px;
        padding-top: 3px;
        font-size: 11pt;
    }
    .travel-table td {
        border: none;
        padding: 0px;
        vertical-align: top;
    }
    .line-dots {
        border-bottom: 1px dotted #000;
        display: inline-block;
        width: 150px;
    }
    .sig-spacer {
        height: 60px;
    }
</style>
