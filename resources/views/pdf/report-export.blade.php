<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Export - {{ $proposal->id }}</title>
    @include('pdf.partials.styles')
    @include('pdf.partials.section-styles')
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
        $totalRAB = $proposal->budgetItems->sum('total_price');
    @endphp
</head>
<body>
    @include('pdf.partials.section-footer')

    @include('pdf.partials.cover', [
        'coverTitle' => 'LAPORAN '.$periodLabel.' '.($proposal->detailable_type === 'App\Models\Research' ? 'PENELITIAN' : 'PENGABDIAN').' INTERNAL',
        'coverYear' => $report->reporting_year,
        'proposal' => $proposal,
        'submitterFullName' => $submitterFullName,
        'submitterNidn' => $proposal->submitter->identity?->identity_id ?? '-',
        'facultyName' => $facultyName,
        'prodiName' => $prodiName,
    ])

    <div style="page-break-after: always;"></div>

    @include('pdf.partials.header')

    @if(!empty($pdfConfig['intro_text'] ?? null))
        <div style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; background: #f9f9f9; text-align: justify; font-size: 9pt;">
            {!! nl2br(e($pdfConfig['intro_text'])) !!}
        </div>
    @endif

    <div class="report-type-box">
        LAPORAN {{ $report->reporting_period === 'final' ? 'AKHIR' : 'KEMAJUAN' }}
        {{ $proposal->detailable_type === 'App\Models\Research' ? 'PENELITIAN' : 'PENGABDIAN' }}
        {{ $report->reporting_year }}
    </div>

    <div style="text-align: center; margin-bottom: 15px; font-size: 9pt;">
        ID Proposal: {{ $proposal->id }} | Periode: {{ strtoupper($report->reporting_period) }}<br>
        Rencana Pelaksanaan {{ $proposal->detailable_type === 'App\Models\Research' ? 'Penelitian' : 'Pengabdian' }} : tahun {{ $proposal->start_year }} s.d. tahun {{ $proposal->start_year + $proposal->duration_in_years - 1 }}
    </div>

    @php $sectionNum = 1; @endphp

    @include('pdf.partials.section-judul', ['sectionNum' => $sectionNum])
    @php $sectionNum++; @endphp

    @include('pdf.partials.section-identitas-pengusul', ['sectionNum' => $sectionNum])
    @php $sectionNum++; @endphp

    @include('pdf.partials.section-identitas-mahasiswa', ['sectionNum' => $sectionNum])
    @php $sectionNum++; @endphp

    @include('pdf.partials.section-mitra', ['sectionNum' => $sectionNum])
    @if($proposal->partners->count() > 0)
        @php $sectionNum++; @endphp
    @endif

    @include('pdf.partials.section-asta-cita', ['sectionNum' => $sectionNum])
    @if(isset($proposal->asta_cita) && $proposal->asta_cita)
        @php $sectionNum++; @endphp
    @endif

    @if(isset($proposal->sdgs) && $proposal->sdgs->count() > 0)
        <div class="section-title">{{ $sectionNum++ }}. Sustainable Development Goals (SDGs)</div>
        <div style="margin-left: 20px; text-align: justify;">
            @foreach($proposal->sdgs as $sdg)
                <div>{{ trim($sdg->name) }}</div>
            @endforeach
        </div>
    @endif

    @include('pdf.partials.section-luaran-dijanjikan', ['sectionNum' => $sectionNum])
    @if($proposal->outputs->count() > 0)
        @php $sectionNum++; @endphp
    @endif

    @php
        $supportingDocs = [];
        if ($proposal->detailable?->hasMedia('substance_file')) {
            $supportingDocs[] = ['name' => 'Substansi Usulan (Proposal)', 'file' => $proposal->detailable->getFirstMedia('substance_file')];
        }
        if ($report->hasMedia('substance_file')) {
            $supportingDocs[] = ['name' => 'Substansi ' . ($report->reporting_period === 'final' ? 'Laporan Akhir' : 'Laporan Kemajuan'), 'file' => $report->getFirstMedia('substance_file')];
        }
        if ($report->hasMedia('realization_file')) {
            $supportingDocs[] = ['name' => 'Realisasi Keterlibatan', 'file' => $report->getFirstMedia('realization_file')];
        }
        if ($report->hasMedia('presentation_file')) {
            $supportingDocs[] = ['name' => 'Presentasi Hasil', 'file' => $report->getFirstMedia('presentation_file')];
        }
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

    @include('pdf.partials.section-anggaran', ['sectionNum' => $sectionNum])
    @php $sectionNum++; @endphp

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
            <table class="no-border" style="width: 100%; margin-top: 30px;">
                <tr>
                    <td width="50%" class="text-center" style="vertical-align: top;">
                        Mengetahui,<br>
                        Dekan {{ $proposal->submitter->identity?->faculty?->name ?? '.......................' }}
                    </td>
                    <td width="50%" class="text-center" style="vertical-align: top;">
                        Pekalongan, @if(isset($lecturer_signed_at)) {{ \Carbon\Carbon::parse($lecturer_signed_at)->format('d F Y') }} @else {{ date('d F Y') }} @endif<br>
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

    @if(!empty($pdfConfig['approval_custom_text'] ?? null))
        <div style="margin-top: 20px; padding: 10px; border: 1px solid #ddd; background: #f9f9f9; text-align: center; font-size: 9pt;">
            {!! nl2br(e($pdfConfig['approval_custom_text'])) !!}
        </div>
    @endif

    @if(!empty($pdfConfig['outro_text'] ?? null))
        <div style="margin-top: 15px; padding: 10px; border: 1px solid #ddd; background: #f9f9f9; text-align: justify; font-size: 9pt;">
            {!! nl2br(e($pdfConfig['outro_text'])) !!}
        </div>
    @endif
</body>
</html>
