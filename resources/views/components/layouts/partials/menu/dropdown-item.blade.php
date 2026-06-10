@php
    $itemType = $item['type'] ?? 'link';
    $isActive = $item['active'] ?? false;
@endphp
{{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
@if ($itemType === 'divider')
    <div class="dropdown-divider"></div>
@elseif ($itemType === 'header')
    <h6 class="dropdown-header">{{ $item['label'] }}</h6>
@elseif ($itemType === 'dropend')
    <div class="dropend">
        <a class="{{ $item['toggle_class'] ?? 'dropdown-item dropdown-toggle' }}{{ $isActive ? ' active' : '' }}"
            href="{{ $item['href'] ?? '#' }}" data-bs-toggle="{{ $item['toggle']['data-bs-toggle'] ?? 'dropdown' }}"
            data-bs-auto-close="{{ $item['toggle']['data-bs-auto-close'] ?? 'outside' }}"
            role="{{ $item['toggle']['role'] ?? 'button' }}"
            aria-expanded="{{ $item['toggle']['aria-expanded'] ?? 'false' }}">
            {{ $item['label'] }}
        </a>
        <div class="dropdown-menu">
            @foreach ($item['children'] ?? [] as $child)
                @include('components.layouts.partials.menu.dropdown-item', ['item' => $child, 'key' => $loop->index])
            @endforeach
        </div>
    </div>
@else
    <a class="{{ $item['class'] ?? 'dropdown-item' }}{{ $isActive ? ' active' : '' }}" href="{{ $item['href'] ?? '#' }}"
        @unless(($item['navigate'] ?? true) === false) wire:navigate.hover @endunless @foreach ($item['attributes'] ?? [] as $attribute => $value) {{ $attribute }}="{{ $value }}" @endforeach wire:key="menu-item-{{ $item['label'] ?? $loop->index }}">
        @if (!empty($item['prefix_icon']))
            @include('components.layouts.partials.menu.icon', [
                'name' => $item['prefix_icon'],
                'class' => $item['prefix_icon_class'] ?? 'icon icon-2 icon-inline me-1',
            ])
        @elseif (!empty($item['prefix_html']))
                {!! $item['prefix_html'] !!}
            @endif
    {{ $item['label'] }}
        @if (!empty($item['badge']))
            <span class="{{ $item['badge']['class'] ?? '' }}">{{ $item['badge']['text'] ?? '' }}</span>
        @endif
        </a>
@endif
