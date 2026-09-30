@props(['class' => 'h-9 w-9'])

{{-- Logo Miseku versi inline (kedai/booth + kipas), memakai palet logo asli. --}}
<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 64 64" fill="none"
     xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <path d="M6 22 32 8l26 14" stroke="#adc3f5" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M12 22h40v30H12z" fill="#f6f9ff" stroke="#7a9bcf" stroke-width="3.2" stroke-linejoin="round"/>
    <path d="M12 22c3.5 5 7.5 5 11 0 3.5 5 7.5 5 11 0 3.5 5 7.5 5 11 0 3 4.3 6.4 4.9 7 1.2"
          fill="#ccdbfa" stroke="#7a9bcf" stroke-width="2.6" stroke-linejoin="round"/>
    <path d="M32 52c-7 0-12-4.4-12-10.5S25 31 32 31s12 4.4 12 10.5S39 52 32 52z" fill="#ccdbfa"/>
    <path d="M20 43.5 32 33l12 10.5" stroke="#7a9bcf" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
    <circle cx="32" cy="45" r="2.6" fill="#7a9bcf"/>
    <path d="M9 54h46" stroke="#7a9bcf" stroke-width="3.2" stroke-linecap="round"/>
</svg>
