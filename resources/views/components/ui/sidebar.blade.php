@props([
    'items' => [],
])

<aside {{ $attributes->class(['sidebar']) }}>
    <div class="sidebar-brand">
        <span class="mark"></span>
        <div>
            <div class="sidebar-title">KeyLicense</div>
            <div class="muted">Bảng điều khiển quản trị</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        @foreach ($items as $item)
            <a href="{{ $item['href'] }}" class="sidebar-link {{ ($item['active'] ?? false) ? 'active' : '' }}">
                <span class="sidebar-link-label">{{ $item['label'] }}</span>
                @if (!empty($item['badge']))
                    <span class="sidebar-badge">{{ $item['badge'] }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    <div class="sidebar-footer">
        <div class="muted" style="font-size:.9rem;">Khu vực quản trị an toàn</div>
        <div class="mono" style="margin-top:6px;">v1.0</div>
    </div>
</aside>
