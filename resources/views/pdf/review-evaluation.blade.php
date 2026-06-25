{{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Lembar Penilaian Reviewer - {{ $proposal->id }}</title>
    @include('pdf.partials.styles')
    <style>
        .main-title {
            text-align: center;
            margin: 15px 0;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11pt;
            line-height: 1.3;
        }
        .info-table {
            width: 100%;
            margin-bottom: 15px;
        }
        .info-table td {
            padding: 3.5px 0 !important;
            vertical-align: top;
            border: none !important;
            font-size: 9.5pt;
        }
        .scoring-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .scoring-table th, .scoring-table td {
            border: 0.5pt solid #000;
            padding: 6px;
            font-size: 9pt;
            line-height: 1.3;
            vertical-align: top;
        }
        .scoring-table th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    @include('pdf.partials.review-evaluation-content')
</body>

</html>
