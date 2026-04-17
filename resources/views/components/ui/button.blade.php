@props([
    'href' => null,
    'variant' => 'primary',
    'type' => 'button',
])

@php
    $base = 'button';
    $variants = [
        'primary' => '',
        'alt' => 'alt',
        'danger' => 'danger',
    ];
    $classes = trim($base . ' ' . ($variants[$variant] ?? ''));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class([$classes]) }}>{{ $slot }}</button>
@endif
