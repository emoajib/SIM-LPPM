@php $scope = $scope ?? 'proposal'; @endphp
<div class="section-title">{{ $sectionNum }}. JUDUL {{ $proposal->detailable_type === 'App\Models\Research' ? 'PENELITIAN' : 'PENGABDIAN' }}</div>
<div class="title-border-box">{{ clean_proposal_title($proposal->title) }}</div>

<table>
    <thead>
        <tr>
            <th>Kelompok Skema</th>
            <th>Ruang Lingkup</th>
            <th>Bidang Fokus</th>
            <th>Lama Kegiatan</th>
            <th>Tahun Pertama Usulan</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="text-center">{{ $proposal->researchScheme->name ?? '-' }}</td>
            <td class="text-center">
                @if($proposal->detailable_type === 'App\Models\Research')
                    Penelitian
                @else
                    Pemberdayaan Kemitraan Masyarakat
                @endif
            </td>
            <td class="text-center">{{ $proposal->focusArea->name ?? '-' }}</td>
            <td class="text-center">{{ $proposal->duration_in_years }}</td>
            <td class="text-center">{{ $proposal->start_year }}</td>
        </tr>
    </tbody>
</table>

<table class="no-border" style="margin-bottom: 15px; font-size: 8.5pt;">
    <tr>
        <td width="150" style="padding: 2px;">Tema {{ $proposal->detailable_type === 'App\Models\Research' ? 'Penelitian' : 'Pengabdian' }}</td>
        <td style="padding: 2px;">: {{ $proposal->theme->name ?? '-' }}</td>
    </tr>
    <tr>
        <td style="padding: 2px;">Topik {{ $proposal->detailable_type === 'App\Models\Research' ? 'Penelitian' : 'Pengabdian' }}</td>
        <td style="padding: 2px;">: {{ $proposal->topic->name ?? '-' }}</td>
    </tr>
    <tr>
        <td style="padding: 2px;">Kata Kunci (Keywords)</td>
        <td style="padding: 2px;">
            : 
            @if($proposal->keywords && count($proposal->keywords) > 0)
                {{ implode(', ', $proposal->keywords->pluck('name')->toArray()) }}
            @else
                -
            @endif
        </td>
    </tr>
    @if($proposal->detailable_type === 'App\Models\Research')
        <tr>
            <td style="padding: 2px;">Jenis TKT</td>
            <td style="padding: 2px;">: {{ $proposal->detailable->tkt_type ?? '-' }}</td>
        </tr>
    @endif
</table>
