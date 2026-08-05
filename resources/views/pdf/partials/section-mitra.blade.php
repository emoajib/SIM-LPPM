@if($proposal->partners->count() > 0)
    <div class="section-title">{{ $sectionNum }}. MITRA KERJASAMA</div>
    @foreach($proposal->partners as $index => $partner)
        <div style="margin-bottom: 5px;">
            <strong>Mitra Sasaran {{ $index + 1 }}</strong>
            <table class="no-border" style="margin-left: 15px; margin-bottom: 5px;">
                <tr><td width="150" style="padding: 1px;">Jenis Mitra</td><td style="padding: 1px;">: {{ $partner->type ?? '-' }}</td></tr>
                <tr><td style="padding: 1px;">Nama Mitra Sasaran</td><td style="padding: 1px;">: {{ $partner->name }}</td></tr>
                <tr><td style="padding: 1px;">Institusi</td><td style="padding: 1px;">: {{ $partner->institution ?? '-' }}</td></tr>
                <tr><td style="padding: 1px;">Alamat Lengkap</td><td style="padding: 1px;">: {{ $partner->address ?? '-' }}</td></tr>
            </table>
        </div>
    @endforeach

    @if(isset($report) && !empty($report->partner_changes))
        <div style="margin-top: 10px;">
            <strong>Perubahan Mitra:</strong>
            <div style="margin-left: 20px; text-align: justify; margin-bottom: 5px;">{{ $report->partner_changes }}</div>
        </div>
    @endif

    @if($proposal->detailable_type === 'App\Models\CommunityService')
        <div style="margin-top: 10px;">
            <strong>Ringkasan Permasalahan Mitra:</strong>
            <div style="margin-left: 20px; text-align: justify; margin-bottom: 5px;">{{ $proposal->detailable->partner_issue_summary ?? '-' }}</div>
            <strong>Solusi yang Ditawarkan:</strong>
            <div style="margin-left: 20px; text-align: justify; margin-bottom: 5px;">{{ $proposal->detailable->solution_offered ?? '-' }}</div>
            @if(!empty($showFullDetails))
                <strong>Latar Belakang:</strong>
                <div class="text-justify">{!! nl2br(e($proposal->detailable->background_service ?? '')) !!}</div>
                <strong>Metodologi:</strong>
                <div class="text-justify">{!! nl2br(e($proposal->detailable->methodology_service ?? '')) !!}</div>
            @endif
        </div>
    @elseif($proposal->detailable_type === 'App\Models\Research' && !empty($showFullDetails))
        <div class="text-justify" style="line-height: 1.4;">
            <strong>Latar Belakang:</strong>
            <div>{!! nl2br(e($proposal->detailable->background_research ?? '')) !!}</div>
            <strong style="display: block; margin-top: 10px;">Metodologi:</strong>
            <div>{!! nl2br(e($proposal->detailable->methodology_research ?? '')) !!}</div>
        </div>
    @endif
@endif
