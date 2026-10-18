@props(['size' => 32])
{{-- Marca Atendly: balão de atendimento com "check" (chamado percebido) e o ponto âmbar de alerta. --}}
<svg {{ $attributes->merge(['class' => 'logo']) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <defs>
        <linearGradient id="atendly-g" x1="4" y1="2" x2="36" y2="38" gradientUnits="userSpaceOnUse">
            <stop stop-color="#2CC4A6"/>
            <stop offset="1" stop-color="#0D8A74"/>
        </linearGradient>
    </defs>
    <rect width="40" height="40" rx="11" fill="url(#atendly-g)"/>
    <path d="M14.5 11h11a3.5 3.5 0 0 1 3.5 3.5v8a3.5 3.5 0 0 1-3.5 3.5h-6L15 30v-4h-.5a3.5 3.5 0 0 1-3.5-3.5v-8a3.5 3.5 0 0 1 3.5-3.5Z" fill="#fff"/>
    <path d="m15.8 18.6 2.6 2.6 5.4-5.4" stroke="#0D8A74" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
    <circle cx="30.5" cy="9.5" r="4" fill="#F59E0B"/>
</svg>
