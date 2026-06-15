<!DOCTYPE html>
<html>
@php
    // Defensive defaults in case variables are not passed from all render paths
    $isApproved ??= false;
    $isSigned ??= false;
    $logbookApprovalMode ??= 'digital';
    // Fallback: derive submitterFullName from proposal if not explicitly passed
    if (!isset($submitterFullName)) {
        $submitterIdentity = $proposal->submitter->identity ?? null;
        $submitterFullName = format_name(
            $submitterIdentity?->title_prefix ?? '',
            $proposal->submitter->name,
            $submitterIdentity?->title_suffix ?? ''
        );
    }
@endphp
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Catatan Harian - {{ $proposal->id }}</title>
    @include('pdf.partials.styles')
    <style>
        .protection-box {
            text-align: center;
            border: 1px solid #000;
            padding: 5px;
            margin-top: 5px;
            font-size: 8pt;
            background-color: #fff;
            margin-bottom: 15px;
        }
        .document-title {
            text-align: center;
            margin: 15px 0;
            font-weight: bold;
            font-size: 14pt;
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
            padding: 6px;
            text-align: left;
            vertical-align: top;
            font-size: 9pt;
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
            margin-bottom: 5px;
            font-size: 10pt;
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
        .log-content {
            margin-top: 5px;
            font-size: 8.5pt;
            text-align: justify;
            white-space: pre-wrap;
        }
        /* Vetted by AI - Manual Review Required by Senior Engineer/Manager */
        .signature-section {
            width: 100%;
            margin-top: 1cm;
        }
        .signature-table {
            width: 100%;
            border: none;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            border: none;
            padding: 10px;
        }
        .signature-qr {
            margin: 10px auto;
            display: block;
        }
    </style>
</head>
<body>
    {{-- Institutional Footer for all pages --}}
    <div class="footer-institutional">
        Lppm ITSNU Pekalongan - Tahun Akademik {{ $academicYear ?? date('Y') }}<br>
        <span class="page-number">Halaman </span>
    </div>

    <div class="page-break">
        <div style="font-size: 14pt; font-weight: bold; margin-bottom: 20px; text-transform: uppercase; text-align: center;">
            CATATAN HARIAN {{ $proposal->detailable_type === 'App\Models\Research' ? 'PENELITIAN' : 'PENGABDIAN' }} INTERNAL
        </div>
        
        <div style="margin: 40px 0; text-align: center;">
            @php $pdfConfig ??= get_pdf_config('letter'); @endphp
            @if(($pdfConfig['show_logo'] ?? true) && get_logo_base64())
                {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
                <img src="{{ get_logo_base64() }}" style="width: 180px;">
            @endif
        </div>

        <div style="font-size: 14pt; font-weight: bold; margin-bottom: 30px; line-height: 1.3; text-align: center;">
            {{ clean_proposal_title($proposal->title) }}
        </div>

        <div style="width: 100%; margin: 20px 0;">
            <div style="font-weight: bold; margin-bottom: 5px; text-align: center;">Oleh:</div>
            <table style="width: 100%; border: 0.5pt dashed #000; margin-bottom: 0;">
                <tr>
                    <td style="width: 15%; border: 0.5pt dashed #000; padding: 8px;">Ketua</td>
                    <td style="width: 45%; border: 0.5pt dashed #000; padding: 8px; font-weight: bold;">{{ format_name($proposal->submitter->identity?->title_prefix ?? '', $proposal->submitter->name, $proposal->submitter->identity?->title_suffix ?? '') }}</td>
                    <td style="width: 10%; border: 0.5pt dashed #000; padding: 8px;">NIDN</td>
                    <td style="width: 30%; border: 0.5pt dashed #000; padding: 8px; font-weight: bold;">{{ $proposal->submitter->identity?->identity_id ?? '-' }}</td>
                </tr>
                @php
                    $lecturerMembersCover = $proposal->teamMembers->filter(fn($m) => $m->id !== $proposal->submitter_id && ($m->identity?->type === 'dosen' || $m->pivot->role === 'anggota' || $m->pivot->role === 'dosen'));
                @endphp
                @foreach($lecturerMembersCover as $index => $member)
                <tr>
                    <td style="width: 15%; border: 0.5pt dashed #000; padding: 8px;">Anggota {{ to_roman($index + 1) }}</td>
                    <td style="width: 45%; border: 0.5pt dashed #000; padding: 8px; font-weight: bold;">{{ format_name($member->identity?->title_prefix ?? '', $member->name, $member->identity?->title_suffix ?? '') }}</td>
                    <td style="width: 10%; border: 0.5pt dashed #000; padding: 8px;">NIDN</td>
                    <td style="width: 30%; border: 0.5pt dashed #000; padding: 8px; font-weight: bold;">{{ $member->identity?->identity_id ?? '-' }}</td>
                </tr>
                @endforeach
            </table>
        </div>

        <div style="position: absolute; bottom: 2cm; width: 100%; text-align: center; font-weight: bold; font-size: 12pt; text-transform: uppercase;">
            FAKULTAS {{ strtoupper($proposal->submitter->identity?->faculty?->name ?? '-') }}<br>
            PROGRAM STUDI {{ strtoupper($proposal->submitter->identity?->studyProgram?->name ?? '-') }}<br>
            ITSNU PEKALONGAN<br>
            TAHUN {{ $proposal->start_year }}
        </div>
    </div>
    {{-- Standardized Header --}}
    @include('pdf.partials.header')

    <div class="document-title"
        style="margin-top: 1cm; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px;">
        {{ $docTitle ?? 'CATATAN HARIAN' }}
    </div>

    <table class="info-table">
        <tr>
            <td class="info-label">Judul Usulan</td>
            <td class="info-colon">:</td>
            <td><strong>{{ clean_proposal_title($proposal->title) }}</strong></td>
        </tr>
        <tr>
            <td class="info-label">Ketua Pengusul</td>
            <td class="info-colon">:</td>
            <td>{{ $proposal->submitter->name }} (NIDN: {{ $proposal->submitter->identity?->identity_id ?? '-' }})</td>
        </tr>
        <tr>
            <td class="info-label">Program Studi</td>
            <td class="info-colon">:</td>
            <td>{{ $proposal->submitter->identity?->studyProgram?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Skema</td>
            <td class="info-colon">:</td>
            <td>{{ $proposal->researchScheme->name ?? ($proposal->communityServiceScheme->name ?? '-') }}</td>
        </tr>
        <tr>
            <td class="info-label">Tahun Pelaksanaan</td>
            <td class="info-colon">:</td>
            <td>{{ $proposal->start_year }}</td>
        </tr>
    </table>



    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="12%">Tgl</th>
                <th width="30%">Aktivitas & Catatan</th>
                <th width="15%">Kelompok RAB</th>
                <th width="15%">Nominal (Rp)</th>
                <th width="8%">Progres</th>
                <th width="15%">Bukti (File)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($notes as $index => $note)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $note->activity_date->format('d/m/Y') }}</td>
                    <td class="text-justify">
                        <div class="font-bold" style="line-height: 1.4;">{{ $note->activity_description }}</div>
                        @if ($note->notes)
                            <div style="margin-top: 5px; font-style: italic; color: #444; font-size: 8pt; line-height: 1.4;">
                                Catatan: {{ $note->notes }}
                            </div>
                        @endif
                    </td>
                    <td class="text-center">
                        {{ $note->budgetGroup->name ?? '-' }}
                    </td>
                    <td class="text-right">
                        {{ $note->amount ? number_format($note->amount, 0, ',', '.') : '-' }}
                    </td>
                    <td class="text-center">{{ $note->progress_percentage }}%</td>
                    <td>
                        @if ($note->media->isNotEmpty())
                            <ul style="margin: 0; padding-left: 15px; font-size: 8pt;">
                                @foreach ($note->media as $media)
                                    <li>{{ \Illuminate\Support\Str::limit($media->file_name, 20) }}</li>
                                @endforeach
                            </ul>
                        @else
                            <div style="text-align: center; color: #666;">-</div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Belum ada catatan aktivitas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 30px; page-break-inside: avoid;">
        <table class="no-border" style="width: 100%;">
            <tr>
                <td width="50%" class="text-center" style="vertical-align: top; border: none;">
                    Menyetujui,<br>
                    Kepala LPPM ITSNU Pekalongan
                </td>
                <td width="50%" class="text-center" style="vertical-align: top; border: none;">
                    {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
                    Pekalongan, {{ $proposal->logbook_signed_at ? \Carbon\Carbon::parse($proposal->logbook_signed_at)->format('d F Y') : now()->format('d F Y') }}<br>
                    Ketua {{ $proposal->detailable_type === 'App\Models\Research' ? 'Peneliti' : 'Pelaksana' }}
                </td>
            </tr>
            <tr>
                <td class="text-center" style="height: 120px; vertical-align: bottom; border: none; padding-bottom: 10px;">
                    @if($qrUrlLppm ?? null)
                        <div style="margin-bottom: 5px;">
                            <img src="{{ generate_qr_code_data_uri($qrUrlLppm, 140) }}" width="70">
                        </div>
                        <div style="font-size: 7pt; color: #059669; font-weight: bold; margin-bottom: 5px;">VERIFIED BY LPPM</div>
                    @else
                        <div style="height: 70px;"></div>
                    @endif
                    @php $kepala = \App\Models\User::role('kepala lppm')->first(); @endphp
                    <strong><u>{{ $kepala->name ?? '.......................' }}</u></strong><br>
                    NPP. {{ $kepala->identity?->identity_id ?? '-' }}
                </td>
                <td class="text-center" style="height: 120px; vertical-align: bottom; border: none; padding-bottom: 10px;">
                    @if($qrUrlSubmitter ?? null)
                        <div style="margin-bottom: 5px;">
                            <img src="{{ generate_qr_code_data_uri($qrUrlSubmitter, 140) }}" width="70">
                        </div>
                        <div style="font-size: 7pt; color: #555; font-weight: bold; margin-bottom: 5px;">DIGITALLY SIGNED</div>
                    @else
                        <div style="height: 70px;"></div>
                    @endif
                    <strong><u>{{ $submitterFullName }}</u></strong><br>
                    NPP. {{ $proposal->submitter->identity?->identity_id ?? '-' }}
                </td>
            </tr>
        </table>
    </div>
    @php
        $hasAttachments = false;
        foreach ($notes as $note) {
            if ($note->media->isNotEmpty()) {
                $hasAttachments = true;
                break;
            }
        }
    @endphp

    @if ($hasAttachments)
        <div style="page-break-before: always;"></div>
        <div class="document-title" style="margin-bottom: 20px;">
            LAMPIRAN BUKTI DUKUNG
        </div>

        @foreach ($notes as $note)
            @if ($note->media->isNotEmpty())
                <div style="margin-bottom: 30px; border: 1px solid #ccc; padding: 15px; page-break-inside: avoid;">
                    <div
                        style="margin-top: 0; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 8px; font-weight: bold; font-size: 11pt;">
                        Kegiatan: {{ $note->activity_date->format('d F Y') }}
                        <div style="font-weight: normal; font-size: 9pt; color: #555; margin-top: 3px;">
                            {{ $note->activity_description }}
                        </div>
                    </div>

                    @foreach ($note->media as $media)
                        <div style="margin-bottom: 20px; text-align: center;">
                            <div
                                style="font-weight: bold; margin-bottom: 8px; font-size: 8pt; text-align: left; background: #f9f9f9; padding: 4px; border-left: 3px solid #ccc;">
                                File: {{ $media->file_name }}
                            </div>

                            @if (str_starts_with($media->mime_type, 'image/'))
                                @php
                                    // Attempt to get optimized 'pdf_image', fallback to original local path
                                    $imagePath = $media->hasGeneratedConversion('pdf_image') && file_exists($media->getPath('pdf_image'))
                                        ? $media->getPath('pdf_image')
                                        : $media->getPath();
                                @endphp

                                @if(file_exists($imagePath))
                                    <img src="{{ $imagePath }}"
                                        style="max-width: 100%; max-height: 500px; display: block; margin: 0 auto; border: 1px solid #ddd; padding: 3px;">
                                @else
                                    <div style="background: #fff0f0; border: 1px dashed red; padding: 10px; color: red;">
                                        Error: File gambar fisik tidak ditemukan di server.
                                    </div>
                                @endif
                            @else
                                <div style="background: #f8f9fa; border: 1px dashed #aaa; padding: 20px; color: #555;">
                                    Dokumen Lampiran Eksternal (<span style="text-transform: uppercase">{{ $media->extension }}</span>)<br>
                                    <small style="font-style: italic;">Sistem tidak dapat menempelkan file ini ke dalam lembar PDF. Harap
                                        ulik secara terpisah.</small>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        @endforeach
    @endif
</body>
</html>