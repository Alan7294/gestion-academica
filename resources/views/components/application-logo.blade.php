@props(['class' => 'h-9 w-9'])

{{-- Marca del sistema: birrete sobre un cuadrado turquesa. --}}
<svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 48 48', 'aria-hidden' => 'true']) }}>
    <defs>
        <linearGradient id="logo-turquesa" x1="0" y1="0" x2="48" y2="48" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#2dd4bf" />
            <stop offset="1" stop-color="#0d9488" />
        </linearGradient>
    </defs>

    <rect width="48" height="48" rx="14" fill="url(#logo-turquesa)" />

    <path d="M24 12 8.5 19.2 24 26.4l15.5-7.2L24 12Z" fill="#082f2c" />
    <path d="M14 22.4V28c0 3.1 4.5 5.6 10 5.6s10-2.5 10-5.6v-5.6" fill="none" stroke="#082f2c" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" />
    <path d="M39 19v8.4" fill="none" stroke="#082f2c" stroke-width="2.6" stroke-linecap="round" />
    <circle cx="39" cy="29.6" r="2.4" fill="#082f2c" />
</svg>
