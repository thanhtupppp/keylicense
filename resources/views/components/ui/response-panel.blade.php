@props([
    'title' => 'Response',
    'toastId' => null,
    'resultId' => null,
    'copyId' => null,
    'state' => 'idle',
])

@php
    $stateMap = [
        'idle' => ['label' => 'Chưa có response', 'type' => 'info'],
        'loading' => ['label' => 'Đang xử lý...', 'type' => 'info'],
        'success' => ['label' => 'Thành công', 'type' => 'success'],
        'error' => ['label' => 'Đã xảy ra lỗi', 'type' => 'danger'],
    ];
    $current = $stateMap[$state] ?? $stateMap['idle'];
@endphp

<x-ui.card>
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <div>
            <h2 style="margin-top:0;">{{ $title }}</h2>
            <span class="badge {{ $current['type'] === 'success' ? 'ok' : ($current['type'] === 'danger' ? 'current' : '') }}">{{ $current['label'] }}</span>
        </div>
        @if ($copyId)
            <x-ui.button type="button" variant="alt" id="{{ $copyId }}">Copy</x-ui.button>
        @endif
    </div>

    @if ($toastId)
        <x-ui.toast id="{{ $toastId }}" type="info" data-role="toast"></x-ui.toast>
    @endif

    @if ($resultId)
        <div id="{{ $resultId }}" class="codebox" data-role="result">Chưa có response.</div>
    @endif

    {{ $slot }}
</x-ui.card>
