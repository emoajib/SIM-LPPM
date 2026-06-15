<div class="page-break">
    <div style="font-size: 14pt; font-weight: bold; margin-bottom: 20px; text-transform: uppercase; text-align: center;">
        {{ $pdfConfig['cover_title'] ?: $coverTitle }}
    </div>

    @if(!empty($pdfConfig['cover_subtitle']))
    <div style="font-size: 12pt; margin-bottom: 20px; text-align: center;">
        {{ $pdfConfig['cover_subtitle'] }}
    </div>
    @endif

    <div style="margin: 40px 0; text-align: center;">
        @php $pdfConfig ??= get_pdf_config('letter'); @endphp
        @if(($pdfConfig['show_logo'] ?? true) && get_logo_base64())
            <img src="{{ get_logo_base64() }}" style="width: {{ $pdfConfig['logo_size'] ?? 110 }}px;">
        @endif
    </div>

    <div style="font-size: 14pt; font-weight: bold; margin-bottom: 30px; line-height: 1.3; text-align: center;">
        {{ clean_proposal_title($proposal->title) }}
    </div>

    @if($pdfConfig['cover_show_team'] ?? true)
    <div style="width: 100%; margin: 20px 0;">
        <div style="font-weight: bold; margin-bottom: 5px; text-align: center;">Oleh:</div>
        <table style="width: 100%; border: 0.5pt dashed #000; margin-bottom: 0;">
            <tr>
                <td style="width: 15%; border: 0.5pt dashed #000; padding: 8px;">Ketua</td>
                <td style="width: 45%; border: 0.5pt dashed #000; padding: 8px; font-weight: bold;">{{ $submitterFullName }}</td>
                <td style="width: 10%; border: 0.5pt dashed #000; padding: 8px;">NIDN</td>
                <td style="width: 30%; border: 0.5pt dashed #000; padding: 8px; font-weight: bold;">{{ $submitterNidn }}</td>
            </tr>
            @php
                $lecturerMembersCover = $proposal->teamMembers->filter(fn($m) => $m->id !== $proposal->submitter_id && ($m->identity?->type === 'dosen' || $m->pivot->role === 'anggota' || $m->pivot->role === 'dosen'));
            @endphp
            @foreach($lecturerMembersCover as $index => $member)
            <tr>
                <td style="width: 15%; border: 0.5pt dashed #000; padding: 8px;">Anggota {{ to_roman($index + 1) }}</td>
                <td style="width: 45%; border: 0.5pt dashed #000; padding: 8px; font-weight: bold;">{{ format_name($member->identity?->title_prefix ?? '', $member->name, $member->identity?->title_suffix ?? '') }}</td>
                <td style="width: 10%; border: 0.5pt dashed #000; padding: 8px;">NIDN</td>
                <td style="width: 30%; border: 0.5pt dashed #000; padding: 8px; font-weight: bold;">{{ $member->identity?->identity_id ?? '-' }}</td>
            </tr>
            @endforeach
        </table>
    </div>
    @endif

    <div style="position: absolute; bottom: 2cm; width: 100%; text-align: center; font-weight: bold; font-size: 12pt; text-transform: uppercase;">
        FAKULTAS {{ strtoupper($facultyName) }}<br>
        PROGRAM STUDI {{ strtoupper($prodiName) }}<br>
        ITSNU PEKALONGAN<br>
        TAHUN {{ $coverYear }}
    </div>
</div>
