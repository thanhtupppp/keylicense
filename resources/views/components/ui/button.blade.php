@props([
    'type' => 'button',
    'variant' => 'primary',
])

@php
    $base = 'inline-flex items-center justify-center rounded-full px-5 py-3 text-sm font-semibold transition duration-150 focus:outline-none focus:ring-2';

    $variants = [
        'primary' => 'bg-linear-to-r from-[#F8B803] to-[#FFD76A] text-[#050814] shadow-lg shadow-amber-500/30 hover:-translate-y-[1px] hover:brightness-105 focus:ring-[#F8B803]/40',
        'secondary' => 'border border-white/10 bg-white/5 text-white hover:bg-white/10 focus:ring-[#F8B803]/20',
        'ghost' => 'text-slate-200 hover:bg-white/5 focus:ring-[#F8B803]/20',
        'danger' => 'border border-rose-400/20 bg-rose-400/10 text-rose-100 hover:bg-rose-400/15 focus:ring-rose-400/20',
    ];
@endphp

<button type="{{ $type }}" {{ $attributes->class($base . ' ' . ($variants[$variant] ?? $variants['primary'])) }}>
    {{ $slot }}
</button>
