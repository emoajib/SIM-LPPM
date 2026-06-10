{{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
@php
    $requiredReviewers = (int) \App\Models\Setting::get('reviewer_count_required', 2);
    $totalCols = max(8, 5 + $requiredReviewers);
@endphp
<table>
    <thead>
        <tr>
            <th colspan="{{ $totalCols }}" style="text-align: center; font-size: 16pt; font-weight: bold;">
                LAPORAN PENUGASAN &amp; REVIEWER LPPM TAHUN {{ $period }}
            </th>
        </tr>
        <tr>
            <th colspan="{{ $totalCols }}" style="text-align: center; font-size: 10pt; color: gray;">
                Dicetak pada: {{ now()->format('d-m-Y H:i:s') }}
            </th>
        </tr>
        <tr></tr>
        
        <!-- SECTION 1: IKHTISAR PENUGASAN -->
        <tr>
            <th colspan="{{ $totalCols }}" style="font-size: 12pt; font-weight: bold; background-color: #f2f2f2;">
                I. IKHTISAR PENUGASAN REVIEWER
            </th>
        </tr>
        <tr>
            <th style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2;">No</th>
            <th style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2;">Judul Proposal</th>
            <th style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2;">Dosen Pengaju</th>
            <th style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2;">Fakultas / Prodi</th>
            <th style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2;">Jenis</th>
            <th style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2;">Reviewer Ditugaskan</th>
            <th style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2;">Status Penugasan</th>
        </tr>
    </thead>
    <tbody>
        @foreach($proposals as $index => $proposal)
            @php
                $identity = $proposal->submitter->identity;
                $revNames = $proposal->reviewers->map(fn($r) => $r->user->name)->implode(', ');
                $status = $proposal->reviewers->count() === 0 ? 'Belum Diplot' : ($proposal->reviewers->count() < $requiredReviewers ? 'Kurang Reviewer' : 'Lengkap');
            @endphp
            <tr>
                <td style="border: 1px solid black; text-align: center;">{{ $index + 1 }}</td>
                <td style="border: 1px solid black;">{{ $proposal->title }}</td>
                <td style="border: 1px solid black;">{{ $proposal->submitter->name }}</td>
                <td style="border: 1px solid black;">
                    {{ $identity?->faculty?->name }} / {{ $identity?->studyProgram?->name }}
                </td>
                <td style="border: 1px solid black; text-align: center;">
                    {{ $proposal->detailable_type === 'App\Models\Research' ? 'Penelitian' : 'PKM' }}
                </td>
                <td style="border: 1px solid black;">{{ $revNames ?: '-' }}</td>
                <td style="border: 1px solid black; text-align: center;">{{ $status }}</td>
            </tr>
        @endforeach
        
        <tr></tr>
        <tr></tr>

        <!-- SECTION 2: BEBAN KERJA REVIEWER -->
        <tr>
            <th colspan="{{ $totalCols }}" style="font-size: 12pt; font-weight: bold; background-color: #f2f2f2;">
                II. BEBAN KERJA REVIEWER
            </th>
        </tr>
        <tr>
            <th style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2;">No</th>
            <th colspan="2" style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2;">Nama Reviewer</th>
            <th colspan="2" style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2;">Fakultas / Instansi</th>
            <th style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2; text-align: center;">Total Tugas</th>
            <th style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2; text-align: center;">Selesai (Completed)</th>
            <th style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2; text-align: center;">Pending (Proses)</th>
        </tr>
    </tbody>
    <tbody>
        @foreach($reviewers as $index => $rev)
            <tr>
                <td style="border: 1px solid black; text-align: center;">{{ $index + 1 }}</td>
                <td colspan="2" style="border: 1px solid black; font-weight: bold;">{{ $rev->name }}</td>
                <td colspan="2" style="border: 1px solid black;">{{ $rev->identity->faculty->name ?? '-' }}</td>
                <td style="border: 1px solid black; text-align: center;">{{ $rev->total_assigned }}</td>
                <td style="border: 1px solid black; text-align: center; color: green;">{{ $rev->completed_count }}</td>
                <td style="border: 1px solid black; text-align: center; color: orange;">{{ $rev->pending_count }}</td>
            </tr>
        @endforeach

        <tr></tr>
        <tr></tr>

        <!-- SECTION 3: REKAPITULASI PENILAIAN DOSEN -->
        <tr>
            <th colspan="{{ $totalCols }}" style="font-size: 12pt; font-weight: bold; background-color: #f2f2f2;">
                III. REKAPITULASI HASIL PENILAIAN PROPOSAL DOSEN
            </th>
        </tr>
        <tr>
            <th style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2;">No</th>
            <th style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2;">Judul Proposal</th>
            <th style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2;">Dosen Pengaju</th>
            @for ($i = 0; $i < $requiredReviewers; $i++)
                <th style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2; text-align: center;">Skor Rev {{ $i + 1 }}</th>
            @endfor
            <th style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2; text-align: center;">Nilai Rata-rata</th>
            <th style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2; text-align: center;">Status Review</th>
        </tr>
    </tbody>
    <tbody>
        @foreach($proposals as $index => $proposal)
            @php
                // Vetted by AI - Manual Review Required by Senior Engineer/Manager
                $reviewersList = $proposal->reviewers;
            @endphp
            <tr>
                <td style="border: 1px solid black; text-align: center;">{{ $index + 1 }}</td>
                <td style="border: 1px solid black;">{{ $proposal->title }}</td>
                <td style="border: 1px solid black;">{{ $proposal->submitter->name }}</td>
                @for ($i = 0; $i < $requiredReviewers; $i++)
                    @php
                        $r = $reviewersList[$i] ?? null;
                        $rScore = $r && $r->isCompleted() ? ($r->latestLog()->total_score ?? '-') : '-';
                    @endphp
                    <td style="border: 1px solid black; text-align: center;">{{ $rScore }}</td>
                @endfor
                <td style="border: 1px solid black; text-align: center; font-weight: bold; color: blue;">{{ $proposal->score ?? '-' }}</td>
                <td style="border: 1px solid black; text-align: center;">
                    {{ $proposal->status === \App\Enums\ProposalStatus::REVIEWED ? 'Selesai' : ($proposal->status === \App\Enums\ProposalStatus::UNDER_REVIEW ? 'Proses' : $proposal->status->label()) }}
                </td>
            </tr>
        @endforeach

        <tr></tr>
        <tr></tr>

        <!-- SECTION 4: DETAIL KOMPONEN KRITERIA PENILAIAN -->
        <tr>
            <th colspan="{{ $totalCols }}" style="font-size: 12pt; font-weight: bold; background-color: #f2f2f2;">
                IV. RINCIAN KOMPONEN NILAI KRITERIA PENILAIAN
            </th>
        </tr>
        <tr>
            <th style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2;">No</th>
            <th style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2;">Dosen Pengaju</th>
            <th style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2;">Judul Proposal</th>
            <th style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2;">Nama Reviewer</th>
            <th style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2;">Kriteria Penilaian</th>
            <th style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2; text-align: center;">Bobot</th>
            <th style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2; text-align: center;">Skor (1-100)</th>
            <th style="font-weight: bold; border: 1px solid black; background-color: #d9e1f2; text-align: center;">Nilai Terbobot</th>
        </tr>
    </tbody>
    <tbody>
        @php $rowNo = 1; @endphp
        @foreach($proposals as $proposal)
            @foreach($proposal->reviewers as $reviewer)
                @foreach($reviewer->scores as $score)
                    @php
                        $weighted = ($score->score * $score->weight_snapshot) / 100;
                    @endphp
                    <tr>
                        <td style="border: 1px solid black; text-align: center;">{{ $rowNo++ }}</td>
                        <td style="border: 1px solid black;">{{ $proposal->submitter->name }}</td>
                        <td style="border: 1px solid black;">{{ $proposal->title }}</td>
                        <td style="border: 1px solid black;">{{ $reviewer->user->name }}</td>
                        <td style="border: 1px solid black;">{{ $score->criteria->criteria }}</td>
                        <td style="border: 1px solid black; text-align: center;">{{ $score->weight_snapshot }}%</td>
                        <td style="border: 1px solid black; text-align: center;">{{ $score->score }}</td>
                        <td style="border: 1px solid black; text-align: center;">{{ number_format($weighted, 2) }}</td>
                    </tr>
                @endforeach
            @endforeach
        @endforeach
    </tbody>
</table>
