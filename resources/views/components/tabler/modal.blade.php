@props([
    'id',
    'title' => 'Modal Title',
    'wireIgnore' => true,
    'closeButton' => true,
    'closable' => true,
    'backdrop' => 'static',
    'keyboard' => false,
    'scrollable' => false,
    'centered' => true,
    'size' => 'md',
    'variant' => 'default', // simple, large, small, full-width, scrollable, form, success, danger, confirmation
    'type' => 'default', // success, danger, warning, info, primary (for colored headers)
    'icon' => null, // icon class for header
    'onShow' => null,
    'onHide' => null,
])

@php
    $dialogClasses = 'modal-dialog';
    $modalClasses = 'modal modal-blur fade';
    $headerClasses = 'modal-header';
    $iconMap = [
        'success' => 'ti ti-check-circle text-success',
        'danger' => 'ti ti-alert-triangle text-danger',
        'warning' => 'ti ti-alert-circle text-warning',
        'info' => 'ti ti-info-circle text-info',
    ];

    // Apply size based on variant if not explicitly set
    if (!$attributes->has('size') && $variant !== 'default') {
        switch ($variant) {
            case 'simple':
                $size = 'md';
                break;
            case 'large':
                $size = 'xl';
                break;
            case 'small':
                $size = 'sm';
                break;
            case 'full-width':
                $size = 'xl';
                $modalClasses .= ' w-100';
                break;
            default:
                $size = $size ?: 'lg';
        }
    }

    // Apply variant-specific styling
    if ($variant === 'scrollable') {
        $scrollable = true;
    }

    if ($centered) {
        $dialogClasses .= ' modal-dialog-centered';
    }

    if ($scrollable) {
        $dialogClasses .= ' modal-dialog-scrollable';
    }

    if ($size) {
        $dialogClasses .= ' modal-' . $size;
    }

    // Apply type-specific styling
    if ($type !== 'default') {
        $headerClasses .= ' text-bg-' . $type;
        if (!$icon && isset($iconMap[$type])) {
            $icon = $iconMap[$type];
        }
    }

    $titleId = $id . '-label';

    // Prepare wire:ignore.self attribute if wireIgnore is true
    $wireIgnoreAttr = $wireIgnore ? ['wire:ignore.self' => true] : [];
@endphp

<div wire:key="{{ $id }}"
    {{ $attributes->merge(
        array_merge(
            [
                'class' => $modalClasses,
                'id' => $id,
                'tabindex' => '-1',
                'aria-labelledby' => $titleId,
                'aria-hidden' => 'true',
                'data-bs-backdrop' => (string) $backdrop,
                'data-bs-keyboard' => $keyboard ? 'true' : 'false',
                'data-livewire-modal' => $id,
                'data-livewire-on-show' => $onShow,
                'data-livewire-on-hide' => $onHide,
            ],
            $wireIgnoreAttr,
        ),
    ) }}>
    <div class="{{ trim($dialogClasses) }}">
        <div class="modal-content">
            @if ($title || isset($header))
                <div class="{{ trim($headerClasses) }}">
                    @isset($header)
                        {{ $header }}
                    @else
                        @if ($icon)
                            <i class="{{ $icon }} me-2"></i>
                        @endif
                        <h5 class="modal-title" id="{{ $titleId }}">{{ $title }}</h5>
                    @endisset

                    @if ($closeButton)
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    @endif
                </div>
            @endif

            <div class="modal-body">
                {{ $body ?? $slot }}
            </div>

            @isset($footer)
                <div class="modal-footer">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
