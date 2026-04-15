<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - License Platform</title>
    <style>
        body {
            margin: 0;
            font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
            background: #0b1220;
            color: #e2e8f0;
        }
        .wrap { max-width: 980px; margin: 32px auto; padding: 0 16px; }
        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }
        .card {
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 14px;
        }
        .muted { color: #94a3b8; }
        .countdown {
            font-weight: 700;
            color: #f59e0b;
        }
        .warn {
            background: #78350f;
            border: 1px solid #f59e0b;
            color: #fef3c7;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 12px;
        }
        .token {
            word-break: break-all;
            background: #020617;
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 10px;
            font-size: .86rem;
        }
        button {
            background: #dc2626;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 10px 14px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="wrap">
        @if (session('admin_warning'))
            <div class="warn">{{ session('admin_warning') }}</div>
        @endif

        <div class="top">
            <h1>Admin Dashboard</h1>
            <form method="POST" action="{{ route('admin.portal.logout') }}">
                @csrf
                <button type="submit">Đăng xuất</button>
            </form>
        </div>

        <section class="card">
            <h3>Thông tin đăng nhập</h3>
            <p class="muted">Tên: {{ data_get($admin, 'full_name', 'N/A') }}</p>
            <p class="muted">Email: {{ data_get($admin, 'email', 'N/A') }}</p>
            <p class="muted">Session timeout: {{ $session_timeout }} giây</p>
            <p>Session còn lại: <span id="session-countdown" class="countdown">--:--</span></p>
        </section>

        <section class="card">
            <h3>Admin API token (session)</h3>
            <div class="token">{{ $token }}</div>
            <p class="muted">Token đang được lưu trong session server-side và dùng để gọi API admin.</p>
        </section>

        <section class="card">
            <h3>Phiên đăng nhập</h3>
            <p><a href="{{ route('admin.portal.sessions') }}" style="color:#93c5fd;">Mở trang Quản lý phiên đăng nhập</a></p>
        </section>

        <section class="card">
            <h3>API MVP sẵn sàng</h3>
            <ul>
                <li>POST /api/v1/admin/products</li>
                <li>POST /api/v1/admin/plans</li>
                <li>POST /api/v1/admin/entitlements</li>
                <li>POST /api/v1/admin/licenses/issue</li>
                <li>POST /api/v1/client/licenses/activate</li>
                <li>POST /api/v1/client/licenses/validate</li>
            </ul>
        </section>
    </div>

    <script>
        (function () {
            const expiresAtRaw = @json($session_expires_at);
            const countdown = document.getElementById('session-countdown');

            if (!expiresAtRaw || !countdown) {
                return;
            }

            const expiresAt = new Date(expiresAtRaw).getTime();

            const tick = () => {
                const now = Date.now();
                const diff = Math.max(0, Math.floor((expiresAt - now) / 1000));

                const mm = String(Math.floor(diff / 60)).padStart(2, '0');
                const ss = String(diff % 60).padStart(2, '0');
                countdown.textContent = `${mm}:${ss}`;

                if (diff <= 0) {
                    window.location.href = @json(route('admin.portal.login'));
                }
            };

            tick();
            setInterval(tick, 1000);
        })();
    </script>
</body>
</html>
