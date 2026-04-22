@props([
    'title' => null,
    'subtitle' => null,
    'actions' => null,
    'compact' => false,
])

@php
    $baseClasses = 'border border-white/10 bg-[rgba(18,24,43,0.88)] shadow-[0_24px_80px_rgba(0,0,0,0.45)] backdrop-blur-2xl';
    $spacingClasses = $compact ? 'rounded-[24px] p-4' : 'rounded-[28px] p-6';
@endphp

<section {{ $attributes->class("{$baseClasses} {$spacingClasses}") }}>
    @if ($title || $actions)
        <header class="mb-5 flex items-start justify-between gap-4">
            <div>
                @if ($title)
                    <h2 class="text-base font-semibold tracking-tight text-white">{{ $title }}</h2>
                @endif

                @if ($subtitle)
                    <p class="mt-1 text-sm text-slate-400">{{ $subtitle }}</p>
                @endif
            </div>

            @if ($actions)
                <div class="flex items-center gap-2">
                    {{ $actions }}
                </div>
            @endif
        </header>
    @endif

    <div class="text-sm text-slate-100">
        {{ $slot }}
    </div>
</section>
