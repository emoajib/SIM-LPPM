@props(['user' => null, 'size' => 'avatar-sm'])

@php
    $avatarUrl = $user?->profile_picture;
@endphp

<span {{ $attributes->merge(['class' => "avatar {$size}"]) }}
    @if ($avatarUrl) style="background-image: url({{ $avatarUrl }})" @endif>
    @if (! $avatarUrl)
        {{ $user?->initials() ?? '?' }}
    @endif
</span>
