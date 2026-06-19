<div class="section-title">{{ $sectionNum }}. IDENTITAS PENGUSUL</div>
<table class="table-data">
    <thead>
        <tr>
            <th width="20%">Nama, Peran</th>
            <th width="15%">Institusi</th>
            <th width="15%">Program Studi</th>
            <th width="15%">Bidang Tugas</th>
            <th width="10%">ID Sinta</th>
            <th width="10%">GS H-Index</th>
            <th width="15%">Rumpun Ilmu</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <span class="font-bold">{{ strtoupper($submitterFullName) }}</span><br>
                Ketua Pengusul
            </td>
            <td>{{ $proposal->submitter->identity?->institution?->name ?? '-' }}</td>
            <td>{{ $proposal->submitter->identity?->studyProgram->name ?? '-' }}</td>
            <td>{{ $proposal->teamMembers->firstWhere('id', $proposal->submitter_id)->pivot->tasks ?? '-' }}</td>
            <td class="text-center">{{ $proposal->submitter->identity?->sinta_id ?? '-' }}</td>
            <td class="text-center">{{ $proposal->submitter->identity?->gs_h_index ?? '-' }}</td>
            <td>{{ $proposal->submitter->identity?->scienceCluster?->name ?? '-' }}</td>
        </tr>
        @php
            $lecturerMembersSection2 = $proposal->teamMembers->filter(fn($m) => $m->id !== $proposal->submitter_id && ($m->identity?->type === 'dosen' || $m->pivot->role === 'anggota' || $m->pivot->role === 'dosen'));
        @endphp
        @foreach($lecturerMembersSection2 as $member)
            @if($member->identity?->type === 'dosen' || $member->pivot->role === 'anggota' || $member->pivot->role === 'dosen')
                <tr>
                    <td>
                        <span class="font-bold">{{ strtoupper(format_name($member->identity?->title_prefix ?? '', $member->name, $member->identity?->title_suffix ?? '')) }}</span><br>
                        Anggota Pelaksana
                    </td>
                    <td>{{ $member->identity?->institution?->name ?? 'ITSNU Pekalongan' }}</td>
                    <td>{{ $member->identity?->studyProgram?->name ?? '-' }}</td>
                    <td>{{ $member->pivot->tasks ?? '-' }}</td>
                    <td class="text-center">{{ $member->identity?->sinta_id ?? '-' }}</td>
                    <td class="text-center">{{ $member->identity?->gs_h_index ?? '-' }}</td>
                    <td>{{ $member->identity?->scienceCluster?->name ?? '-' }}</td>
                </tr>
            @endif
        @endforeach
    </tbody>
</table>
