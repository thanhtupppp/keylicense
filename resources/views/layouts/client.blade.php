<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $description ?? 'Cổng khách hàng KeyLicense' }}">
    <title>{{ $title ?? 'Cổng khách hàng KeyLicense' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @include('partials.shared-theme')
    <style>
        .client-shell { display:grid; gap: 18px; }
        .client-header, .client-footer {
            display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap;
            padding: 18px 20px; border-radius: 24px; border: 1px solid var(--border);
            background: rgba(15, 23, 42, .76); backdrop-filter: blur(18px);
        }
        .client-nav { display:flex; gap:10px; flex-wrap:wrap; }
        .client-nav a { padding: 10px 14px; border-radius: 14px; border: 1px solid rgba(148,163,184,.16); background: rgba(2,6,23,.22); }
        .client-nav a:hover { border-color: rgba(124,58,237,.45); }
        .shell-toggle {
            display:none; align-items:center; justify-content:center; width:44px; height:44px; border-radius:14px;
            border:1px solid var(--border); background: rgba(2,6,23,.22); color: var(--text); cursor:pointer;
        }
        @media (max-width: 900px) {
            .client-nav { display:none; width:100%; }
            .client-shell.is-open .client-nav { display:flex; }
            .client-header { align-items:flex-start; }
            .shell-toggle { display:inline-flex; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="wrap client-shell" id="client-shell">
        <header class="client-header">
            <div class="brand">
                <span class="mark"></span>
                <div>
                    <div class="sidebar-title">Cổng khách hàng KeyLicense</div>
                    <div class="muted">Quản lý license, hóa đơn và subscription</div>
                </div>
            </div>
            <button type="button" class="shell-toggle" id="client-shell-toggle" aria-label="Mở hoặc đóng menu">☰</button>
            <nav class="client-nav">
                <a href="{{ route('client.portal') }}">Tổng quan</a>
                <a href="{{ route('client.licenses') }}">License</a>
                <a href="{{ route('client.invoices') }}">Hóa đơn</a>
                <a href="{{ route('client.subscriptions') }}">Subscription</a>
                <a href="{{ route('client.notifications') }}">Thông báo</a>
                <a href="{{ route('client.auth') }}">Tài khoản</a>
                <a href="{{ route('client.gdpr') }}">GDPR</a>
                <a href="{{ route('client.activate') }}">Kích hoạt</a>
                <a href="{{ route('client.validate') }}">Xác thực</a>
                <a href="{{ route('client.offline') }}">Ngoại tuyến</a>
                <a href="{{ route('client.deactivate') }}">Thu hồi</a>
            </nav>
        </header>

        <main>
            @yield('content')
        </main>

        <x-ui.footer />
    </div>
    <script>
        (() => {
            const shell = document.getElementById('client-shell');
            const toggle = document.getElementById('client-shell-toggle');
            if (!shell || !toggle) return;
            toggle.addEventListener('click', () => shell.classList.toggle('is-open'));
        })();
    </script>
    @include('partials.client-scripts')
    @stack('scripts')
</body>
</html>
