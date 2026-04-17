@extends('layouts.admin', [
    'title' => 'Bảng điều khiển quản trị | KeyLicense',
    'description' => 'Bảng điều khiển quản trị KeyLicense',
    'sidebarItems' => [
        ['href' => route('admin.portal.dashboard'), 'label' => 'Tổng quan', 'active' => true],
        ['href' => route('admin.portal.sessions'), 'label' => 'Phiên đăng nhập', 'badge' => '12'],
        ['href' => '#billing', 'label' => 'Thanh toán'],
        ['href' => '#settings', 'label' => 'Cài đặt'],
    ],
])

@section('content')
    <x-ui.header title="Bảng điều khiển quản trị" subtitle="Trung tâm điều khiển nền tảng cấp phép">
        <form method="POST" action="{{ route('admin.portal.logout') }}">
            @csrf
            <x-ui.button type="submit" variant="danger">Đăng xuất</x-ui.button>
        </form>
    </x-ui.header>

    @if (session('admin_warning'))
        <x-ui.notice type="warning">{{ session('admin_warning') }}</x-ui.notice>
    @endif

    <div class="grid">
        <div class="stack">
            <section class="card">
                <h2 style="margin-top:0;">Thông tin đăng nhập</h2>
                <p class="muted">Tên: {{ data_get($admin, 'full_name', 'N/A') }}</p>
                <p class="muted">Email: {{ data_get($admin, 'email', 'N/A') }}</p>
                <p class="muted">Thời gian chờ phiên: {{ $session_timeout }} giây</p>
                <p>Thời gian còn lại: <span id="session-countdown" style="font-size:2rem;font-weight:700;color:#fbbf24;">--:--</span></p>
                <div class="stats">
                    <x-ui.stat value="An toàn" label="Token lưu phía máy chủ" />
                    <x-ui.stat value="Nhanh" label="Luồng quản trị tối ưu" />
                    <x-ui.stat value="Trực tiếp" label="Theo dõi phiên liên tục" />
                </div>
            </section>

            <section class="card">
                <h2 style="margin-top:0;">Các API MVP đã sẵn sàng</h2>
                <ul style="padding-left:18px;line-height:1.9;color:#dbeafe;">
                    <li>POST /api/v1/admin/products</li>
                    <li>POST /api/v1/admin/plans</li>
                    <li>POST /api/v1/admin/entitlements</li>
                    <li>POST /api/v1/admin/licenses/issue</li>
                    <li>POST /api/v1/client/licenses/activate</li>
                    <li>POST /api/v1/client/licenses/validate</li>
                </ul>
            </section>
        </div>

        <div class="stack">
            <section class="card">
                <h2 style="margin-top:0;">Token API quản trị</h2>
                <div class="mono" style="background: rgba(2, 6, 23, .62); border: 1px solid rgba(148, 163, 184, .16); border-radius: 16px; padding: 14px; line-height:1.7;">{{ $token }}</div>
                <p class="muted">Token được lưu trong session phía máy chủ và dùng cho các thao tác nội bộ.</p>
            </section>

            <section class="card">
                <h2 style="margin-top:0;">Phiên đăng nhập</h2>
                <p class="muted">Quản lý các phiên hoạt động, thu hồi nhanh khi cần.</p>
                <x-ui.button :href="route('admin.portal.sessions')">Mở trang Phiên đăng nhập</x-ui.button>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const expiresAtRaw = @json($session_expires_at);
            const countdown = document.getElementById('session-countdown');

            if (!expiresAtRaw || !countdown) return;

            const expiresAt = new Date(expiresAtRaw).getTime();
            const tick = () => {
                const diff = Math.max(0, Math.floor((expiresAt - Date.now()) / 1000));
                const mm = String(Math.floor(diff / 60)).padStart(2, '0');
                const ss = String(diff % 60).padStart(2, '0');
                countdown.textContent = `${mm}:${ss}`;
                if (diff <= 0) window.location.href = @json(route('admin.portal.login'));
            };

            tick();
            setInterval(tick, 1000);
        })();
    </script>
@endpush
