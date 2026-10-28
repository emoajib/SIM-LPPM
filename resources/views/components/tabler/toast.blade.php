@props([
    'id' => 'toast-' . uniqid(),
    'title' => null,
    'subtitle' => null,
    'avatar' => null,
    'icon' => null,
    'autoHide' => true,
    'delay' => 5000, // milliseconds
    'position' => 'bottom-end', // top-start, top-center, top-end, middle-start, middle-center, middle-end, bottom-start, bottom-center, bottom-end
    'variant' => 'default', // default, success, danger, warning, info
    'showHeader' => true,
    'closeButton' => true,
])

@php
    $positionMap = [
        'top-start' => 'top-0 start-0',
        'top-center' => 'top-0 start-50 translate-middle-x',
        'top-end' => 'top-0 end-0',
        'middle-start' => 'top-50 start-0 translate-middle-y',
        'middle-center' => 'top-50 start-50 translate-middle',
        'middle-end' => 'top-50 end-0 translate-middle-y',
        'bottom-start' => 'bottom-0 start-0',
        'bottom-center' => 'bottom-0 start-50 translate-middle-x',
        'bottom-end' => 'bottom-0 end-0',
    ];

    $positionClass = $positionMap[$position] ?? $positionMap['bottom-end'];

    $variantClasses = match ($variant) {
        'success' => 'border-success',
        'danger' => 'border-danger',
        'warning' => 'border-warning',
        'info' => 'border-info',
        default => '',
    };

    $variantIconClasses = match ($variant) {
        'success' => 'ti ti-check text-success',
        'danger' => 'ti ti-x text-danger',
        'warning' => 'ti ti-alert-triangle text-warning',
        'info' => 'ti ti-info-circle text-info',
        default => '',
    };
@endphp

<div class="toast-container position-fixed {{ $positionClass }} p-3" style="z-index: 1090;">
    <div class="toast {{ $variantClasses }}" id="{{ $id }}" role="alert" aria-live="assertive" aria-atomic="true"
        data-bs-autohide="{{ $autoHide ? 'true' : 'false' }}" data-bs-delay="{{ $delay }}">
        @if ($showHeader)
            <div class="toast-header">
                @if ($avatar)
                    <span class="me-2 avatar avatar-xs" style="background-image: url({{ $avatar }})"></span>
                @elseif ($icon)
                    <x-lucide-{{ $icon }} class="me-2 icon" />
                @elseif ($variant !== 'default')
                    <i class="{{ $variantIconClasses }} me-2" aria-hidden="true"></i>
                @endif

                @if ($title)
                    <strong class="me-auto">{{ $title }}</strong>
                @endif

                @if ($subtitle)
                    <small>{{ $subtitle }}</small>
                @endif

                @if ($closeButton)
                    <button type="button" class="ms-2 btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                @endif
            </div>
        @endif

        <div class="toast-body">
            {{ $slot }}
        </div>
    </div>
</div>
