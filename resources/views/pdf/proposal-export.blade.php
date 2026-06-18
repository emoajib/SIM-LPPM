<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <meta http-equiv="Expires" content="0"/>
    <title>Proposal Export - {{ $proposal->id }} ({{ $proposal->status->value }})</title>
    @include('pdf.partials.styles')
    @include('pdf.partials.section-styles')
    @php
        $submitterIdentity = $proposal->submitter->identity;
        $submitterFullName = format_name(
            $submitterIdentity?->title_prefix ?? '',
            $proposal->submitter->name,
            $submitterIdentity?->title_suffix ?? ''
        );
        $submitterNidn = $submitterIdentity?->identity_id ?? '-';
        $academicYear = $proposal->start_year . '/' . ((int) $proposal->start_year + 1);
        $facultyName = $submitterIdentity?->faculty?->name ?? '.......................';
        $prodiName = $submitterIdentity?->studyProgram?->name ?? '.......................';
        $institutionName = $submitterIdentity?->institution?->name ?? 'ITSNU Pekalongan';
        $lecturerSig = $proposal->signatures->first(fn($s) => $s->signed_role === 'lecturer' && strtolower($s->action) === 'submitted');
        $statusValue = $proposal->status->value;
        $totalRAB = $proposal->budgetItems->sum('total_price');
    @endphp
