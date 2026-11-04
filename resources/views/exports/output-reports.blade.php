<table>
    <thead>
    <tr>
        <th colspan="6" style="text-align: center; font-weight: bold; font-size: 14px;">
            LAPORAN LUARAN {{ strtoupper($activeTab === 'research' ? 'PENELITIAN' : 'PENGABDIAN') }}
        </th>
    </tr>
    <tr><th colspan="6"></th></tr>
    <tr>
        <th style="font-weight: bold; border: 1px solid #000000; background-color: #f0f0f0;">No</th>
        <th style="font-weight: bold; border: 1px solid #000000; background-color: #f0f0f0;">Judul Proposal</th>
        <th style="font-weight: bold; border: 1px solid #000000; background-color: #f0f0f0;">Ketua</th>
        <th style="font-weight: bold; border: 1px solid #000000; background-color: #f0f0f0;">Fakultas / Prodi</th>
        <th style="font-weight: bold; border: 1px solid #000000; background-color: #f0f0f0;">Jenis Luaran</th>
        <th style="font-weight: bold; border: 1px solid #000000; background-color: #f0f0f0;">Detail Luaran</th>
    </tr>
    </thead>
    <tbody>
    @php $index = 1; @endphp
    @foreach($proposals as $proposal)
        @foreach($proposal->progressReports as $report)
            @foreach($report->mandatoryOutputs as $output)
                @if($outputType === 'all' || $outputType === 'mandatory')
                <tr>
                    <td style="border: 1px solid #000000; text-align: center;">{{ $index++ }}</td>
                    <td style="border: 1px solid #000000;">{{ $proposal->title }}</td>
                    <td style="border: 1px solid #000000;">{{ $proposal->submitter?->name ?? '-' }}</td>
                    <td style="border: 1px solid #000000;">
                        {{ $proposal->submitter?->identity?->faculty?->name ?? '-' }} / 
                        {{ $proposal->submitter?->identity?->studyProgram?->name ?? '-' }}
                    </td>
                    <td style="border: 1px solid #000000; text-align: center;">Wajib: {{ $output->proposalOutput->category ?? '-' }}</td>
                    <td style="border: 1px solid #000000;">
                        Status: {{ ucfirst($output->status_type ?? 'Draft') }}
                        @if($output->journal_title) | Jurnal: {{ $output->journal_title }} @endif
                        @if($output->article_title) | Judul: {{ $output->article_title }} @endif
                        @php $link = $output->article_url ?? ($output->journal_url ?? ($output->media_url ?? ($output->video_url ?? ($output->url ?? null)))); @endphp
                        @if($link) | URL: {{ $link }} @endif
                        | Verifikasi: {{ $output->is_verified ? 'Terverifikasi' : 'Belum' }}
                        @if($output->is_verified && $output->verified_at) | Tgl Verifikasi: {{ $output->verified_at->format('d/m/Y') }} @endif
                    </td>
                </tr>
                @endif
            @endforeach
            @foreach($report->additionalOutputs as $output)
                @if($outputType === 'all' || $outputType === 'additional')
                <tr>
                    <td style="border: 1px solid #000000; text-align: center;">{{ $index++ }}</td>
                    <td style="border: 1px solid #000000;">{{ $proposal->title }}</td>
                    <td style="border: 1px solid #000000;">{{ $proposal->submitter?->name ?? '-' }}</td>
                    <td style="border: 1px solid #000000;">
                        {{ $proposal->submitter?->identity?->faculty?->name ?? '-' }} / 
                        {{ $proposal->submitter?->identity?->studyProgram?->name ?? '-' }}
                    </td>
                    <td style="border: 1px solid #000000; text-align: center;">Tambahan: {{ $output->proposalOutput->category ?? '-' }}</td>
                    <td style="border: 1px solid #000000;">
                        Status: {{ ucfirst($output->status ?? 'Draft') }}
                        @if($output->journal_title) | Jurnal: {{ $output->journal_title }} @endif
                        @php $linkAdd = $output->book_url ?? ($output->journal_url ?? ($output->media_url ?? ($output->video_url ?? ($output->url ?? null)))); @endphp
                        @if($linkAdd) | URL: {{ $linkAdd }} @endif
                        | Verifikasi: {{ $output->is_verified ? 'Terverifikasi' : 'Belum' }}
                        @if($output->is_verified && $output->verified_at) | Tgl Verifikasi: {{ $output->verified_at->format('d/m/Y') }} @endif
                    </td>
                </tr>
                @endif
            @endforeach
        @endforeach
    @endforeach
    </tbody>
</table>
