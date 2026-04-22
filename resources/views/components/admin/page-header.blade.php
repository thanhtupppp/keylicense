@props([
    'overline' => null,
    'title',
    'description' => null,
])

<div {{ $attributes->class('flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between') }}>
    <div>
        @if($overline)
            <p class="text-xs uppercase tracking-[0.24em] text-[#F8B803]">{{ $overline }}</p>
        @endif

        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-white sm:text-3xl">{{ $title }}</h1>

        @if($description)
            <p class="mt-2 max-w-2xl text-sm text-slate-400">{{ $description }}</p>
        @endif
    </div>

    @if(trim($slot) !== '')
        <div class="flex flex-wrap items-center gap-3">{{ $slot }}</div>
    @endif
</div>
