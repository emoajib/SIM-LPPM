{{--
    Template: Laporan Keuangan (LPJ) Penelitian & Pengabdian
    Vetted by AI - Manual Review Required by Senior Engineer/Manager
--}}
<!DOCTYPE html>
<html>
@php
    $isApproved ??= false;
    $isSigned ??= false;
    $logbookApprovalMode ??= 'digital';
    if (!isset($submitterFullName)) {
        $submitterIdentity = $proposal->submitter->identity ?? null;
        $submitterFullName = format_name(
            $submitterIdentity?->title_prefix ?? '',
            $proposal->submitter->name,
            $submitterIdentity?->title_suffix ?? ''
        );
    }
    $academicYear = $proposal->start_year . '/' . ($proposal->start_year + 1);
    $totalProposedBudget = (float) $proposal->budgetItems->sum('total_price');
    $totalUsedBudget = (float) $proposal->dailyNotes->sum('amount');
    $pdfConfig ??= get_pdf_config('letter', 'logbook');
    $lineHeight = $pdfConfig['line_height'] ?? 1.5;
@endphp
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Laporan Keuangan - {{ $proposal->id }}</title>
    @include('pdf.partials.styles')
    <style>
        .document-title {
            text-align: center;
            margin: 15px 0;
            font-weight: bold;
            font-size: 13pt;
            text-transform: uppercase;
            text-decoration: underline;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        th, td {
            border: 0.5pt solid #000;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
            font-size: 8.5pt;
        }
        th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
        }
        .no-border, .no-border td, .no-border th {
            border: none !important;
            padding: 2px !important;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-justify { text-align: justify; }
        .font-bold { font-weight: bold; }
        .page-break { page-break-after: always; }
        
        .section-title {
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 6px;
            font-size: 9.5pt;
            text-transform: uppercase;
        }
        
        .footer-institutional {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            border-top: 1px solid #ccc;
            padding-top: 3px;
            color: #444;
        }
        .page-number::after {
            content: counter(page);
        }
    </style>
</head>
<body>
    {{-- Institutional Footer --}}
    <div class="footer-institutional">
        LPPM ITSNU Pekalongan - Laporan Pertanggungjawaban Keuangan (LPJ) - Tahun {{ $proposal->start_year }}<br>
        <span class="page-number">Halaman </span>
    </div>

    {{-- ===================== HALAMAN 1: COVER LAPORAN KEUANGAN ===================== --}}
    <div class="page-break">
        @include('pdf.partials.cover', [
            'coverTitle' => 'LAPORAN KEUANGAN (LPJ)<br>' . ($proposal->detailable_type === 'App\Models\Research' ? 'PENELITIAN' : 'PENGABDIAN KEPADA MASYARAKAT') . ' INTERNAL',
            'coverYear' => $proposal->start_year,
            'proposal' => $proposal,
            'submitterFullName' => $submitterFullName,
            'submitterNidn' => $proposal->submitter->identity?->identity_id ?? '-',
            'facultyName' => $proposal->submitter->identity?->faculty?->name ?? '-',
            'prodiName' => $proposal->submitter->identity?->studyProgram?->name ?? '-',
            'pdfConfig' => $pdfConfig,
        ])
    </div>

    {{-- ===================== HALAMAN 2: REKAPITULASI & RINCIAN ANGGARAN ===================== --}}
    @include('pdf.partials.header')

    <div class="document-title">
        LAPORAN PERTANGGUNGJAWABAN BIAYA (LPJ)
    </div>

    <table class="no-border" style="margin-bottom: 15px;">
        <tr>
            <td style="width: 22%;">Judul Usulan</td>
            <td style="width: 2%;">:</td>
            <td style="font-weight: bold;">{{ clean_proposal_title($proposal->title) }}</td>
        </tr>
        <tr>
            <td>Nomor Kontrak</td>
            <td>:</td>
            <td style="font-weight: bold;">{{ $proposal->contract_number ?? '-' }}</td>
        </tr>
        <tr>
            <td>Ketua Pelaksana</td>
            <td>:</td>
            <td>{{ $submitterFullName }} (NIDN: {{ $proposal->submitter->identity?->identity_id ?? '-' }})</td>
        </tr>
        <tr>
            <td>Skema</td>
            <td>:</td>
            <td>{{ $proposal->researchScheme->name ?? ($proposal->communityServiceScheme->name ?? '-') }}</td>
        </tr>
        <tr>
            <td>Tahun Anggaran</td>
            <td>:</td>
            <td>{{ $proposal->start_year }}</td>
        </tr>
    </table>

    <div class="section-title">A. REKAPITULASI PENGGUNAAN DANA</div>
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="45%">Kelompok Anggaran (RAB)</th>
                <th width="25%">Anggaran Disetujui (Rp)</th>
                <th width="25%">Realisasi Pengeluaran (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $budgetGroups = \App\Models\BudgetGroup::all();
                $totalProposed = 0;
                $totalRealized = 0;
            @endphp
            @foreach($budgetGroups as $index => $group)
                @php
                    $groupProposed = (float) $proposal->budgetItems->where('budget_group_id', $group->id)->sum('total_price');
                    $groupRealized = (float) $proposal->dailyNotes->where('budget_group_id', $group->id)->sum('amount');
                    $totalProposed += $groupProposed;
                    $totalRealized += $groupRealized;
                @endphp
                @if($groupProposed > 0 || $groupRealized > 0)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $group->name }}</td>
                    <td class="text-right">{{ number_format($groupProposed, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($groupRealized, 0, ',', '.') }}</td>
                </tr>
                @endif
            @endforeach
            <tr style="background-color: #f2f2f2; font-weight: bold;">
                <td colspan="2" class="text-center">TOTAL DANA</td>
                <td class="text-right">Rp {{ number_format($totalProposed, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($totalRealized, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title" style="margin-top: 20px;">B. RINCIAN PENGELUARAN DANA (CATATAN HARIAN)</div>
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="12%">Tanggal</th>
                <th width="40%">Uraian Aktivitas & Pengeluaran</th>
                <th width="23%">Kelompok Anggaran</th>
                <th width="20%">Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($proposal->dailyNotes()->orderBy('activity_date', 'asc')->get() as $index => $note)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $note->activity_date->format('d/m/Y') }}</td>
                    <td>
                        <div class="font-bold">{{ $note->activity_description }}</div>
                        @if ($note->notes)
                            <div style="margin-top: 3px; font-style: italic; color: #555; font-size: 7.5pt;">
                                Catatan: {{ $note->notes }}
                            </div>
                        @endif
                    </td>
                    <td class="text-center">{{ $note->budgetGroup->name ?? '-' }}</td>
                    <td class="text-right">{{ $note->amount ? number_format($note->amount, 0, ',', '.') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">Belum ada rincian pengeluaran dana.</td>
                </tr>
            @endforelse
            <tr style="background-color: #f2f2f2; font-weight: bold;">
                <td colspan="4" class="text-center">TOTAL REALISASI PENGELUARAN</td>
                <td class="text-right">Rp {{ number_format($totalRealized, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Pengesahan Keuangan --}}
    <div style="margin-top: 25px; page-break-inside: avoid;">
        <table class="no-border" style="width: 100%;">
            <tr>
                <td width="50%" class="text-center" style="vertical-align: top; border: none;">
                    Menyetujui,<br>
                    Kepala LPPM ITSNU Pekalongan
                </td>
                <td width="50%" class="text-center" style="vertical-align: top; border: none;">
                    Pekalongan, {{ $proposal->logbook_signed_at ? \Carbon\Carbon::parse($proposal->logbook_signed_at)->format('d F Y') : now()->format('d F Y') }}<br>
                    Ketua {{ $proposal->detailable_type === 'App\Models\Research' ? 'Peneliti' : 'Pelaksana' }}
                </td>
            </tr>
            <tr>
                <td class="text-center" style="height: 110px; vertical-align: bottom; border: none; padding-bottom: 5px;">
                    @if($qrUrlLppm ?? null)
                        <div style="margin-bottom: 3px;">
                            <img src="{{ generate_qr_code_data_uri($qrUrlLppm, 130) }}" width="65">
                        </div>
                        <div style="font-size: 7pt; color: #059669; font-weight: bold; margin-bottom: 3px;">VERIFIED BY LPPM</div>
                    @else
                        <div style="height: 65px;"></div>
                    @endif
                    @php $kepala = \App\Models\User::role('kepala lppm')->first(); @endphp
                    <strong><u>{{ $kepala->name ?? '.......................' }}</u></strong><br>
                    NIDN. {{ $kepala->identity?->identity_id ?? '-' }}
                </td>
                <td class="text-center" style="height: 110px; vertical-align: bottom; border: none; padding-bottom: 5px;">
                    @if($qrUrlSubmitter ?? null)
                        <div style="margin-bottom: 3px;">
                            <img src="{{ generate_qr_code_data_uri($qrUrlSubmitter, 130) }}" width="65">
                        </div>
                        <div style="font-size: 7pt; color: #555; font-weight: bold; margin-bottom: 3px;">DIGITALLY SIGNED</div>
                    @else
                        <div style="height: 65px;"></div>
                    @endif
                    <strong><u>{{ $submitterFullName }}</u></strong><br>
                    NIDN. {{ $proposal->submitter->identity->identity_id ?? '-' }}
                </td>
            </tr>
        </table>
    </div>

    {{-- ===================== HALAMAN 3: CATATAN HARIAN KEGIATAN (LOGBOOK) ===================== --}}
    <div class="page-break"></div>
    @include('pdf.partials.header')

    <div class="document-title">
        C. CATATAN HARIAN KEGIATAN (LOGBOOK)
    </div>

    <table class="no-border" style="margin-bottom: 12px;">
        <tr>
            <td style="width: 22%;">Judul Usulan</td>
            <td style="width: 2%;">:</td>
            <td style="font-weight: bold;">{{ clean_proposal_title($proposal->title) }}</td>
        </tr>
        <tr>
            <td>Ketua Pelaksana</td>
            <td>:</td>
            <td>{{ $submitterFullName }} (NIDN: {{ $proposal->submitter->identity?->identity_id ?? '-' }})</td>
        </tr>
        <tr>
            <td>Tahun Pelaksanaan</td>
            <td>:</td>
            <td>{{ $proposal->start_year }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="12%">Tanggal</th>
                <th width="33%">Aktivitas & Catatan</th>
                <th width="15%">Kelompok RAB</th>
                <th width="13%">Nominal (Rp)</th>
                <th width="8%">Progres</th>
                <th width="14%">Bukti (File)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($proposal->dailyNotes->sortBy('activity_date') as $index => $note)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $note->activity_date->format('d/m/Y') }}</td>
                    <td>
                        <div class="font-bold">{{ $note->activity_description }}</div>
                        @if ($note->notes)
                            <div style="margin-top: 3px; font-style: italic; color: #444; font-size: 8pt;">
                                Catatan: {{ $note->notes }}
                            </div>
                        @endif
                    </td>
                    <td class="text-center">{{ $note->budgetGroup->name ?? '-' }}</td>
                    <td class="text-right">{{ $note->amount ? number_format($note->amount, 0, ',', '.') : '-' }}</td>
                    <td class="text-center">{{ $note->progress_percentage ?? 0 }}%</td>
                    <td style="font-size: 7.5pt;">
                        @if ($note->media->isNotEmpty())
                            <ul style="margin: 0; padding-left: 12px;">
                                @foreach ($note->media as $m)
                                    <li>{{ \Illuminate\Support\Str::limit($m->file_name, 18) }}</li>
                                @endforeach
                            </ul>
                        @else
                            <div style="text-align: center; color: #888">-</div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Belum ada catatan aktivitas harian.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ===================== HALAMAN LAMPIRAN BUKTI FISIK NOTA / KWITANSI ===================== --}}
    @php
        $notesWithMedia = $proposal->dailyNotes()->with('media')->get()->filter(fn($n) => $n->media->isNotEmpty());
    @endphp

    @if ($notesWithMedia->isNotEmpty())
        <div class="page-break"></div>
        <div class="document-title" style="margin-bottom: 20px;">
            LAMPIRAN BUKTI FISIK PENGELUARAN DANA (KWITANSI / NOTA / STRUK)
        </div>

        @foreach ($notesWithMedia as $note)
            <div style="margin-bottom: 25px; border: 1px solid #ccc; padding: 12px; page-break-inside: avoid;">
                <div style="margin-top: 0; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 6px; font-weight: bold; font-size: 10pt;">
                    Transaksi: {{ $note->activity_date->format('d F Y') }} — Nominal: Rp {{ number_format($note->amount ?? 0, 0, ',', '.') }}
                    <div style="font-weight: normal; font-size: 8.5pt; color: #444; margin-top: 3px;">
                        Uraian: {{ $note->activity_description }} (Kelompok: {{ $note->budgetGroup->name ?? '-' }})
                    </div>
                </div>

                @foreach ($note->media as $media)
                    <div style="margin-bottom: 15px; text-align: center;">
                        <div style="font-weight: bold; margin-bottom: 6px; font-size: 7.5pt; text-align: left; background: #f9f9f9; padding: 3px 6px; border-left: 3px solid #0054a6;">
                            Nama Berkas: {{ $media->file_name }}
                        </div>

                        @if (str_starts_with($media->mime_type, 'image/'))
                            @php
                                $imagePath = $media->hasGeneratedConversion('pdf_image') && file_exists($media->getPath('pdf_image'))
                                    ? $media->getPath('pdf_image')
                                    : $media->getPath();
                            @endphp

                            @if(file_exists($imagePath))
                                <img src="{{ $imagePath }}"
                                    style="max-width: 100%; max-height: 460px; display: block; margin: 0 auto; border: 1px solid #ddd; padding: 2px;">
                            @else
                                <div style="background: #fff0f0; border: 1px dashed red; padding: 8px; color: red; font-size: 8pt;">
                                    Berkas gambar bukti tidak ditemukan di server.
                                </div>
                            @endif
                        @else
                            <div style="background: #f8f9fa; border: 1px dashed #aaa; padding: 15px; color: #555; font-size: 8pt;">
                                Dokumen Bukti Eksternal (<span style="text-transform: uppercase">{{ $media->extension }}</span>)<br>
                                <small style="font-style: italic;">Berkas PDF / Dokumen terlampir pada sistem.</small>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endforeach
    @endif
</body>
</html>
