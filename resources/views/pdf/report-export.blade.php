<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Export - {{ $proposal->id }}</title>
    <style>
        @page {
            margin: 3cm 3cm 3cm 4cm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            line-height: 1.4;
            color: #000;
            text-align: justify;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #000;
            margin-bottom: 5px;
            padding-bottom: 5px;
        }
        .logo {
            width: 60px;
        }
        .header-text {
            text-align: left;
            padding-left: 10px;
        }
        .header-text div {
            font-weight: bold;
            font-size: 11pt;
        }
        .protection-box {
            text-align: center;
            border: 1px solid #000;
            padding: 5px;
            margin-top: 5px;
            font-size: 8pt;
            background-color: #fff;
            margin-bottom: 15px;
        }
        .report-type-box {
            text-align: center;
            margin: 10px 0;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #000;
            color: #fff;
            padding: 5px;
            font-size: 10pt;
        }
        .report-id {
            text-align: center;
            font-size: 9pt;
            margin-bottom: 20px;
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
            margin-bottom: 5px;
            font-size: 10pt;
            text-transform: uppercase;
        }
        
        .title-border-box {
            border: 1px solid #000;
            padding: 10px;
            margin-bottom: 15px;
            font-weight: bold;
            text-align: justify;
            background-color: #fafafa;
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
    @php
        $submitterFullName = format_name(
            $proposal->submitter->identity?->title_prefix ?? '',
            $proposal->submitter->name,
            $proposal->submitter->identity?->title_suffix ?? ''
        );
        $academicYear = $report->reporting_year . '/' . ((int)$report->reporting_year + 1);
        $facultyName = $proposal->submitter->identity?->faculty?->name ?? '.......................';
        $prodiName = $proposal->submitter->identity?->studyProgram?->name ?? '.......................';
        $periodLabel = $report->reporting_period === 'final' ? 'AKHIR' : 'KEMAJUAN';
    @endphp
</head>
<body>
    {{-- Institutional Footer for all pages --}}
    <div class="footer-institutional">
        Lppm ITSNU Pekalongan - Tahun Akademik {{ $academicYear ?? date('Y') }}<br>
        <span class="page-number">Halaman </span>
    </div>

    <div class="page-break">
        <div style="font-size: 14pt; font-weight: bold; margin-bottom: 20px; text-transform: uppercase; text-align: center;">
            LAPORAN {{ $periodLabel }} {{ $proposal->detailable_type === 'App\Models\Research' ? 'PENELITIAN' : 'PENGABDIAN' }} INTERNAL
        </div>
        
        <div style="margin: 40px 0; text-align: center;">
            @if(get_logo_base64())
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
                    <td style="width: 45%; border: 0.5pt dashed #000; padding: 8px; font-weight: bold;">{{ $submitterFullName }}</td>
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
            FAKULTAS {{ strtoupper($facultyName) }}<br>
            PROGRAM STUDI {{ strtoupper($prodiName) }}<br>
            ITSNU PEKALONGAN<br>
            TAHUN {{ $report->reporting_year }}
        </div>
    </div>

    <div style="page-break-after: always;"></div>

    <table class="header-table no-border">
        <tr>
            <td class="logo" style="width: 60px;">
                @if(get_logo_base64())
                    <img src="{{ get_logo_base64() }}" alt="Logo" style="width: 50px;">
                @endif
            </td>
            <td class="header-text">
                <div>Lembaga Penelitian dan Pengabdian kepada Masyarakat (LPPM)</div>
                <div>Institut Teknologi dan Sains Nahdlatul Ulama (ITSNU) Pekalongan</div>
                <div style="font-weight: normal; font-size: 8pt;">Jl. Karangdowo No. 9, Karangdowo, Kec. Kedungwuni, Kab. Pekalongan, Jawa Tengah 51173</div>
                <div style="font-weight: normal; font-size: 8pt;">Email: lppmitsnupkl@gmail.com | Website: https://lppm.itsnupekalongan.ac.id/</div>
            </td>
        </tr>
    </table>

    <div class="report-type-box">
        LAPORAN {{ $report->reporting_period === 'final' ? 'AKHIR' : 'KEMAJUAN' }}
        {{ $proposal->detailable_type === 'App\Models\Research' ? 'PENELITIAN' : 'PENGABDIAN' }}
        {{ $report->reporting_year }}
    </div>

    <div style="text-align: center; margin-bottom: 15px; font-size: 9pt;">
        ID Proposal: {{ $proposal->id }} | Periode: {{ strtoupper($report->reporting_period) }}<br>
        Rencana Pelaksanaan {{ $proposal->detailable_type === 'App\Models\Research' ? 'Penelitian' : 'Pengabdian' }} : tahun {{ $proposal->start_year }} s.d. tahun {{ $proposal->start_year + $proposal->duration_in_years - 1 }}
    </div>

    @php
        $sectionNum = 1;
        $submitterFullName = format_name(
            $proposal->submitter->identity?->title_prefix ?? '',
            $proposal->submitter->name,
            $proposal->submitter->identity?->title_suffix ?? ''
        );
    @endphp

    {{-- 1. JUDUL --}}
    <div class="section-title">{{ $sectionNum++ }}. JUDUL {{ $proposal->detailable_type === 'App\Models\Research' ? 'PENELITIAN' : 'PENGABDIAN' }}</div>
    <div class="title-border-box">{{ clean_proposal_title($proposal->title) }}</div>

    <table>
        <thead>
            <tr>
                <th>Kelompok Skema</th>
                <th>Ruang Lingkup</th>
                <th>Bidang Fokus</th>
                <th>Lama Kegiatan</th>
                <th>Tahun Pertama Usulan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">{{ $proposal->researchScheme->name ?? '-' }}</td>
                <td class="text-center">
                    @if($proposal->detailable_type === 'App\Models\Research')
                        Penelitian
                    @else
                        Pemberdayaan Kemitraan Masyarakat
                    @endif
                </td>
                <td class="text-center">{{ $proposal->focusArea->name ?? '-' }}</td>
                <td class="text-center">{{ $proposal->duration_in_years }}</td>
                <td class="text-center">{{ $proposal->start_year }}</td>
            </tr>
        </tbody>
    </table>

    <table class="no-border" style="margin-bottom: 15px; font-size: 8.5pt;">
        <tr>
            <td width="150" style="padding: 2px;">Tema {{ $proposal->detailable_type === 'App\Models\Research' ? 'Penelitian' : 'Pengabdian' }}</td>
            <td style="padding: 2px;">: {{ $proposal->theme->name ?? '-' }}</td>
        </tr>
        <tr>
            <td style="padding: 2px;">Topik {{ $proposal->detailable_type === 'App\Models\Research' ? 'Penelitian' : 'Pengabdian' }}</td>
            <td style="padding: 2px;">: {{ $proposal->topic->name ?? '-' }}</td>
        </tr>
        <tr>
            <td style="padding: 2px;">Kata Kunci (Keywords)</td>
            <td style="padding: 2px;">: 
                @if($proposal->keywords && count($proposal->keywords) > 0)
                    {{ implode(', ', $proposal->keywords->pluck('name')->toArray()) }}
                @else
                    -
                @endif
            </td>
        </tr>
        @if($proposal->detailable_type === 'App\Models\Research')
            <tr>
                <td style="padding: 2px;">Jenis TKT</td>
                <td style="padding: 2px;">: {{ $proposal->detailable->tkt_type ?? '-' }}</td>
            </tr>
        @endif
    </table>

    {{-- 2. IDENTITAS PENGUSUL --}}
    <div class="section-title">{{ $sectionNum++ }}. IDENTITAS PENGUSUL</div>
    <table>
        <thead>
            <tr>
                <th width="20%">Nama, Peran</th>
                <th width="15%">Institusi</th>
                <th width="15%">Program Studi</th>
                <th width="15%">Bidang Tugas</th>
                <th width="10%">ID Sinta</th>
                <th width="10%">H-Index</th>
                <th width="15%">Rumpun Ilmu</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><span class="font-bold">{{ strtoupper($submitterFullName) }}</span><br>Ketua Pengusul</td>
                <td>{{ $proposal->submitter->identity?->institution?->name ?? '-' }}</td>
                <td>{{ $proposal->submitter->identity?->studyProgram->name ?? '-' }}</td>
                <td>{{ $proposal->teamMembers->firstWhere('id', $proposal->submitter_id)->pivot->tasks ?? '-' }}</td>
                <td class="text-center">{{ $proposal->submitter->identity?->sinta_id ?? '-' }}</td>
                <td class="text-center">{{ $proposal->submitter->identity?->scopus_h_index ?? '-' }}</td>
                <td>{{ $proposal->submitter->identity?->scienceCluster?->name ?? $proposal->clusterLevel1->name ?? '-' }}</td>
            </tr>
            @php
                $lecturerMembersSection2 = $proposal->teamMembers->filter(fn($m) => $m->id !== $proposal->submitter_id && ($m->identity?->type === 'dosen' || $m->pivot->role === 'anggota' || $m->pivot->role === 'dosen'));
            @endphp
            @foreach($lecturerMembersSection2 as $member)
                <tr>
                    <td><span class="font-bold">{{ strtoupper(format_name($member->identity?->title_prefix ?? '', $member->name, $member->identity?->title_suffix ?? '')) }}</span><br>Anggota Pelaksana</td>
                    <td>{{ $member->identity?->institution?->name ?? 'ITSNU Pekalongan' }}</td>
                    <td>{{ $member->identity?->studyProgram?->name ?? '-' }}</td>
                    <td>{{ $member->pivot->tasks ?? '-' }}</td>
                    <td class="text-center">{{ $member->identity?->sinta_id ?? '-' }}</td>
                    <td class="text-center">{{ $member->identity?->scopus_h_index ?? '-' }}</td>
                    <td>{{ $member->identity?->scienceCluster?->name ?? $proposal->clusterLevel1->name ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- 3. IDENTITAS MAHASISWA --}}
    <div class="section-title">{{ $sectionNum++ }}. IDENTITAS MAHASISWA</div>
    @php 
        $mahasiswaRelation = $proposal->teamMembers->filter(fn($m) => ($m->identity?->type === 'mahasiswa' || $m->pivot?->role === 'mahasiswa'));
        $mahasiswaJson = [];
        if (!empty($proposal->student_members)) {
            $decoded = is_string($proposal->student_members) ? json_decode($proposal->student_members, true) : $proposal->student_members;
            if (is_array($decoded)) {
                $mahasiswaJson = $decoded;
            }
        }
        $hasStudents = $mahasiswaRelation->count() > 0 || count($mahasiswaJson) > 0;
    @endphp
    
    @if($hasStudents)
    <table>
        <thead>
            <tr>
                <th>Nama Anggota</th>
                <th>NIM</th>
                <th>Program Studi</th>
                <th>Tugas Dalam {{ $proposal->detailable_type === 'App\Models\Research' ? 'Penelitian' : 'Pengabdian' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mahasiswaRelation as $member)
                <tr>
                    <td>{{ strtoupper($member->name) }}</td>
                    <td>{{ $member->identity?->identity_id ?? '-' }}</td>
                    <td>{{ $member->identity?->studyProgram?->name ?? '-' }}</td>
                    <td>{{ $member->pivot->tasks ?? '-' }}</td>
                </tr>
            @endforeach
            @foreach($mahasiswaJson as $student)
                <tr>
                    <td>{{ strtoupper($student['name'] ?? '-') }}</td>
                    <td>{{ $student['identifier'] ?? '-' }}</td>
                    <td>{{ $student['study_program'] ?? ($student['prodi'] ?? '-') }}</td>
                    <td>{{ $student['tasks'] ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div style="margin-left: 20px; border: 1px dashed #ccc; padding: 10px; color: #666; font-style: italic;">Tidak ada anggota mahasiswa dalam usulan ini.</div>
    @endif

    {{-- 4. MITRA KERJASAMA --}}
    @if($proposal->partners->count() > 0)
    <div class="section-title">{{ $sectionNum++ }}. MITRA KERJASAMA</div>
    @foreach($proposal->partners as $index => $partner)
    <div style="margin-bottom: 5px;">
        <strong>Mitra Sasaran {{ $index + 1 }}</strong>
        <table class="no-border" style="margin-left: 15px; margin-bottom: 5px;">
            <tr><td width="150" style="padding: 1px;">Jenis Mitra</td><td style="padding: 1px;">: {{ $partner->type ?? '-' }}</td></tr>
            <tr><td style="padding: 1px;">Nama Mitra Sasaran</td><td style="padding: 1px;">: {{ $partner->name }}</td></tr>
            <tr><td style="padding: 1px;">Institusi</td><td style="padding: 1px;">: {{ $partner->institution ?? '-' }}</td></tr>
            <tr><td style="padding: 1px;">Alamat Lengkap</td><td style="padding: 1px;">: {{ $partner->address ?? '-' }}</td></tr>
        </table>
    </div>
    @endforeach
    @if($proposal->detailable_type === 'App\Models\CommunityService')
        <div style="margin-top: 10px;">
            <strong>Ringkasan Permasalahan Mitra:</strong>
            <div style="margin-left: 20px; text-align: justify; margin-bottom: 5px;">{{ $proposal->detailable->partner_issue_summary ?? '-' }}</div>
            <strong>Solusi yang Ditawarkan:</strong>
            <div style="margin-left: 20px; text-align: justify; margin-bottom: 5px;">{{ $proposal->detailable->solution_offered ?? '-' }}</div>
        </div>
    @endif
    @endif

    {{-- 5. Asta Cita --}}
    @if(isset($proposal->asta_cita) && $proposal->asta_cita)
    <div class="section-title">{{ $sectionNum++ }}. Asta Cita</div>
    <div style="margin-left: 20px; text-align: justify;">{{ $proposal->asta_cita }}</div>
    @endif

    {{-- 6. SDGs --}}
    @if(isset($proposal->sdgs) && $proposal->sdgs->count() > 0)
    <div class="section-title">{{ $sectionNum++ }}. Sustainable Development Goals (SDGs)</div>
    <div style="margin-left: 20px; text-align: justify;">
        @foreach($proposal->sdgs as $sdg)
            <div>{{ trim($sdg->name) }}</div>
        @endforeach
    </div>
    @endif

    {{-- 7. LUARAN DIJANJIKAN --}}
    @if($proposal->outputs->count() > 0)
    <div class="section-title">{{ $sectionNum++ }}. LUARAN DIJANJIKAN</div>
    <table>
        <thead>
            <tr>
                <th>Tahun</th>
                <th>Kelompok Luaran</th>
                <th>Jenis Luaran</th>
                <th>Status Target</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($proposal->outputs as $output)
            <tr>
                <td class="text-center">{{ $output->output_year }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $output->group)) }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $output->type)) }}</td>
                <td class="text-center">{{ $output->target_status }}</td>
                <td>{{ $output->description ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- 8. Dokumen Pendukung --}}
    @php
        $supportingDocs = [];
        // Proposal documents
        if ($proposal->detailable?->hasMedia('substance_file')) {
            $supportingDocs[] = ['name' => 'Substansi Usulan (Proposal)', 'file' => $proposal->detailable->getFirstMedia('substance_file')];
        }
        
        // Report documents
        if ($report->hasMedia('substance_file')) {
            $supportingDocs[] = ['name' => 'Substansi ' . ($report->reporting_period === 'final' ? 'Laporan Akhir' : 'Laporan Kemajuan'), 'file' => $report->getFirstMedia('substance_file')];
        }
        if ($report->hasMedia('realization_file')) {
            $supportingDocs[] = ['name' => 'Realisasi Keterlibatan', 'file' => $report->getFirstMedia('realization_file')];
        }
        if ($report->hasMedia('presentation_file')) {
            $supportingDocs[] = ['name' => 'Presentasi Hasil', 'file' => $report->getFirstMedia('presentation_file')];
        }

        // Output documents
        foreach($report->mandatoryOutputs as $mo) {
            $collections = ['journal_article', 'book_document', 'publication_certificate'];
            foreach($collections as $col) {
                if ($mo->hasMedia($col)) {
                    $supportingDocs[] = ['name' => 'Luaran Wajib: ' . ($mo->proposalOutput->type ?? 'Output') . ' - ' . $mo->getFirstMedia($col)->name, 'file' => $mo->getFirstMedia($col)];
                }
            }
        }
        foreach($report->additionalOutputs as $ao) {
            $collections = ['journal_article', 'book_document', 'publication_certificate'];
            foreach($collections as $col) {
                if ($ao->hasMedia($col)) {
                    $supportingDocs[] = ['name' => 'Luaran Tambahan: ' . ($ao->proposalOutput->type ?? 'Output') . ' - ' . $ao->getFirstMedia($col)->name, 'file' => $ao->getFirstMedia($col)];
                }
            }
        }
    @endphp
    @if(count($supportingDocs) > 0)
    <div class="section-title">{{ $sectionNum++ }}. Dokumen Pendukung (Terlampir)</div>
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="75%">Nama Data Pendukung</th>
                <th width="20%">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($supportingDocs as $index => $doc)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $doc['name'] }}</td>
                <td class="text-center">Terlampir</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- 9. Dokumen Pendukung Lainnya --}}
    @php
        $otherDocs = [];
        foreach($proposal->partners as $partner) {
            if ($partner->hasMedia('commitment_letter')) {
                $otherDocs[] = ['name' => 'Surat Pernyataan Kerjasama Mitra - ' . $partner->name, 'file' => $partner->getFirstMedia('commitment_letter')];
            }
        }
    @endphp
    @if(count($otherDocs) > 0)
    <div class="section-title">{{ $sectionNum++ }}. Dokumen Pendukung Lainnya</div>
    <table>
        <thead>
            <tr>
                <th>Kategori</th>
                <th>Nama Mitra</th>
                <th>File</th>
            </tr>
        </thead>
        <tbody>
            @foreach($otherDocs as $doc)
            <tr>
                <td>Surat Pernyataan Kerjasama</td>
                <td>{{ str_replace('Surat Pernyataan Kerjasama Mitra - ', '', $doc['name']) }}</td>
                <td>Terlampir</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- 10. ANGGARAN --}}
    <div class="section-title">{{ $sectionNum++ }}. ANGGARAN</div>
    <p class="mb-0" style="font-size: 8pt;">Rencana Anggaran Biaya {{ $proposal->detailable_type === 'App\Models\Research' ? 'Penelitian' : 'Pengabdian' }} mengacu pada PMK dan buku Panduan Penelitian dan Pengabdian kepada Masyarakat yang berlaku.</p>
    @php
        $totalRAB = $proposal->budgetItems->sum('total_price');
        $budgetGroups = $proposal->budgetItems->groupBy(function($item) {
            return $item->budgetGroup->name ?? ($item->group ?? 'Lainnya');
        });
    @endphp
    <p class="mt-0"><strong>Total RAB : Rp. {{ number_format($totalRAB, 0, ',', '.') }}</strong></p>

    @foreach($budgetGroups as $groupName => $items)
        @php $groupTotal = $items->sum('total_price'); @endphp
        <div class="group-total">
            Total Biaya {{ $groupName }} Rp. {{ number_format($groupTotal, 0, ',', '.') }} 
            ({{ $totalRAB > 0 ? number_format(($groupTotal / $totalRAB) * 100, 2) : 0 }}%)
        </div>
        <table>
            <thead>
                <tr>
                    <th width="20%">Komponen</th>
                    <th width="35%">Item</th>
                    <th width="10%">Satuan</th>
                    <th width="5%">Vol.</th>
                    <th width="15%">Biaya Satuan</th>
                    <th width="15%">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item->budgetComponent->name ?? $item->component }}</td>
                    <td>{{ $item->item_description ?? $item->item_name }}</td>
                    <td class="text-center">{{ $item->budgetComponent->unit ?? ($item->unit ?? '-') }}</td>
                    <td class="text-center">{{ $item->volume }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item->total_price, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    {{-- REPORT DETAILS --}}
    <div style="margin-top: 30px; border-top: 2px dashed #000; padding-top: 20px;"></div>

    <div class="section-title">{{ $sectionNum++ }}. RINGKASAN {{ $report->reporting_period === 'final' ? 'AKHIR' : 'KEMAJUAN' }}</div>
    <div class="text-justify" style="margin-bottom: 15px; border: 1px solid #eee; padding: 10px; font-size: 9pt; line-height: 1.4;">
        {!! nl2br(e($report->summary_update)) !!}
    </div>

    <div class="section-title">{{ $sectionNum++ }}. CAPAIAN LUARAN WAJIB</div>
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="20%">Jenis Luaran</th>
                <th width="15%">Status Saat Ini</th>
                <th width="60%">Keterangan / Detail Capaian</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report->mandatoryOutputs as $index => $mo)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $mo->proposalOutput->type ?? '-' }}</td>
                    <td class="text-center">{{ ucfirst($mo->status_type) }}</td>
                    <td>
                        @if($mo->article_title) <strong>Judul:</strong> {{ $mo->article_title }}<br> @endif
                        @if($mo->book_title) <strong>Judul Buku:</strong> {{ $mo->book_title }}<br> @endif
                        @if($mo->product_name) <strong>Nama Produk:</strong> {{ $mo->product_name }}<br> @endif
                        @if($mo->journal_title) <strong>Jurnal/Penerbit:</strong> {{ $mo->journal_title }} @if($mo->volume) (Vol {{ $mo->volume }}) @endif<br> @endif
                        @if($mo->article_url) <strong>Tautan:</strong> {{ $mo->article_url }}<br> @endif
                        @if($mo->description) <strong>Deskripsi:</strong> {{ $mo->description }}<br> @endif
                        @if(!$mo->article_title && !$mo->book_title && !$mo->product_name && !$mo->description)
                            <em>Detail belum dilengkapi</em>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">Belum ada capaian luaran wajib dilaporkan</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($report->additionalOutputs->count() > 0)
        <div class="section-title">{{ $sectionNum++ }}. CAPAIAN LUARAN TAMBAHAN</div>
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="20%">Jenis Luaran</th>
                    <th width="15%">Status Saat Ini</th>
                    <th width="60%">Keterangan / Detail Capaian</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report->additionalOutputs as $index => $ao)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $ao->proposalOutput->type ?? 'Luaran Tambahan' }}</td>
                        <td class="text-center">{{ ucfirst($ao->status_type ?? $ao->status ?? '') }}</td>
                        <td>
                            @if($ao->article_title) <strong>Judul:</strong> {{ $ao->article_title }}<br> @endif
                            @if($ao->book_title) <strong>Judul Buku:</strong> {{ $ao->book_title }}<br> @endif
                            @if($ao->product_name) <strong>Nama Produk:</strong> {{ $ao->product_name }}<br> @endif
                            @if($ao->journal_title) <strong>Jurnal/Penerbit:</strong> {{ $ao->journal_title }} @if($ao->volume) (Vol {{ $ao->volume }}) @endif<br> @endif
                            @if($ao->article_url) <strong>Tautan:</strong> {{ $ao->article_url }}<br> @endif
                            @if($ao->description) <strong>Deskripsi:</strong> {{ $ao->description }}<br> @endif
                            @if(!$ao->article_title && !$ao->book_title && !$ao->product_name && !$ao->description)
                                <em>Detail belum dilengkapi</em>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- HALAMAN PENGESAHAN LAPORAN --}}
    @if(isset($report_approval_mode) && ($report_approval_mode === 'digital' || $report_approval_mode === 'both'))
        <div class="page-break"></div>
        <div style="text-align: center; font-weight: bold; font-size: 11pt; margin-bottom: 20px; text-transform: uppercase;">
            HALAMAN PENGESAHAN LAPORAN {{ $report->reporting_period === 'final' ? 'AKHIR' : 'KEMAJUAN' }}
            {{ $proposal->detailable_type === 'App\Models\Research' ? 'PENELITIAN' : 'PENGABDIAN' }}
        </div>

        <table style="width: 100%; border-collapse: collapse; font-size: 9pt;">
            <tr>
                <td width="5%" class="text-center">1.</td>
                <td width="35%">Judul {{ $proposal->detailable_type === 'App\Models\Research' ? 'Penelitian' : 'Pengabdian' }}</td>
                <td width="60%">{{ $proposal->title }}</td>
            </tr>
            <tr>
                <td class="text-center">2.</td>
                <td>Rumpun Ilmu</td>
                <td>{{ $proposal->researchScheme->name ?? '-' }} / {{ $proposal->focusArea->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="text-center">3.</td>
                <td colspan="2">Ketua {{ $proposal->detailable_type === 'App\Models\Research' ? 'Peneliti' : 'Pelaksana' }}</td>
            </tr>
            <tr>
                <td></td>
                <td style="padding-left: 20px;">a. Nama Lengkap</td>
                <td>{{ $submitterFullName }}</td>
            </tr>
            <tr>
                <td></td>
                <td style="padding-left: 20px;">b. NIDN/ NIDK</td>
                <td>{{ $proposal->submitter->identity?->identity_id ?? '-' }}</td>
            </tr>
            <tr>
                <td></td>
                <td style="padding-left: 20px;">c. Jabatan Fungsional</td>
                <td>{{ $proposal->submitter->identity?->functional_position ?? '-' }}</td>
            </tr>
            <tr>
                <td></td>
                <td style="padding-left: 20px;">d. Program Studi</td>
                <td>{{ $proposal->submitter->identity?->studyProgram->name ?? '-' }}</td>
            </tr>
            <tr>
                <td></td>
                <td style="padding-left: 20px;">e. Nomor HP</td>
                <td>{{ $proposal->submitter->phone_number ?? '-' }}</td>
            </tr>
            <tr>
                <td></td>
                <td style="padding-left: 20px;">f. Alamat surel (e-mail)</td>
                <td>{{ $proposal->submitter->email ?? '-' }}</td>
            </tr>

            @php
                $lecturerMembers = $proposal->teamMembers->filter(fn($m) => $m->id !== $proposal->submitter_id && ($m->identity?->type === 'dosen' || $m->pivot->role === 'anggota' || $m->pivot->role === 'dosen'));
                $memberCount = 0;
            @endphp

            @foreach($lecturerMembers as $member)
                @php $memberCount++; @endphp
                <tr>
                    <td class="text-center">{{ 3 + $memberCount }}.</td>
                    <td colspan="2">Anggota {{ $proposal->detailable_type === 'App\Models\Research' ? 'Peneliti' : 'Pelaksana' }} {{ $lecturerMembers->count() > 1 ? to_roman($memberCount) : '' }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td style="padding-left: 20px;">a. Nama Lengkap</td>
                    <td>{{ format_name($member->identity?->title_prefix ?? '', $member->name, $member->identity?->title_suffix ?? '') }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td style="padding-left: 20px;">b. NIDN/ NIDK</td>
                    <td>{{ $member->identity?->identity_id ?? '-' }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td style="padding-left: 20px;">c. Perguruan Tinggi</td>
                    <td>{{ $member->identity?->institution->name ?? 'ITSNU Pekalongan' }}</td>
                </tr>
            @endforeach

            @php
                $nextNum = 4 + $lecturerMembers->count();
            @endphp

            <tr>
                <td class="text-center">{{ $nextNum }}.</td>
                <td>Biaya Laporan {{ $report->reporting_period === 'final' ? 'Akhir' : 'Kemajuan' }}</td>
                <td>Rp {{ number_format($totalRAB, 0, ',', '.') }}</td>
            </tr>
        </table>

        <div style="page-break-inside: avoid;">
            {{-- Row 1: Dekan & Ketua --}}
            <table class="no-border" style="width: 100%; margin-top: 30px;">
                <tr>
                    <td width="50%" class="text-center" style="vertical-align: top;">
                        Mengetahui,<br>
                        Dekan {{ $proposal->submitter->identity?->faculty?->name ?? '.......................' }}
                    </td>
                    <td width="50%" class="text-center" style="vertical-align: top;">
                        Pekalongan, @if(isset($report->updated_at)) {{ $report->updated_at->format('d F Y') }} @else {{ date('d F Y') }} @endif<br>
                        Ketua {{ $proposal->detailable_type === 'App\Models\Research' ? 'Peneliti' : 'Pelaksana' }}
                    </td>
                </tr>
                <tr>
                    <td class="text-center" style="height: 120px; vertical-align: bottom; padding-bottom: 20px;">
                        @if($qrDeanUrl ?? null)
                            <div style="margin-bottom: 5px;">
                                <img src="{{ generate_qr_code_data_uri($qrDeanUrl, 140) }}" width="70">
                            </div>
                            <div style="font-size: 7pt; color: #1a56db; font-weight: bold; margin-bottom: 5px;">DEAN APPROVED</div>
                        @else
                            <div style="height: 70px;"></div>
                        @endif
                        <strong><u>{{ $dean_name ?? '.......................' }}</u></strong><br>
                        NPP. {{ $dean_id ?? '-' }}
                    </td>
                    <td class="text-center" style="height: 120px; vertical-align: bottom; padding-bottom: 20px;">
                        @if($qrLecturerUrl ?? null)
                            <div style="margin-bottom: 5px;">
                                <img src="{{ generate_qr_code_data_uri($qrLecturerUrl, 140) }}" width="70">
                            </div>
                            <div style="font-size: 7pt; color: #555; font-weight: bold; margin-bottom: 5px;">DIGITALLY SIGNED</div>
                        @endif
                        <strong><u>{{ $submitterFullName }}</u></strong><br>
                        NPP. {{ $proposal->submitter->identity?->identity_id ?? '-' }}
                    </td>
                </tr>
            </table>

            {{-- Row 2: Kepala LPPM (Centered) --}}
            <table class="no-border" style="width: 100%;">
                <tr>
                    <td class="text-center" style="vertical-align: top;">
                        Menyetujui,<br>
                        Kepala LPPM ITSNU Pekalongan
                    </td>
                </tr>
                <tr>
                    <td class="text-center" style="height: 120px; vertical-align: bottom;">
                        @if($qrLppmUrl ?? null)
                            <div style="margin-bottom: 5px;">
                                <img src="{{ generate_qr_code_data_uri($qrLppmUrl, 140) }}" width="70">
                            </div>
                            <div style="font-size: 7pt; color: #059669; font-weight: bold; margin-bottom: 5px;">VERIFIED BY LPPM</div>
                        @else
                            <div style="height: 70px;"></div>
                        @endif
                        <strong><u>{{ $lppm_head_name ?? '.......................' }}</u></strong><br>
                        NPP. {{ $lppm_head_id ?? '-' }}
                    </td>
                </tr>
            </table>
        </div>

        <div style="margin-top: 40px; text-align: center; color: #666; font-size: 8pt; border: 1px dashed #ccc; padding: 10px; background-color: #fcfcfc;">
            <div style="font-weight: bold; margin-bottom: 3px; color: #333;">DOKUMEN INI DISAHKAN SECARA DIGITAL</div>
            <div>Sesuai dengan kebijakan LPPM ITSNU Pekalongan, pengesahan laporan dilakukan melalui sistem informasi.</div>
            <div style="margin-top: 3px; font-family: monospace;">ID Laporan: {{ $report->id }} | Dicetak pada: {{ date('Y-m-d H:i:s') }}</div>
        </div>
    @endif
</body>
</html>