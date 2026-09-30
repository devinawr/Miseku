@props(['value' => 0, 'prefix' => 'Rp'])

{{-- Format rupiah ala Indonesia: Rp120.000 --}}
<span {{ $attributes }}>{{ $prefix }}{{ number_format((float) $value, 0, ',', '.') }}</span>
