<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Surat - {{ $letter->letter_number ?? 'Draft' }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; margin: 0; padding: 24px; background: #f6f7fb; color: #111827; }
        .card { max-width: 860px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 18px 18px; }
        .title { font-size: 18px; font-weight: 700; margin: 0 0 6px 0; }
        .subtitle { margin: 0 0 16px 0; color: #6b7280; font-size: 13px; line-height: 1.5; }
        .grid { display: grid; grid-template-columns: 1fr; gap: 10px; }
        .row { display: flex; justify-content: space-between; gap: 12px; padding: 10px 12px; border: 1px solid #f1f5f9; border-radius: 10px; background: #fafafa; }
        .k { color: #6b7280; font-size: 12px; }
        .v { font-weight: 600; font-size: 13px; text-align: right; word-break: break-all; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 999px; font-size: 12px; font-weight: 800; }
        .b-valid { background: #dcfce7; color: #166534; }
        .b-invalid { background: #fee2e2; color: #991b1b; }
        .actions { margin-top: 14px; display: flex; gap: 10px; flex-wrap: wrap; }
        .btn { display: inline-block; text-decoration: none; padding: 10px 12px; border-radius: 10px; font-weight: 700; font-size: 13px; border: 1px solid #e5e7eb; color: #111827; background: #ffffff; }
    </style>
</head>
<body>
    <div class="card">
        <h1 class="title">Verifikasi Surat</h1>
        <p class="subtitle">Halaman ini memverifikasi keaslian surat yang diterbitkan oleh LPPM ITSNU Pekalongan.</p>

        <div class="grid">
            <div class="row">
                <div class="k">Status</div>
                <div class="v"><span class="badge {{ in_array($letter->status, ['published', 'ready_to_print']) ? 'b-valid' : 'b-invalid' }}">{{ $statusLabel }}</span></div>
            </div>
            <div class="row">
                <div class="k">Jenis Surat</div>
                <div class="v">{{ $letter->letterType->name ?? '-' }}</div>
            </div>
            <div class="row">
                <div class="k">Nomor Surat</div>
                <div class="v">{{ $letter->letter_number ?? '-' }}</div>
            </div>
            <div class="row">
                <div class="k">Pengaju</div>
                <div class="v">{{ $letter->user->name ?? '-' }}</div>
            </div>
            <div class="row">
                <div class="k">Diterbitkan</div>
                <div class="v">{{ $letter->published_at?->translatedFormat('d M Y H:i') ?? '-' }}</div>
            </div>
            <div class="row">
                <div class="k">Mode Tanda Tangan</div>
                <div class="v">{{ $letter->signature_mode === 'tte' ? 'TTE (QR Code)' : 'Kosong' }}</div>
            </div>
        </div>

        <div class="actions">
            <a class="btn" href="{{ url('/') }}">Beranda</a>
        </div>
    </div>
</body>
</html>
