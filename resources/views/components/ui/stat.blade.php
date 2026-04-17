@props(['value', 'label'])

<div {{ $attributes->class(['stat']) }}>
    <strong>{{ $value }}</strong>
    <span class="muted">{{ $label }}</span>
</div>
