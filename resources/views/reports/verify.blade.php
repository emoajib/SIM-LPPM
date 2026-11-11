<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><i class="ti ti-check me-1" aria-hidden="true"></i>
Verifikasi Dokumen</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; margin: 0; padding: 24px; background: #f6f7fb; color: #111827; }
        .card { max-width: 760px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 18px 18px; }
        .title { font-size: 18px; font-weight: 700; margin: 0 0 6px 0; }
        .subtitle { margin: 0 0 16px 0; color: #6b7280; font-size: 13px; }
        .grid { display: grid; grid-template-columns: 1fr; gap: 10px; }
        .row { display: flex; justify-content: space-between; gap: 12px; padding: 10px 12px; border: 1px solid #f1f5f9; border-radius: 10px; background: #fafafa; }
        .k { color: #6b7280; font-size: 12px; }
        .v { font-weight: 600; font-size: 13px; text-align: right; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .b-draft { background: #e5e7eb; color: #111827; }
        .b-submitted { background: #dbeafe; color: #1d4ed8; }
        .b-approved { background: #dcfce7; color: #166534; }
        .b-rejected { background: #fee2e2; color: #991b1b; }
        .actions { margin-top: 14px; display: flex; gap: 10px; flex-wrap: wrap; }
        .btn { display: inline-block; text-decoration: none; padding: 10px 12px; border-radius: 10px; font-weight: 700; font-size: 13px; border: 1px solid #e5e7eb; color: #111827; background: #ffffff; }
        .btn-primary { background: #1d4ed8; color: #ffffff; border-color: #1d4ed8; }
        .muted { margin-top: 12px; font-size: 12px; color: #6b7280; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="card">
        <h1 class="title"><i class="ti ti-check me-1" aria-hidden="true"></i>
Verifikasi Dokumen</h1>
        <p class="subtitle">Dokumen ini diverifikasi melalui QR pada PDF.</p>

        @php
            $status = $report->status?->value;
            $statusClass = match ($status) {
                'draft' => 'b-draft',
                'submitted' => 'b-submitted',
                'approved' => 'b-approved',
                'rejected' => 'b-rejected',
                default => 'b-draft',
            };
        @endphp

        <div class="grid">
            <div class="row">
                <div class="k">Jenis Laporan</div>
                <div class="v">{{ $typeLabel }}</div>
            </div>
            <div class="row">
                <div class="k">Tahun</div>
                <div class="v">{{ $report->year }}</div>
            </div>
            <div class="row">
                <div class="k">Versi Dokumen</div>
                <div class="v">{{ $variant === 'approved' ? 'Disetujui' : 'Diajukan' }}</div>
            </div>
            <div class="row">
                <div class="k">Status</div>
                <div class="v"><span class="badge {{ $statusClass }}">{{ $report->status?->label() ?? '-' }}</span></div>
            </div>
            <div class="row">
                <div class="k">Diajukan Oleh (Kepala LPPM)</div>
                <div class="v">{{ $report->submitter?->name ?? '-' }}</div>
            </div>
            <div class="row">
                <div class="k">Waktu Pengajuan</div>
                <div class="v">{{ $report->submitted_at?->translatedFormat('d M Y H:i') ?? '-' }}</div>
            </div>
            <div class="row">
                <div class="k">Disetujui Oleh (Rektor)</div>
                <div class="v">{{ $report->approver?->name ?? '-' }}</div>
            </div>
            <div class="row">
                <div class="k">Waktu Persetujuan</div>
                <div class="v">{{ $report->approved_at?->translatedFormat('d M Y H:i') ?? '-' }}</div>
            </div>
            <div class="row">
                <div class="k">Validasi Kepala LPPM</div>
                <div class="v">
                    @if($submittedSignature)
                        {{ $submittedValid ? 'VALID' : 'TIDAK VALID' }}
                    @else
                        -
                    @endif
                </div>
            </div>
            <div class="row">
                <div class="k">Validasi Rektor</div>
                <div class="v">
                    @if($approvedSignature)
                        {{ $approvedValid ? 'VALID' : 'TIDAK VALID' }}
                    @else
                        -
                    @endif
                </div>
            </div>
            <div class="row">
                <div class="k">Hash PDF Resmi (SHA-256)</div>
                <div class="v">
                    @if($approvedSignature?->document_hash)
                        {{ $approvedSignature->document_hash }}
                    @elseif($submittedSignature?->document_hash)
                        {{ $submittedSignature->document_hash }}
                    @else
                        -
                    @endif
                </div>
            </div>
        </div>

        <div class="actions">
            <a class="btn btn-primary d-flex align-items-center gap-2" href="{{ url('/reports') }}">Buka Monitoring Laporan</a>
            <a class="btn d-flex align-items-center gap-2" href="{{ url('/') }}">Beranda</a>
        </div>

        <div class="muted">
            QR ini memastikan tautan verifikasi tidak diubah. Jika status masih “Diajukan”, maka dokumen belum disetujui Rektor.
        </div>
    </div>
</body>
</html>
