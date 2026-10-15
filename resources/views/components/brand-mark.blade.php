@props(['class' => 'h-10 w-10', 'full' => false])

{{--
    Logo Miseku (gradasi ungu → magenta).
    full=true  → logo lengkap dengan tulisan MISEKU (public/img/logo.png)
    full=false → simbol kedai saja (public/img/logo-mark.png), dipakai di header
--}}
<img src="{{ asset($full ? 'img/logo.png' : 'img/logo-mark.png') }}" alt="Logo Miseku"
     {{ $attributes->merge(['class' => $class . ' object-contain']) }}>
