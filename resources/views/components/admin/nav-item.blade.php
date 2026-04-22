@props(['active' => false, 'href', 'collapsed' => false])

@php($label = trim(strip_tags($slot)))

<a
    href="{{ $href }}"
    {{ $attributes->class($active ? 'group relative flex items-center overflow-hidden rounded-2xl border border-[#F8B803]/20 bg-[linear-gradient(90deg,rgba(248,184,3,0.16),rgba(240,172,184,0.1))] px-3.5 py-3 text-sm font-semibold text-[#F8B803] shadow-[0_0_0_1px_rgba(248,184,3,0.12),0_10px_30px_rgba(248,184,3,0.12)]' : 'group relative flex items-center rounded-2xl border border-transparent px-4 py-3 text-sm font-medium text-slate-300 transition hover:border-white/10 hover:bg-white/5 hover:text-white') }}
    title="{{ $label }}"
>
    @if($active)
        <span class="active-pill absolute inset-y-2 left-1 w-1 rounded-full bg-[#F8B803] shadow-[0_0_18px_rgba(248,184,3,0.65)]"></span>
        <span class="absolute inset-0 -translate-x-full bg-[linear-gradient(90deg,transparent,rgba(248,184,3,0.08),transparent)] motion-safe:animate-[shimmer_1.8s_ease-in-out_infinite]"></span>
    @endif

    @isset($icon)
        <span class="relative z-10 inline-flex h-5 w-5 flex-none shrink-0 items-center justify-center overflow-visible text-base leading-none {{ $active ? 'text-[#F8B803]' : 'text-slate-400 group-hover:text-white' }}">
            {{ $icon }}
        </span>
    @endisset

    <span class="min-w-0 truncate {{ isset($icon) ? 'ml-3' : '' }} {{ $collapsed ? 'sr-only' : '' }}">{{ $slot }}</span>
</a>
