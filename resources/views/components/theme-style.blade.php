{{-- Variabel warna tema aktif (ThemeService). Ditaruh di <body> supaya ikut terganti saat wire:navigate. --}}
@php $theme = app(\App\Services\ThemeService::class)->current()['theme']; @endphp
@if ($theme)
<style id="ms-theme">:root{@foreach ($theme->cssVariables() as $k => $v){{ $k }}:{{ $v }};@endforeach}</style>
@endif
