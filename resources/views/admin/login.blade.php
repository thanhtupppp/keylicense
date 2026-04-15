<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - License Platform</title>
    <style>
        :root { color-scheme: light dark; }
        body {
            margin: 0;
            font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            display: grid;
            place-items: center;
        }
        .card {
            width: 100%;
            max-width: 420px;
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 20px 50px rgba(0,0,0,.35);
        }
        h1 { margin: 0 0 8px; font-size: 1.35rem; }
        p { margin: 0 0 20px; color: #94a3b8; font-size: .95rem; }
        .group { margin-bottom: 14px; }
        label { display: block; margin-bottom: 6px; font-size: .88rem; color: #cbd5e1; }
        input {
            width: 100%;
            box-sizing: border-box;
            padding: 11px 12px;
            border-radius: 10px;
            border: 1px solid #334155;
            background: #0b1220;
            color: #e2e8f0;
            outline: none;
        }
        input:focus { border-color: #3b82f6; }
        .error {
            margin-bottom: 12px;
            background: #7f1d1d;
            border: 1px solid #ef4444;
            color: #fecaca;
            padding: 10px;
            border-radius: 10px;
            font-size: .9rem;
        }
        button {
            width: 100%;
            margin-top: 6px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 11px 12px;
            font-weight: 600;
            cursor: pointer;
        }
        button:hover { background: #1d4ed8; }
        .helper { margin-top: 12px; color: #94a3b8; font-size: .8rem; }
        .helper code { color: #e2e8f0; }
    </style>
</head>
<body>
    <main class="card">
        <h1>Đăng nhập Admin</h1>
        <p>License Platform Control Panel</p>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.portal.login.submit') }}">
            @csrf

            <div class="group">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" />
            </div>

            <div class="group">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required autocomplete="current-password" />
            </div>

            <div class="group" style="display:flex;align-items:center;gap:8px;">
                <input id="remember" name="remember" type="checkbox" value="1" style="width:auto;" {{ old('remember') ? 'checked' : '' }} />
                <label for="remember" style="margin:0;">Remember me (giữ đăng nhập lâu hơn)</label>
            </div>

            <button type="submit">Đăng nhập</button>
        </form>

        <div class="helper">
            Dev default: <code>admin@internal.local</code> / <code>secret-password</code>
        </div>
    </main>
</body>
</html>
