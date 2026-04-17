@props([
    'title' => null,
    'subtitle' => null,
])

<header {{ $attributes->class(['app-header']) }}>
    <div>
        @if ($title)
            <h1 class="app-header-title">{{ $title }}</h1>
        @endif
        @if ($subtitle)
            <div class="muted">{{ $subtitle }}</div>
        @endif
    </div>

    <div class="app-header-actions">
        {{ $slot }}
    </div>
</header>
