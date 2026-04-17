@props([
    'title' => null,
    'subtitle' => null,
])

<div {{ $attributes->class(['section-header']) }} style="display:grid;gap:6px;margin-bottom:18px;">
    @if ($title)
        <h2 style="margin:0;">{{ $title }}</h2>
    @endif
    @if ($subtitle)
        <p class="muted" style="margin:0;line-height:1.7;">{{ $subtitle }}</p>
    @endif
</div>
