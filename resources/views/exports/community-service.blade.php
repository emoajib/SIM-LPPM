<table>
    <thead>
        <tr>
            <th colspan="8" style="text-align: center; font-weight: bold; font-size: 16px;">
                INSTITUT TEKNOLOGI DAN SAINS NAHDLATUL ULAMA PEKALONGAN
            </th>
        </tr>
        <tr>
            <th colspan="8" style="text-align: center; font-weight: bold; font-size: 14px;">
                LEMBAGA PENELITIAN DAN PENGABDIAN KEPADA MASYARAKAT (LPPM)
            </th>
        </tr>
        <tr>
            <th colspan="8" style="text-align: center; font-weight: bold; font-size: 12px;">
                LAPORAN PENGABDIAN MASYARAKAT (PKM) TAHUN {{ $period }}@if($semester && $semester !== 'all')<br>Semester {{ ucfirst($semester) }}@endif
            </th>
        </tr>
        <tr>
            <th colspan="8"></th>
        </tr>
        <tr>
            <th
                style="font-weight: bold; border: 2px solid #000000; background-color: #e2e8f0; text-align: center; width: 50px;">
                No</th>
            <th
                style="font-weight: bold; border: 2px solid #000000; background-color: #e2e8f0; text-align: center; width: 350px;">
                Judul PKM</th>
            <th
                style="font-weight: bold; border: 2px solid #000000; background-color: #e2e8f0; text-align: center; width: 200px;">
                Ketua Pelaksana</th>
            <th
                style="font-weight: bold; border: 2px solid #000000; background-color: #e2e8f0; text-align: center; width: 200px;">
                Fakultas / Prodi</th>
            <th
                style="font-weight: bold; border: 2px solid #000000; background-color: #e2e8f0; text-align: center; width: 150px;">
                Skema</th>
            <th
                style="font-weight: bold; border: 2px solid #000000; background-color: #e2e8f0; text-align: center; width: 80px;">
                Semester</th>
            <th
                style="font-weight: bold; border: 2px solid #000000; background-color: #e2e8f0; text-align: center; width: 100px;">
                Status</th>
            <th
                style="font-weight: bold; border: 2px solid #000000; background-color: #e2e8f0; text-align: center; width: 150px;">
                Dana Disetujui (Rp)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($proposals as $index => $proposal)
            <tr>
                <td style="border: 1px solid #000000; text-align: center; vertical-align: top;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #000000; font-weight: bold; vertical-align: top;">{{ $proposal->title }}</td>
                <td style="border: 1px solid #000000; vertical-align: top;">
                    {{ $proposal->submitter?->name ?? '-' }}
                    @if($proposal->submitter?->identity?->nidn) (NIDN: {{ $proposal->submitter->identity->nidn }}) @endif
                </td>
                <td style="border: 1px solid #000000; vertical-align: top;">
                    {{ $proposal->submitter?->identity?->faculty?->name ?? '-' }} /
                    {{ $proposal->submitter?->identity?->studyProgram?->name ?? '-' }}
                </td>
                <td style="border: 1px solid #000000; text-align: center; vertical-align: top;">
                    {{ $proposal->communityServiceScheme?->name ?? '-' }}</td>
                <td style="border: 1px solid #000000; text-align: center; vertical-align: top; text-transform: capitalize;">
                    {{ $proposal->semester ?? '-' }}</td>
                <td style="border: 1px solid #000000; text-align: center; vertical-align: top;">
                    {{ $proposal->status?->label() ?? '-' }}</td>
                <td style="border: 1px solid #000000; text-align: right; vertical-align: top;">
                    {{ ($proposal->sbk_value && $proposal->sbk_value > 0) ? $proposal->sbk_value : ($proposal->budgetItems->sum('total_price') ?? 0) }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>