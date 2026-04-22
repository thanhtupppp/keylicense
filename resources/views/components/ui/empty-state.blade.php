@props([
    'icon' => null,
    'title',
    'description' => null,
])

@php
    $iconMap = [
        'search' => '🔍',
        'shield' => '🛡️',
        'document' => '📄',
        'folder' => '📁',
    ];

    $resolvedIcon = $iconMap[$icon] ?? '📂';
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center gap-3 rounded-3xl border border-dashed border-white/15 bg-white/5 px-6 py-10 text-center text-sm text-slate-300']) }}>
    @if($icon)
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#12182B] text-xl shadow-md shadow-black/40">
            <span>{{ $resolvedIcon }}</span>
        </div>
    @endif

    <div>
        <h3 class="text-sm font-semibold text-slate-100">{{ $title }}</h3>

        @if($description)
            <p class="mt-1 text-xs text-slate-400">{{ $description }}</p>
        @endif
    </div>

    @if(trim($slot) !== '')
        <div class="mt-2 flex flex-wrap items-center justify-center gap-2 text-xs">
            {{ $slot }}
        </div>
    @endif
</div>
