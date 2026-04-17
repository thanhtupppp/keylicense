@props(['padding' => null])

<div {{ $attributes->class(['card']) }} @if($padding) style="padding: {{ $padding }};" @endif>
    {{ $slot }}
</div>
