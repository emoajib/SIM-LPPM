<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Lampiran Laporan - {{ $proposal->id }}</title>
    @include('pdf.partials.styles')
    @include('pdf.partials.section-styles')
    @php
        $submitterFullName = format_name(
            $proposal->submitter->identity?->title_prefix ?? '',
            $proposal->submitter->name,
            $proposal->submitter->identity?->title_suffix ?? ''
        );
        $isResearch = $proposal->detailable_type === 'App\Models\Research';
        $totalRAB = $proposal->budgetItems->sum('total_price');
    @endphp
</head>
<body>
    @include('pdf.partials.section-footer')

    @if(($part ?? 'all') === 'l1_l2_l3' || ($part ?? 'all') === 'all')
        {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
        <!-- LAMPIRAN 1: ALOKASI PENGGUNAAN DANA -->
        <div class="section-title" style="font-size: 11pt; font-weight: bold; margin-bottom: 10px;">
            LAMPIRAN 1. ALOKASI PENGGUNAAN DANA
        </div>
        <div style="font-size: 9pt; margin-bottom: 10px; color: #444;">
            Rekapitulasi alokasi dan realisasi penggunaan anggaran {{ $isResearch ? 'penelitian' : 'pengabdian' }} yang disetujui:
        </div>
        <table class="table-data mb-4">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="25%">Kelompok Anggaran</th>
                    <th width="35%">Komponen & Justifikasi</th>
                    <th width="10%">Vol</th>
                    <th width="10%">Satuan</th>
                    <th width="15%">Nominal (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($proposal->budgetItems as $idx => $item)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>{{ $item->budgetGroup->name ?? '-' }}</td>
                        <td>
                            <strong>{{ $item->budgetComponent->name ?? $item->description }}</strong>
                            @if($item->justification)
                                <br><small class="text-muted">{{ $item->justification }}</small>
                            @endif
                        </td>
                        <td class="text-center">{{ $item->volume }}</td>
                        <td class="text-center">{{ $item->unit }}</td>
                        <td class="text-right">Rp {{ number_format($item->total_price, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">Belum ada rincian alokasi penggunaan dana</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5" class="text-right">Total Realisasi Anggaran:</th>
                    <th class="text-right">Rp {{ number_format($totalRAB, 0, ',', '.') }}</th>
                </tr>
            </tfoot>
        </table>

        <!-- LAMPIRAN 2: BIODATA TIM PENELITI / PENGUSUL -->
        <div class="section-title" style="font-size: 11pt; font-weight: bold; margin-top: 25px; margin-bottom: 10px; page-break-before: always;">
            LAMPIRAN 2. FORMAT BIODATA KETUA DAN ANGGOTA TIM PENGUSUL (DOSEN & MAHASISWA)
        </div>
        
        <!-- A. Biodata Ketua Tim -->
        <div style="font-size: 10pt; font-weight: bold; margin-bottom: 5px; color: #1a56db;">A. Biodata Ketua {{ $isResearch ? 'Peneliti' : 'Pelaksana' }}</div>
        <table class="table-data mb-3" style="width: 100%;">
            <tr>
                <td width="5%" class="text-center">1.</td>
                <td width="35%">Nama Lengkap (dengan gelar)</td>
                <td width="60%"><strong>{{ $submitterFullName }}</strong></td>
            </tr>
            <tr>
                <td class="text-center">2.</td>
                <td>NIDN / NIDK / NIP</td>
                <td>{{ $proposal->submitter->identity?->identity_id ?? '-' }}</td>
            </tr>
            <tr>
                <td class="text-center">3.</td>
                <td>Jabatan Fungsional</td>
                <td>{{ $proposal->submitter->identity?->functional_position ?? '-' }}</td>
            </tr>
            <tr>
                <td class="text-center">4.</td>
                <td>Program Studi / Jurusan</td>
                <td>{{ $proposal->submitter->identity?->studyProgram?->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="text-center">5.</td>
                <td>Perguruan Tinggi</td>
                <td>{{ $proposal->submitter->identity?->institution?->name ?? 'ITSNU Pekalongan' }}</td>
            </tr>
            <tr>
                <td class="text-center">6.</td>
                <td>Alamat Surel (E-mail)</td>
                <td>{{ $proposal->submitter->email ?? '-' }}</td>
            </tr>
            <tr>
                <td class="text-center">7.</td>
                <td>Nomor Telepon / HP</td>
                <td>{{ $proposal->submitter->phone_number ?? '-' }}</td>
            </tr>
            <tr>
                <td class="text-center">8.</td>
                <td>Tugas Dalam {{ $isResearch ? 'Penelitian' : 'Pengabdian' }}</td>
                <td>{{ $proposal->teamMembers->firstWhere('id', $proposal->submitter_id)?->pivot?->tasks ?? 'Ketua Pelaksana / Penanggung Jawab Kegiatan' }}</td>
            </tr>
        </table>

        <!-- B. Biodata Anggota Dosen -->
        @php
            $lecturerMembers = $proposal->teamMembers->filter(fn($m) => $m->id !== $proposal->submitter_id && ($m->identity?->type === 'dosen' || $m->pivot->role === 'anggota' || $m->pivot->role === 'dosen'));
        @endphp
        @if($lecturerMembers->count() > 0)
            <div style="font-size: 10pt; font-weight: bold; margin-top: 15px; margin-bottom: 5px; color: #1a56db;">B. Biodata Anggota Dosen</div>
            @foreach($lecturerMembers as $mIdx => $member)
                <div style="font-size: 9pt; font-weight: bold; margin-top: 5px; margin-bottom: 3px;">Anggota Dosen {{ $mIdx + 1 }}</div>
                <table class="table-data mb-2" style="width: 100%;">
                    <tr>
                        <td width="5%" class="text-center">1.</td>
                        <td width="35%">Nama Lengkap (dengan gelar)</td>
                        <td width="60%"><strong>{{ format_name($member->identity?->title_prefix ?? '', $member->name, $member->identity?->title_suffix ?? '') }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-center">2.</td>
                        <td>NIDN / NIDK</td>
                        <td>{{ $member->identity?->identity_id ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-center">3.</td>
                        <td>Program Studi / Instansi</td>
                        <td>{{ $member->identity?->studyProgram?->name ?? '-' }} ({{ $member->identity?->institution?->name ?? 'ITSNU Pekalongan' }})</td>
                    </tr>
                    <tr>
                        <td class="text-center">4.</td>
                        <td>Tugas Dalam Kegiatan</td>
                        <td>{{ $member->pivot->tasks ?? 'Anggota Tim' }}</td>
                    </tr>
                </table>
            @endforeach
        @endif

        <!-- C. Biodata Anggota Mahasiswa -->
        @php
            $students = is_array($proposal->student_members) ? $proposal->student_members : json_decode($proposal->student_members ?? '[]', true);
        @endphp
        @if(!empty($students))
            <div style="font-size: 10pt; font-weight: bold; margin-top: 15px; margin-bottom: 5px; color: #1a56db;">C. Biodata Anggota Mahasiswa</div>
            <table class="table-data mb-3">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="30%">Nama Mahasiswa</th>
                        <th width="15%">NIM / NPM</th>
                        <th width="25%">Program Studi</th>
                        <th width="25%">Tugas Dalam Kegiatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $sIdx => $st)
                        <tr>
                            <td class="text-center">{{ $sIdx + 1 }}</td>
                            <td><strong>{{ $st['name'] ?? '-' }}</strong></td>
                            <td class="text-center">{{ $st['identifier'] ?? '-' }}</td>
                            <td>{{ $st['study_program'] ?? '-' }}</td>
                            <td>{{ $st['tasks'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- LAMPIRAN 3: JADWAL PENELITIAN -->
        @if($isResearch)
            <div class="section-title" style="font-size: 11pt; font-weight: bold; margin-top: 25px; margin-bottom: 10px; page-break-before: always;">
                LAMPIRAN 3. JADWAL PENELITIAN
            </div>
            <div style="font-size: 9pt; margin-bottom: 10px; color: #444;">
                Matriks jadwal pelaksanaan tahapan kegiatan penelitian:
            </div>
            @php
                $schedules = $proposal->research?->schedules ?? collect();
            @endphp
            <table class="table-data mb-4">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="45%">Nama Kegiatan / Tahapan</th>
                        <th width="25%">Tahun Pelaksanaan</th>
                        <th width="25%">Durasi (Bulan)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $scIdx => $sch)
                        <tr>
                            <td class="text-center">{{ $scIdx + 1 }}</td>
                            <td>{{ $sch->activity_name }}</td>
                            <td class="text-center">Tahun {{ $sch->year ?? 1 }}</td>
                            <td class="text-center">Bulan {{ $sch->start_month }} s.d. Bulan {{ $sch->end_month }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">Jadwal pelaksanaan penelitian mengikuti matriks usulan proposal.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @endif
    @endif

    @if(($part ?? 'all') === 'logbook' || ($part ?? 'all') === 'all')
        <!-- LAMPIRAN 6: LOGBOOK KEGIATAN -->
        @php
            $logbooks = $proposal->dailyNotes()->orderBy('activity_date', 'asc')->get();
        @endphp
        <div class="section-title" style="font-size: 11pt; font-weight: bold; margin-top: 25px; margin-bottom: 10px; page-break-before: always;">
            LAMPIRAN {{ $isResearch ? '6' : '13' }}. LOGBOOK KEGIATAN
        </div>
        <div style="font-size: 9pt; margin-bottom: 10px; color: #444;">
            Catatan harian aktivitas pelaksanaan {{ $isResearch ? 'penelitian' : 'pengabdian' }}:
        </div>
        <table class="table-data">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="12%">Tanggal</th>
                    <th width="40%">Aktivitas & Catatan Kegiatan</th>
                    <th width="18%">Kelompok RAB</th>
                    <th width="15%">Nominal (Rp)</th>
                    <th width="10%">Progres</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logbooks as $index => $note)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="text-center">{{ $note->activity_date->format('d/m/Y') }}</td>
                        <td class="text-justify">
                            <div class="font-bold" style="line-height: 1.4;">{{ $note->activity_description }}</div>
                            @if ($note->notes)
                                <div style="margin-top: 4px; font-style: italic; color: #444; font-size: 8pt; line-height: 1.3;">
                                    Catatan: {{ $note->notes }}
                                </div>
                            @endif
                        </td>
                        <td class="text-center">{{ $note->budgetGroup->name ?? '-' }}</td>
                        <td class="text-right">{{ $note->amount ? number_format($note->amount, 0, ',', '.') : '-' }}</td>
                        <td class="text-center">{{ $note->progress_percentage }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">Belum ada catatan harian logbook dilaporkan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif
</body>
</html>
