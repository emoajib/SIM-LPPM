<div class="section-title">{{ $sectionNum }}. IDENTITAS MAHASISWA</div>
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
    <table class="table-data">
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
                    <td>{{ ucwords(strtolower($member->name)) }}</td>
                    <td>{{ $member->identity?->identity_id ?? '-' }}</td>
                    <td>{{ $member->identity?->studyProgram?->name ?? '-' }}</td>
                    <td>{{ $member->pivot->tasks ?? '-' }}</td>
                </tr>
            @endforeach
            @foreach($mahasiswaJson as $student)
                <tr>
                    <td>{{ ucwords(strtolower($student['name'] ?? '-')) }}</td>
                    <td>{{ $student['identifier'] ?? '-' }}</td>
                    <td>{{ $student['study_program'] ?? ($student['prodi'] ?? '-') }}</td>
                    <td>{{ $student['tasks'] ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div style="margin-left: 20px; border: 1px dashed #ccc; padding: 10px; color: #666; font-style: italic;">
        Tidak ada anggota mahasiswa dalam usulan ini.
    </div>
@endif
