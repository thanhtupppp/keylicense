@props(['type' => 'info', 'id' => null, 'message' => null])

@php
    $map = [
        'info' => ['border' => 'rgba(59, 130, 246, .2)', 'bg' => 'rgba(59, 130, 246, .12)', 'color' => '#dbeafe'],
        'success' => ['border' => 'rgba(34, 197, 94, .22)', 'bg' => 'rgba(34, 197, 94, .12)', 'color' => '#bbf7d0'],
        'warning' => ['border' => 'rgba(245, 158, 11, .22)', 'bg' => 'rgba(245, 158, 11, .12)', 'color' => '#fde68a'],
        'danger' => ['border' => 'rgba(239, 68, 68, .24)', 'bg' => 'rgba(239, 68, 68, .12)', 'color' => '#fecaca'],
    ];
    $theme = $map[$type] ?? $map['info'];
@endphp

<div @if($id) id="{{ $id }}" @endif {{ $attributes->class(['toast']) }} data-toast-type="{{ $type }}" style="display:none; padding: 12px 14px; border-radius: 16px; border: 1px solid {{ $theme['border'] }}; background: {{ $theme['bg'] }}; color: {{ $theme['color'] }};">
    {{ $message ?? $slot }}
</div>
