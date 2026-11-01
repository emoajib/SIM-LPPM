@props(['module' => 'research', 'active' => 'usulan'])

@php
    $base = $module === 'community-service' ? '/community-service' : '/research';
    $tabs = [
        'usulan' => ['label' => 'Usulan', 'href' => $base],
        'perbaikan' => ['label' => 'Perbaikan Usulan', 'href' => $base.'/proposal-revision'],
        'laporan' => ['label' => 'Laporan Akhir', 'href' => $base.'/final-report'],
        'realisasi' => ['label' => 'Realisasi Anggaran', 'href' => $base.'/daily-note'],
    ];
@endphp

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-2">
        <div class="d-flex gap-1 overflow-auto">
            @foreach($tabs as $key => $tab)
                <a href="{{ $tab['href'] }}"
                   class="px-3 py-2 text-sm fw-semibold text-decoration-none rounded {{ $active === $key ? 'bg-primary text-white' : 'text-muted' }}"
                   style="white-space: nowrap;">
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</div>