</head>
<body>
    @include('pdf.partials.section-footer')

    @include('pdf.partials.cover', [
        'coverTitle' => 'PROPOSAL '.($proposal->detailable_type === 'App\Models\Research' ? 'PENELITIAN' : 'PENGABDIAN').' INTERNAL',
        'coverYear' => $proposal->start_year,
        'proposal' => $proposal,
        'submitterFullName' => $submitterFullName,
        'submitterNidn' => $submitterNidn,
        'facultyName' => $facultyName,
        'prodiName' => $prodiName,
    ])

    @if($proposal_approval_mode === 'digital' || $proposal_approval_mode === 'both')
        <div class="page-break">
            <div style="text-align: center; font-weight: bold; font-size: 12pt; color: #1a4d2e; margin-bottom: 25px; text-transform: uppercase;">
                HALAMAN PERSETUJUAN PROPOSAL {{ $proposal->detailable_type === 'App\Models\Research' ? 'PENELITIAN' : 'PENGABDIAN' }}
            </div>

            <table class="table-data" style="width: 100%;">
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
                    <td colspan="2" class="font-bold">Ketua {{ $proposal->detailable_type === 'App\Models\Research' ? 'Peneliti' : 'Pelaksana' }}</td>
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
                        <td colspan="2" class="font-bold">Anggota {{ $proposal->detailable_type === 'App\Models\Research' ? 'Peneliti' : 'Pelaksana' }} {{ $lecturerMembers->count() > 1 ? to_roman($memberCount) : '' }}</td>
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
                        <td>{{ $member->identity?->institution?->name ?? 'ITSNU Pekalongan' }}</td>
                    </tr>
                @endforeach

                @php
                    $studentMembers = $proposal->teamMembers->filter(fn($m) => $m->identity?->type === 'mahasiswa' || $m->pivot->role === 'mahasiswa');

                    if (!empty($proposal->student_members)) {
                        $rawJson = is_string($proposal->student_members) ? json_decode($proposal->student_members, true) : $proposal->student_members;
                        if (is_array($rawJson)) {
                            foreach ($rawJson as $jm) {
                                $dummy = new \stdClass();
                                $dummy->name = $jm['name'];
                                $dummy->identity = new \stdClass();
                                $dummy->identity->identity_id = $jm['identifier'] ?? '-';
                                $dummy->identity->studyProgram = new \stdClass();
                                $dummy->identity->studyProgram->name = $jm['study_program'] ?? ($jm['prodi'] ?? '-');
                                $dummy->identity->institution = new \stdClass();
                                $dummy->identity->institution->name = $jm['institution'] ?? 'ITSNU Pekalongan';
                                $dummy->pivot = new \stdClass();
                                $dummy->pivot->tasks = $jm['tasks'] ?? '-';

                                $studentMembers->push($dummy);
                            }
                        }
                    }

                    $nextNum = 4 + $lecturerMembers->count();
                @endphp

                <tr>
                    <td class="text-center">{{ $nextNum }}.</td>
                    <td>Nama Mahasiswa</td>
                    <td>
                        @if($studentMembers->count() > 0)
                            <ol style="margin: 0; padding-left: 15px;">
                            @foreach($studentMembers as $student)
                                <li>{{ $student->name }} ({{ $student->identity?->identity_id ?? '-' }})</li>
                            @endforeach
                            </ol>
                        @else
                            -
                        @endif
                    </td>
                </tr>

                <tr>
                    <td class="text-center">{{ $nextNum + 1 }}.</td>
                    <td>Luaran yang dihasilkan</td>
                    <td>
                        @if($proposal->outputs->count() > 0)
                            {{ implode(', ', $proposal->outputs->pluck('type')->unique()->toArray()) }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="text-center">{{ $nextNum + 2 }}.</td>
                    <td>Jangka Waktu Pelaksanaan</td>
                    <td>{{ $proposal->duration_in_years }} Tahun</td>
                </tr>
                <tr>
                    <td class="text-center">{{ $nextNum + 3 }}.</td>
                    <td>Anggaran Biaya</td>
                    <td>Rp {{ number_format($totalRAB, 0, ',', '.') }}</td>
                </tr>
            </table>

            <div style="page-break-inside: avoid; margin-top: 30px;">
                <table class="no-border" style="width: 100%;">
                    <tr>
                        <td width="50%" class="text-center" style="vertical-align: top;">
                            Mengetahui,<br>
                            Dekan Fakultas {{ $proposal->submitter->identity?->faculty?->name ?? '.......................' }}
                        </td>
                        <td width="50%" class="text-center" style="vertical-align: top;">
                            Pekalongan, {{ $lecturerSig && $lecturerSig->signed_at ? $lecturerSig->signed_at->format('d F Y') : date('d F Y') }}<br>
                            Ketua {{ $proposal->detailable_type === 'App\Models\Research' ? 'Peneliti' : 'Pelaksana' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-center" style="height: 120px; vertical-align: bottom; padding-bottom: 20px;">
                            @php
                                $dekanSig = $proposal->signatures->first(fn($s) => $s->signed_role === 'dekan' && strtolower($s->action) === 'approved');
                            @endphp
                            @if($dekanSig && $dekanSig->signed_at)
                                <div style="margin-bottom: 5px;">
                                    <img src="{{ generate_qr_code_data_uri(\Illuminate\Support\Facades\URL::signedRoute('signatures.verify', ['documentSignature' => $dekanSig->id])) }}" width="70">
                                </div>
                                <div style="font-size: 7pt; margin-bottom: 5px;">Disetujui secara digital oleh:<br>{{ $dean_name }}<br>pada {{ $dekanSig->signed_at->format('d-m-Y H:i') }}</div>
                            @else
                                <div style="height: 70px;"></div>
                            @endif
                            <strong><u>{{ $dean_name }}</u></strong><br>
                            NIDN. {{ $dean_id }}
                        </td>
                        <td class="text-center" style="height: 120px; vertical-align: bottom; padding-bottom: 20px;">
                            @if($lecturerSig && $lecturerSig->signed_at && !in_array($statusValue, ['draft', 'revision_needed']))
                                <div style="margin-bottom: 5px;">
                                    <img src="{{ generate_qr_code_data_uri(\Illuminate\Support\Facades\URL::signedRoute('signatures.verify', ['documentSignature' => $lecturerSig->id])) }}" width="70">
                                </div>
                                <div style="font-size: 7pt; margin-bottom: 5px;">Diajukan secara digital oleh:<br>{{ $submitterFullName }}<br>pada {{ $lecturerSig->signed_at->format('d-m-Y H:i') }}</div>
                            @else
                                <div style="height: 70px;"></div>
                            @endif
                            <strong><u>{{ $submitterFullName }}</u></strong><br>
                            NIDN. {{ $proposal->submitter->identity?->identity_id ?? '-' }}
                        </td>
                    </tr>
                </table>

                <table class="no-border" style="width: 100%; margin-top: 20px;">
                    <tr>
                        <td class="text-center" style="vertical-align: top;">
                            Menyetujui,<br>
                            Kepala LPPM ITSNU Pekalongan
                        </td>
                    </tr>
                    <tr>
                        <td class="text-center" style="height: 120px; vertical-align: bottom;">
                            @php
                                $lppmSig = $proposal->signatures->first(fn($s) => $s->signed_role === 'kepala_lppm' && strtolower($s->action) === 'finalized');
                            @endphp
                            @if($lppmSig && $lppmSig->signed_at && $proposal->status === \App\Enums\ProposalStatus::COMPLETED)
                                <div style="margin-bottom: 5px;">
                                    <img src="{{ generate_qr_code_data_uri(\Illuminate\Support\Facades\URL::signedRoute('signatures.verify', ['documentSignature' => $lppmSig->id])) }}" width="70">
                                </div>
                                <div style="font-size: 7pt; margin-bottom: 5px;">Disetujui secara digital oleh:<br>{{ $lppm_head_name }}<br>pada {{ $lppmSig->signed_at->format('d-m-Y H:i') }}</div>
                            @else
                                <div style="height: 70px;"></div>
                            @endif
                            <strong><u>{{ $lppm_head_name }}</u></strong><br>
                            NIDN. {{ $lppm_head_id }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    @endif

    @include('pdf.partials.header')

    @if(!empty($pdfConfig['intro_text'] ?? null))
        <div style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; background: #f9f9f9; text-align: justify; font-size: 9pt;">
            {!! nl2br(e($pdfConfig['intro_text'])) !!}
        </div>
    @endif

    <div class="protection-box">
        <strong>PROTEKSI ISI PROPOSAL</strong><br>
        Dilarang menyalin, menyimpan, memperbanyak sebagian atau seluruh isi proposal ini dalam bentuk apapun<br>
        kecuali oleh pengusul dan pengelola administrasi LPPM ITSNU Pekalongan.
    </div>

    <div class="proposal-type-box">
        PROPOSAL {{ $proposal->detailable_type === 'App\Models\Research' ? 'PENELITIAN' : 'PENGABDIAN' }} {{ $proposal->start_year }}
    </div>

    <div class="proposal-id">
        ID Proposal: {{ $proposal->id }}<br>
        Rencana Pelaksanaan {{ $proposal->detailable_type === 'App\Models\Research' ? 'Penelitian' : 'Pengabdian' }} : tahun {{ $proposal->start_year }} s.d. tahun {{ (int) $proposal->start_year + (int) $proposal->duration_in_years - 1 }}
    </div>

    @php $sectionNum = 1; @endphp

    @include('pdf.partials.section-judul', ['sectionNum' => $sectionNum])
    @php $sectionNum++; @endphp

    @include('pdf.partials.section-identitas-pengusul', ['sectionNum' => $sectionNum])
    @php $sectionNum++; @endphp

    @include('pdf.partials.section-identitas-mahasiswa', ['sectionNum' => $sectionNum])
    @php $sectionNum++; @endphp

    @include('pdf.partials.section-mitra', ['sectionNum' => $sectionNum, 'showFullDetails' => true])
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
                <div>{{ trim($sdg->name) }} : {{ $sdg->description }}</div>
            @endforeach
        </div>
    @endif

    @if(isset($proposal->iku) && $proposal->iku)
        <div class="section-title">{{ $sectionNum++ }}. IKU</div>
        <div style="margin-left: 20px; text-align: justify;">{{ $proposal->iku }}</div>
    @endif

    @include('pdf.partials.section-luaran-dijanjikan', ['sectionNum' => $sectionNum])
    @if($proposal->outputs->count() > 0)
        @php $sectionNum++; @endphp
    @endif

    @include('pdf.partials.section-anggaran', ['sectionNum' => $sectionNum])
    @php $sectionNum++; @endphp

    @php
        $supportingDocs = [];
        if ($proposal->detailable?->hasMedia('substance_file')) {
            $media = $proposal->detailable->getFirstMedia('substance_file');
            $mime = $media->mime_type ?? '';
            $type = str_starts_with($mime, 'image/') ? 'image' : (str_contains($mime, 'pdf') ? 'pdf' : 'other');
            $supportingDocs[] = ['label' => 'Substansi Usulan', 'media' => $media, 'type' => $type];
        }
        if (in_array($proposal_approval_mode, ['upload', 'both']) && $proposal->detailable?->hasMedia('approval_file')) {
            $media = $proposal->detailable->getFirstMedia('approval_file');
            $mime = $media->mime_type ?? '';
            $type = str_starts_with($mime, 'image/') ? 'image' : (str_contains($mime, 'pdf') ? 'pdf' : 'other');
            $supportingDocs[] = ['label' => 'Lembar Pengesahan (Tanda Tangan Basah)', 'media' => $media, 'type' => $type];
        }
    @endphp
    @include('pdf.partials.section-lampiran', [
        'title' => 'Dokumen Pendukung',
        'items' => $supportingDocs,
        'sectionNum' => $sectionNum,
    ])
    @if(count($supportingDocs) > 0)
        @php $sectionNum++; @endphp
    @endif

    @php
        $otherDocs = [];
        foreach ($proposal->partners as $partner) {
            if ($partner->hasMedia('commitment_letter')) {
                $media = $partner->getMedia('commitment_letter')
                    ->where('custom_properties.proposal_id', $proposal->id)
                    ->first();
                if ($media) {
                    $mime = $media->mime_type ?? '';
                    $type = str_starts_with($mime, 'image/') ? 'image' : (str_contains($mime, 'pdf') ? 'pdf' : 'other');
                    $otherDocs[] = ['label' => 'Surat Pernyataan Kerjasama Mitra - ' . $partner->name, 'media' => $media, 'type' => $type];
                }
            }
        }
    @endphp
    @include('pdf.partials.section-lampiran', [
        'title' => 'Dokumen Pendukung Lainnya',
        'items' => $otherDocs,
        'sectionNum' => $sectionNum,
    ])
    @if(count($otherDocs) > 0)
        @php $sectionNum++; @endphp
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
