@php $pdfConfig ??= get_pdf_config('letter'); @endphp
<div>
    @php
        $lineHeight = $pdfConfig['line_height'] ?? 1.5;
    @endphp

    <div style="font-size: 16pt; font-weight: bold; margin-top: 40px; margin-bottom: 20px; text-transform: uppercase; text-align: center; line-height: {{ $lineHeight }};">
        {{ $pdfConfig['cover_title'] ?: $coverTitle }}
    </div>

    @if(!empty($pdfConfig['cover_subtitle']))
    <div style="font-size: 14pt; margin-bottom: 20px; text-align: center; line-height: {{ $lineHeight }};">
        {{ $pdfConfig['cover_subtitle'] }}
    </div>
    @endif

    {{-- Nomor Kontrak dipindah ke bawah Tim Pelaksana (format baru) --}}

    <div style="margin-top: 30px; margin-bottom: 25px; text-align: center;">
        @if(($pdfConfig['show_logo'] ?? true) && get_logo_base64())
            <img src="{{ get_logo_base64() }}" style="width: {{ $pdfConfig['logo_size'] ?? 350 }}px;">
        @endif
    </div>

    <div style="font-size: 14pt; font-weight: bold; margin-bottom: 20px; text-align: center; text-transform: uppercase; line-height: {{ $lineHeight }};">
        {{ clean_proposal_title($proposal->title) }}
    </div>

    @if($pdfConfig['cover_show_team'] ?? true)
    <div style="width: 100%; margin: 0 auto; margin-bottom: 15px; font-size: 11pt; line-height: {{ $lineHeight }};">
        <div style="font-weight: bold; margin-bottom: 10px; text-align: center;">Oleh:</div>
        <table style="margin: 0 auto; border: none; border-collapse: collapse;">
            <tr>
                <td style="width: 1%; border: none; padding: 2px 2px 2px 0; text-align: left; vertical-align: top; white-space: nowrap;">Ketua</td>
                <td style="width: 1%; border: none; padding: 2px 4px 2px 2px; text-align: center; vertical-align: top; white-space: nowrap;">:</td>
                <td style="border: none; padding: 2px 0 2px 2px; text-align: left; vertical-align: top; white-space: nowrap;">
                    <span style="font-weight: bold;">{{ $submitterFullName }}</span>&nbsp;(NIDN:&nbsp;{{ $submitterNidn }})
                </td>
            </tr>
            @php
                $lecturerMembersCover = $proposal->teamMembers->filter(fn($m) => $m->id !== $proposal->submitter_id && ($m->identity?->type === 'dosen' || $m->pivot->role === 'anggota' || $m->pivot->role === 'dosen'))->values();
            @endphp
            @foreach($lecturerMembersCover as $index => $member)
            <tr>
                <td style="width: 1%; border: none; padding: 2px 2px 2px 0; text-align: left; vertical-align: top; white-space: nowrap;">Anggota {{ to_roman($index + 1) }}</td>
                <td style="width: 1%; border: none; padding: 2px 4px 2px 2px; text-align: center; vertical-align: top; white-space: nowrap;">:</td>
                <td style="border: none; padding: 2px 0 2px 2px; text-align: left; vertical-align: top; white-space: nowrap;">
                    <span style="font-weight: bold;">{{ format_name($member->identity?->title_prefix ?? '', $member->name, $member->identity?->title_suffix ?? '') }}</span>&nbsp;(NIDN:&nbsp;{{ $member->identity?->identity_id ?? '-' }})
                </td>
            </tr>
            @endforeach
        </table>
    </div>
    @endif

    {{-- Blok "Dibiayai Oleh" + Nomor Kontrak (format baru) --}}
    <div style="margin-top: 20px; text-align: center; font-size: 11pt; line-height: 1.6; margin-bottom: 20px;">
        Dibiayai Oleh {{ get_institution_config('name') ?: 'Institut Teknologi dan Sains Nahdlatul Ulama Pekalongan' }}<br>
        Berdasarkan Kontrak Pelaksanaan Penelitian/Pengabdian Masyarakat<br>
        <strong>No. Kontrak : {{ $proposal->contract_number ?: 'xxxxxxxxxx' }}</strong>
    </div>

    @php
        $facultyClean = preg_replace('/^FAKULTAS\s+/i', '', trim($facultyName));
        $prodiClean = preg_replace('/^(PROGRAM STUDI|PRODI)\s+/i', '', trim($prodiName));
    @endphp
    <div style="position: absolute; bottom: 60px; left: 0; right: 0; text-align: center; font-weight: bold; font-size: 12pt; text-transform: uppercase; line-height: 1.4;">
        FAKULTAS {{ strtoupper($facultyClean) }}<br>
        PROGRAM STUDI {{ strtoupper($prodiClean) }}<br>
        ITSNU PEKALONGAN<br>
        TAHUN {{ $coverYear }}
    </div>
</div>
