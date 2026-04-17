@props(['type' => 'info'])

@php
    $map = [
        'info' => 'background: rgba(59, 130, 246, .12); border: 1px solid rgba(59, 130, 246, .2); color: #dbeafe;',
        'success' => 'background: rgba(34, 197, 94, .12); border: 1px solid rgba(34, 197, 94, .22); color: #bbf7d0;',
        'warning' => 'background: rgba(245, 158, 11, .12); border: 1px solid rgba(245, 158, 11, .22); color: #fde68a;',
        'danger' => 'background: rgba(239, 68, 68, .12); border: 1px solid rgba(239, 68, 68, .24); color: #fecaca;',
    ];
@endphp

<div {{ $attributes->class(['notice']) }} style="{{ $map[$type] ?? $map['info'] }}">
    {{ $slot }}
</div>
