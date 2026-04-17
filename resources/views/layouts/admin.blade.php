<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $description ?? 'Bảng điều khiển quản trị KeyLicense' }}">
    <title>{{ $title ?? 'Bảng điều khiển quản trị KeyLicense' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @include('partials.shared-theme')
    <style>
        .button.warn { background: linear-gradient(135deg, #d97706, #f59e0b); }
        .button.danger { background: linear-gradient(135deg, #dc2626, #ef4444); }
        .button.primary { background: linear-gradient(135deg, var(--accent), var(--accent2)); }
        .grid { display:grid; grid-template-columns: 280px minmax(0, 1fr); gap: 18px; align-items:start; }
        .notice { margin-bottom: 16px; padding: 12px 14px; border-radius: 16px; }
        .actions { display:flex; flex-wrap: wrap; gap: 10px; margin: 14px 0 18px; }
        .admin-shell { display:grid; grid-template-columns: 280px minmax(0, 1fr); gap: 18px; }
        .sidebar {
            position: sticky; top: 18px; height: calc(100vh - 36px); overflow:auto;
            padding: 20px; border-radius: 24px; border: 1px solid var(--border);
            background: rgba(15, 23, 42, .76); backdrop-filter: blur(18px);
        }
        .sidebar-brand { display:flex; align-items:center; gap:12px; margin-bottom: 18px; }
        .sidebar-title { font-weight: 700; font-size: 1.05rem; }
        .sidebar-nav { display:grid; gap: 8px; }
        .sidebar-link {
            display:flex; align-items:center; justify-content:space-between; gap:12px;
            padding: 12px 14px; border-radius: 14px; border: 1px solid transparent;
            background: rgba(2, 6, 23, .22);
        }
        .sidebar-link.active { border-color: rgba(124, 58, 237, .45); background: rgba(124, 58, 237, .18); }
        .sidebar-badge {
            display:inline-flex; align-items:center; padding: 4px 8px; border-radius: 999px;
            font-size: .75rem; background: rgba(148,163,184,.12); color: #dbeafe;
        }
        .sidebar-footer { margin-top: 18px; padding-top: 18px; border-top: 1px solid rgba(148,163,184,.12); }
        .admin-content { display:grid; gap: 16px; }
        .app-header {
            display:flex; justify-content:space-between; align-items:center; gap:16px;
            padding: 18px 20px; border-radius: 24px; border: 1px solid var(--border);
            background: rgba(15, 23, 42, .76); backdrop-filter: blur(18px);
        }
        .app-header-title { margin: 0; font-size: 1.35rem; }
        .app-header-actions { display:flex; gap: 10px; flex-wrap: wrap; }
        .app-footer {
            display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap;
            padding: 16px 20px; border-radius: 20px; border: 1px solid var(--border);
            background: rgba(15, 23, 42, .55);
        }
        .shell-toggle {
            display:none; align-items:center; justify-content:center; width:44px; height:44px; border-radius:14px;
            border:1px solid var(--border); background: rgba(2,6,23,.22); color: var(--text); cursor:pointer;
        }
        @media (max-width: 1024px) {
            .grid, .admin-shell { grid-template-columns: 1fr; }
            .sidebar { position: relative; top: 0; height: auto; display:none; }
            .admin-shell.is-open .sidebar { display:block; }
            .shell-toggle { display:inline-flex; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="wrap admin-shell" id="admin-shell">
        <x-ui.sidebar :items="$sidebarItems ?? []" />
        <main class="admin-content">
            <div style="display:flex;justify-content:flex-end;">
                <button type="button" class="shell-toggle" id="admin-shell-toggle" aria-label="Mở hoặc đóng menu">☰</button>
            </div>
            @yield('content')
            <x-ui.footer />
        </main>
    </div>
    <script>
        (() => {
            const shell = document.getElementById('admin-shell');
            const toggle = document.getElementById('admin-shell-toggle');
            if (!shell || !toggle) return;
            toggle.addEventListener('click', () => shell.classList.toggle('is-open'));
        })();
    </script>
    @stack('scripts')
</body>
</html>
