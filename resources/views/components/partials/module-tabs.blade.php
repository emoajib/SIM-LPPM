@props(['module' => 'research', 'active' => 'usulan'])

@php
    $base = $module === 'community-service' ? '/community-service' : '/research';
    $isPkm = $module === 'community-service';
    $tabs = [
        'usulan'    => ['label' => 'Usulan',             'href' => $base,                           'icon' => $isPkm ? '🤝' : '📋'],
        'perbaikan' => ['label' => 'Perbaikan Usulan',   'href' => $base.'/proposal-revision',      'icon' => '✏️'],
        'laporan'   => ['label' => 'Laporan Akhir',      'href' => $base.'/final-report',           'icon' => '📑'],
        'realisasi' => ['label' => 'Realisasi Anggaran', 'href' => $base.'/daily-note',            'icon' => '💰'],
    ];

    if (active_has_any_role(['admin lppm', 'kepala lppm', 'rektor'])) {
        $tabs['verifikasi'] = ['label' => 'Verifikasi Luaran', 'href' => '/iku/verification', 'icon' => '🛡️'];
    }
@endphp

{{-- Style konsisten dengan React ModuleTabNav: border-b underline + icon visual --}}
<div class="bg-white border border-slate-200 rounded-xl shadow-sm px-4 pt-3 mb-3">
    <div class="d-flex gap-1 overflow-auto">
        @foreach($tabs as $key => $tab)
            <a href="{{ $tab['href'] }}"
               class="px-3.5 py-2 text-sm fw-bold text-decoration-none border-b-2 transition-all d-inline-flex align-items-center gap-2"
               style="white-space: nowrap;
                      border-bottom: 2px solid {{ $active === $key ? '#1e3a6e' : 'transparent' }};
                      color: {{ $active === $key ? '#1e3a6e' : '#64748b' }};
                      transition: color 0.15s, border-color 0.15s;">
                <span>{{ $tab['icon'] }}</span>
                <span>{{ $tab['label'] }}</span>
            </a>
        @endforeach
    </div>
</div>