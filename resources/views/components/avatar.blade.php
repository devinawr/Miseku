@props(['user' => null, 'src' => null, 'name' => '?', 'class' => 'h-9 w-9 text-sm'])

@php
    $src  = $src ?? $user?->avatar_url;
    $name = $user?->name ?? $name;
@endphp

@if ($src)
    <img src="{{ $src }}" alt="{{ $name }}" {{ $attributes->merge(['class' => $class . ' shrink-0 rounded-full object-cover']) }}>
@else
    <span {{ $attributes->merge(['class' => $class . ' ms-gradient inline-flex shrink-0 items-center justify-center rounded-full font-extrabold text-white']) }}>
        {{ mb_strtoupper(mb_substr($name, 0, 1)) }}
    </span>
@endif
