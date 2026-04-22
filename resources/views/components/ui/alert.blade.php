@props([
    'type' => 'info',
    'title' => null,
    'message' => null,
])

@php
    $variants = [
        'success' => [
            'class' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-100',
            'icon' => '✓',
            'iconClass' => 'text-emerald-200',
        ],
        'error' => [
            'class' => 'border-rose-500/30 bg-rose-500/10 text-rose-100',
            'icon' => '!',
            'iconClass' => 'text-rose-200',
        ],
        'warning' => [
            'class' => 'border-amber-500/30 bg-amber-500/10 text-amber-100',
            'icon' => '!',
            'iconClass' => 'text-amber-200',
        ],
        'info' => [
            'class' => 'border-sky-500/30 bg-sky-500/10 text-sky-100',
            'icon' => 'i',
            'iconClass' => 'text-sky-200',
        ],
    ];

    $variant = $variants[$type] ?? $variants['info'];
    $content = $message ?? trim((string) $slot);
@endphp

<div {{ $attributes->class("overflow-hidden rounded-2xl border shadow-md shadow-black/30 {$variant['class']}") }} role="alert" data-flash-type="{{ $type }}">
    <div class="flex items-start gap-3 px-4 py-3 text-sm">
        <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-white/20 text-xs font-bold {{ $variant['iconClass'] }}">
            {{ $variant['icon'] }}
        </div>

        <div class="min-w-0 flex-1">
            @if($title)
                <div class="text-sm font-semibold leading-5 text-white">{{ $title }}</div>
            @endif

            @if($content !== '')
                <div class="mt-0.5 text-xs leading-5 text-inherit">{{ $content }}</div>
            @endif
        </div>

        <button type="button" class="ml-3 text-xs font-semibold uppercase tracking-[0.18em] text-white/70 transition hover:text-white" onclick="this.closest('[role=alert]')?.remove()">
            Đóng
        </button>
    </div>

    <div class="h-1 w-full bg-white/10">
        <div
            x-data="{ progress: 100, timer: null }"
            x-init="timer = setInterval(() => { progress = Math.max(0, progress - 2) }, 90); setTimeout(() => { clearInterval(timer); $el.closest('[role=alert]')?.remove(); }, 4500)"
            x-show="progress > 0"
            class="h-full origin-left bg-white/80 transition-[width] duration-100"
            :class="{
                'bg-emerald-300': '{{ $type }}' === 'success',
                'bg-rose-300': '{{ $type }}' === 'error',
                'bg-amber-300': '{{ $type }}' === 'warning',
                'bg-sky-300': '{{ $type }}' === 'info'
            }"
            :style="`width: ${progress}%`"
        ></div>
    </div>
</div>
