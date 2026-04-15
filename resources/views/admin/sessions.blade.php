<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý phiên đăng nhập - License Platform</title>
    <style>
        body { margin:0; font-family: Inter, system-ui, sans-serif; background:#0b1220; color:#e2e8f0; }
        .wrap { max-width: 1200px; margin: 32px auto; padding: 0 16px; }
        .card { background:#111827; border:1px solid #1f2937; border-radius: 12px; padding:16px; margin-bottom: 14px; }
        table { width:100%; border-collapse: collapse; font-size: .92rem; }
        th, td { padding: 10px; border-bottom: 1px solid #243041; text-align: left; vertical-align: top; }
        .badge { display:inline-block; padding:2px 8px; border-radius: 999px; font-size: .75rem; }
        .ok { background:#064e3b; color:#a7f3d0; }
        .current { background:#1e3a8a; color:#bfdbfe; }
        .muted { color:#94a3b8; }
        .danger { background:#dc2626; color:#fff; border:0; border-radius:8px; padding:8px 10px; cursor:pointer; }
        .warning { background:#f59e0b; color:#111827; border:0; border-radius:8px; padding:8px 10px; cursor:pointer; font-weight:700; }
        .nav { margin-bottom: 14px; }
        a { color:#93c5fd; text-decoration: none; }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: .84rem; word-break: break-all; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="nav">
        <a href="{{ route('admin.portal.dashboard') }}">← Dashboard</a>
    </div>

    <div class="card">
        <h2>Quản lý phiên đăng nhập</h2>
        <p class="muted">Admin: {{ data_get($admin, 'email') }}</p>

        @if (session('status'))
            <p style="color:#86efac;">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('admin.portal.sessions.revoke_others') }}" style="margin: 8px 0 14px 0;">
            @csrf
            <button class="warning" type="submit">Revoke all except current session</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Device key</th>
                    <th>IP gần nhất</th>
                    <th>User-Agent gần nhất</th>
                    <th>Last activity</th>
                    <th>Expires at</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($tokens as $token)
                <tr>
                    <td class="mono">{{ $token->device_key }}</td>
                    <td>{{ $token->last_ip ?? '-' }}</td>
                    <td class="mono">{{ $token->last_user_agent ?? '-' }}</td>
                    <td>{{ optional($token->last_activity_at)?->format('Y-m-d H:i:s') }}</td>
                    <td>{{ optional($token->expires_at)?->format('Y-m-d H:i:s') }}</td>
                    <td>
                        @if ($current_token_id === $token->id)
                            <span class="badge current">Phiên hiện tại</span>
                        @else
                            <span class="badge ok">Đang hoạt động</span>
                        @endif
                    </td>
                    <td>
                        @if ($current_token_id !== $token->id)
                            <form method="POST" action="{{ route('admin.portal.sessions.revoke') }}">
                                @csrf
                                <input type="hidden" name="token_id" value="{{ $token->id }}">
                                <button class="danger" type="submit">Revoke</button>
                            </form>
                        @else
                            <span class="muted">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="muted">Không có phiên hoạt động.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>Audit log (30 bản ghi gần nhất)</h3>
        <table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Event</th>
                    <th>Actor</th>
                    <th>IP</th>
                    <th>User-Agent</th>
                    <th>Metadata</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td>{{ optional($log->created_at)?->format('Y-m-d H:i:s') }}</td>
                    <td><span class="mono">{{ $log->event }}</span></td>
                    <td>{{ $log->actor_type }}</td>
                    <td>{{ $log->ip_address ?? '-' }}</td>
                    <td class="mono">{{ $log->user_agent ?? '-' }}</td>
                    <td class="mono">{{ json_encode($log->metadata ?? [], JSON_UNESCAPED_UNICODE) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="muted">Chưa có audit log.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
