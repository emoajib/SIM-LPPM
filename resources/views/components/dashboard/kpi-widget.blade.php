{{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
@props([
    'title' => '',
    'value' => 0,
    'subtitle' => '',
    'icon' => 'activity',
    'color' => 'primary',
    'trend' => null,
    'trendType' => 'up'
])

<div class="card border-0 shadow-sm overflow-hidden h-100" style="border-radius: 12px;">
    <div class="card-body">
        <div class="d-flex align-items-center mb-3">
            <div class="subheader text-{{ $color }} fw-bold">{{ $title }}</div>
            <div class="ms-auto text-{{ $color }} bg-{{ $color }}-lt rounded-circle p-2 d-flex align-items-center justify-content-center"
                style="width: 38px; height: 38px;">
                <i class="ti ti-{{ $icon }} fs-3"></i>
            </div>
        </div>
        <div class="d-flex align-items-baseline">
            <div class="h1 mb-1 fw-bold text-dark">{{ $value }}</div>
            @if ($trend !== null)
                <span class="ms-2 badge bg-{{ $trendType === 'up' ? 'green' : 'red' }}-lt d-inline-flex align-items-center gap-1 small">
                    <i class="ti ti-trending-{{ $trendType === 'up' ? 'up' : 'down' }} fs-4"></i>
                    {{ $trend }}%
                </span>
            @endif
        </div>
        <div class="text-muted small">{{ $subtitle }}</div>
    </div>
</div>
