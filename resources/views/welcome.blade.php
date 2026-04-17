<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="KeyLicense - Nền tảng quản lý license, kích hoạt sản phẩm và tự động hóa vận hành cho đội SaaS.">
    <title>KeyLicense | Nền tảng cấp license</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            :root {
                --bg: #050816;
                --bg2: #0b1220;
                --card: rgba(15, 23, 42, .72);
                --card-border: rgba(148, 163, 184, .16);
                --text: #e5eefc;
                --muted: #94a3b8;
                --accent: #7c3aed;
                --accent2: #22c55e;
                --accent3: #38bdf8;
            }
            * { box-sizing: border-box; }
            body {
                margin: 0;
                min-height: 100vh;
                font-family: 'Instrument Sans', system-ui, sans-serif;
                color: var(--text);
                background:
                    radial-gradient(circle at top left, rgba(124, 58, 237, .32), transparent 34%),
                    radial-gradient(circle at 85% 15%, rgba(56, 189, 248, .22), transparent 28%),
                    radial-gradient(circle at bottom right, rgba(34, 197, 94, .14), transparent 30%),
                    linear-gradient(180deg, var(--bg), var(--bg2));
            }
            a { color: inherit; text-decoration: none; }
            .shell { width: min(1180px, calc(100% - 32px)); margin: 0 auto; }
            .nav {
                display: flex; justify-content: space-between; align-items: center;
                padding: 24px 0;
            }
            .brand { display: flex; gap: 12px; align-items: center; font-weight: 700; }
            .brand-mark {
                width: 40px; height: 40px; border-radius: 14px;
                background: linear-gradient(135deg, var(--accent), var(--accent3));
                box-shadow: 0 10px 30px rgba(124, 58, 237, .28);
            }
            .nav-links { display: flex; gap: 14px; align-items: center; }
            .pill, .button {
                display: inline-flex; align-items: center; justify-content: center;
                padding: 12px 18px; border-radius: 999px; font-weight: 600;
            }
            .pill { border: 1px solid var(--card-border); background: rgba(15, 23, 42, .38); }
            .button { background: linear-gradient(135deg, var(--accent), #2563eb); box-shadow: 0 18px 40px rgba(37, 99, 235, .28); }
            .hero { padding: 56px 0 40px; display: grid; grid-template-columns: 1.1fr .9fr; gap: 28px; align-items: center; }
            .eyebrow {
                display: inline-flex; gap: 8px; align-items: center; padding: 8px 12px;
                border-radius: 999px; border: 1px solid rgba(56, 189, 248, .22);
                background: rgba(8, 47, 73, .38); color: #c7f0ff; font-size: .92rem;
            }
            h1 { margin: 18px 0 16px; font-size: clamp(2.8rem, 6vw, 5.3rem); line-height: .95; letter-spacing: -.05em; }
            .lead { font-size: 1.1rem; line-height: 1.75; color: var(--muted); max-width: 60ch; }
            .actions { display: flex; gap: 14px; flex-wrap: wrap; margin-top: 28px; }
            .secondary { border: 1px solid var(--card-border); background: rgba(15, 23, 42, .35); }
            .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-top: 28px; }
            .stat, .feature, .panel {
                border: 1px solid var(--card-border); background: var(--card); backdrop-filter: blur(16px);
                border-radius: 24px; box-shadow: 0 24px 80px rgba(2, 6, 23, .35);
            }
            .stat { padding: 18px; }
            .stat strong { display: block; font-size: 1.55rem; margin-bottom: 6px; }
            .stat span, .feature p, .panel p { color: var(--muted); }
            .mockup { padding: 22px; }
            .panel { padding: 22px; }
            .panel-top { display:flex; justify-content:space-between; align-items:center; margin-bottom: 18px; }
            .badge { padding: 8px 12px; border-radius: 999px; background: rgba(34, 197, 94, .12); color: #b7f7cf; border: 1px solid rgba(34, 197, 94, .18); }
            .meter { display:grid; gap: 12px; margin-top: 18px; }
            .meter-row { display:flex; justify-content:space-between; color: var(--muted); font-size: .95rem; }
            .bar { height: 10px; border-radius: 999px; background: rgba(148, 163, 184, .14); overflow: hidden; }
            .bar > span { display:block; height:100%; border-radius:inherit; background: linear-gradient(90deg, var(--accent3), var(--accent2)); }
            .section { padding: 22px 0 72px; }
            .section h2 { font-size: clamp(1.7rem, 3vw, 2.4rem); margin: 0 0 10px; }
            .grid { display:grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 20px; }
            .feature { padding: 20px; }
            .feature h3 { margin: 14px 0 8px; }
            .icon {
                width: 44px; height: 44px; border-radius: 14px; display:grid; place-items:center;
                background: rgba(124, 58, 237, .14); color: #d8b4fe; border: 1px solid rgba(124, 58, 237, .18);
                font-weight: 700;
            }
            .footer { padding: 10px 0 30px; color: var(--muted); font-size: .95rem; }
            @media (max-width: 900px) {
                .hero, .grid, .stats { grid-template-columns: 1fr; }
                h1 { font-size: clamp(2.4rem, 12vw, 4.8rem); }
            }
        </style>
    @endif
</head>
<body>
    <div class="shell">
        <header class="nav">
            <a class="brand" href="#top">
                <span class="brand-mark"></span>
                <span>KeyLicense</span>
            </a>
            <nav class="nav-links">
                <a class="pill" href="{{ route('admin.portal.login') }}">Cổng quản trị</a>
                @auth
                    <a class="button" href="{{ route('admin.portal.dashboard') }}">Bảng điều khiển</a>
                @else
                    <a class="button" href="{{ route('admin.portal.login') }}">Đăng nhập</a>
                @endauth
            </nav>
        </header>

        <main id="top" class="hero">
            <section>
                <span class="eyebrow">Nền tảng cấp license cho SaaS hiện đại</span>
                <h1>Thiết kế hệ thống cấp license, kích hoạt và quản trị thật gọn.</h1>
                <p class="lead">
                    KeyLicense giúp bạn quản lý vòng đời license, kích hoạt ngoại tuyến, grace period và dashboard vận hành
                    với trải nghiệm rõ ràng, sang trọng và dễ mở rộng cho team sản phẩm.
                </p>
                <div class="actions">
                    <a class="button" href="{{ route('admin.portal.login') }}">Vào cổng quản trị</a>
                    <a class="pill secondary" href="#features">Xem tính năng</a>
                </div>

                <div class="stats">
                    <div class="stat">
                        <strong>24/7</strong>
                        <span>Giám sát license và webhook liên tục</span>
                    </div>
                    <div class="stat">
                        <strong>Ngoại tuyến</strong>
                        <span>Hỗ trợ kích hoạt challenge không cần online</span>
                    </div>
                    <div class="stat">
                        <strong>Nhanh</strong>
                        <span>Luồng quản trị tối ưu cho đội vận hành</span>
                    </div>
                </div>
            </section>

            <aside class="panel mockup">
                <div class="panel-top">
                    <div>
                        <div style="font-weight:700;font-size:1.05rem;">Sức khỏe hệ thống</div>
                        <p style="margin:6px 0 0;">License, dunning, session, webhook</p>
                    </div>
                    <span class="badge">Đang vận hành</span>
                </div>

                <div class="meter">
                    <div>
                        <div class="meter-row"><span>Xác thực license</span><span>98%</span></div>
                        <div class="bar"><span style="width:98%"></span></div>
                    </div>
                    <div>
                        <div class="meter-row"><span>Phân phối webhook</span><span>91%</span></div>
                        <div class="bar"><span style="width:91%"></span></div>
                    </div>
                    <div>
                        <div class="meter-row"><span>Quét thời gian gia hạn</span><span>87%</span></div>
                        <div class="bar"><span style="width:87%"></span></div>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:18px;">
                    <div class="stat" style="margin:0;">
                        <strong>1.2k</strong>
                        <span>Lượt yêu cầu / ngày</span>
                    </div>
                    <div class="stat" style="margin:0;">
                        <strong>99.9%</strong>
                        <span>Thời gian hoạt động</span>
                    </div>
                </div>
            </aside>
        </main>

        <section id="features" class="section">
            <h2>Điểm nhấn frontend</h2>
            <p class="lead">Mình làm lại UI theo phong cách tối hiện đại, tập trung vào độ tin cậy và cảm giác SaaS cao cấp.</p>

            <div class="grid">
                <article class="feature">
                    <div class="icon">A</div>
                    <h3>Sẵn sàng cho quản trị</h3>
                    <p>Trang đăng nhập và bảng điều khiển được đồng bộ ngôn ngữ thiết kế, dễ dẫn dắt người dùng quản trị.</p>
                </article>
                <article class="feature">
                    <div class="icon">B</div>
                    <h3>Rõ ràng, dễ đọc</h3>
                    <p>Typography, khoảng cách và độ tương phản được tối ưu để thao tác nhanh trong môi trường vận hành.</p>
                </article>
                <article class="feature">
                    <div class="icon">C</div>
                    <h3>Thích ứng tốt</h3>
                    <p>Giao diện co giãn tốt trên desktop và mobile, phù hợp cả demo lẫn sử dụng thật.</p>
                </article>
            </div>
        </section>

        <footer class="footer">
            © {{ date('Y') }} KeyLicense. Xây dựng cho cấp license, kích hoạt và vận hành.
        </footer>
    </div>
</body>
</html>
