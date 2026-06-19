@php $pdfConfig ??= get_pdf_config('letter'); @endphp
<div style="position: relative; width: 100%; height: 100%;">
    <div style="font-size: 16pt; font-weight: bold; margin-bottom: 20px; margin-top: 30px; text-transform: uppercase; text-align: center;">
        {{ $pdfConfig['cover_title'] ?: $coverTitle }}
    </div>

    @if(!empty($pdfConfig['cover_subtitle']))
    <div style="font-size: 14pt; margin-bottom: 20px; text-align: center;">
        {{ $pdfConfig['cover_subtitle'] }}
    </div>
    @endif

    <div style="margin: 50px 0; text-align: center;">
        @if(($pdfConfig['show_logo'] ?? true) && get_logo_base64())
            <img src="{{ get_logo_base64() }}" style="width: {{ $pdfConfig['logo_size'] ?? 140 }}px;">
        @endif
    </div>

    <div style="font-size: 16pt; font-weight: bold; margin-bottom: 50px; line-height: 1.4; text-align: center; text-transform: uppercase;">
        {{ clean_proposal_title($proposal->title) }}
    </div>

    @if($pdfConfig['cover_show_team'] ?? true)
    <div style="width: 80%; margin: 0 auto; margin-top: 40px; font-size: 12pt;">
        <div style="font-weight: bold; margin-bottom: 15px; text-align: center;">Oleh:</div>
        <table style="width: 100%; border: none; border-collapse: collapse;">
            <tr>
                <td style="width: 25%; border: none; padding: 6px; text-align: left;">Ketua</td>
                <td style="width: 5%; border: none; padding: 6px; text-align: center;">:</td>
                <td style="width: 45%; border: none; padding: 6px; font-weight: bold; text-align: left;">{{ $submitterFullName }}</td>
                <td style="width: 25%; border: none; padding: 6px; text-align: right;">(NIDN: {{ $submitterNidn }})</td>
            </tr>
            @php
                $lecturerMembersCover = $proposal->teamMembers->filter(fn($m) => $m->id !== $proposal->submitter_id && ($m->identity?->type === 'dosen' || $m->pivot->role === 'anggota' || $m->pivot->role === 'dosen'))->values();
            @endphp
            @foreach($lecturerMembersCover as $index => $member)
            <tr>
                <td style="width: 25%; border: none; padding: 6px; text-align: left;">Anggota {{ to_roman($index + 1) }}</td>
                <td style="width: 5%; border: none; padding: 6px; text-align: center;">:</td>
                <td style="width: 45%; border: none; padding: 6px; font-weight: bold; text-align: left;">{{ format_name($member->identity?->title_prefix ?? '', $member->name, $member->identity?->title_suffix ?? '') }}</td>
                <td style="width: 25%; border: none; padding: 6px; text-align: right;">(NIDN: {{ $member->identity?->identity_id ?? '-' }})</td>
            </tr>
            @endforeach
        </table>
    </div>
    @endif

    @php
        $facultyClean = preg_replace('/^FAKULTAS\s+/i', '', trim($facultyName));
        $prodiClean = preg_replace('/^(PROGRAM STUDI|PRODI)\s+/i', '', trim($prodiName));
    @endphp
    <div style="position: absolute; bottom: 30px; left: 0; right: 0; text-align: center; font-weight: bold; font-size: 14pt; text-transform: uppercase; line-height: 1.4;">
        FAKULTAS {{ strtoupper($facultyClean) }}<br>
        PROGRAM STUDI {{ strtoupper($prodiClean) }}<br>
        ITSNU PEKALONGAN<br>
        TAHUN {{ $coverYear }}
    </div>
</div>
