@props(['items' => []])

@if (session('sim_lppm_app_theme', request()->cookie('sim_lppm_app_theme', 'modern')) === 'modern')
<nav aria-label="Breadcrumb" class="text-xs text-slate-500 mb-3">
    <ol class="flex items-center gap-1 flex-wrap">
        @foreach ($items as $i => $item)
            <li class="flex items-center gap-1">
                @if (!empty($item['href']) && $i < count($items) - 1)
                    <a href="{{ $item['href'] }}" class="hover:underline text-slate-500">{{ $item['label'] }}</a>
                @else
                    <span class="font-semibold text-slate-700">{{ $item['label'] }}</span>
                @endif
                @if ($i < count($items) - 1)
                    <span class="mx-1">/</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
@endif