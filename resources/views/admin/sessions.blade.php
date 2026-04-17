@extends('layouts.admin', [
    'title' => 'Quản lý phiên đăng nhập | KeyLicense',
    'description' => 'Quản lý phiên đăng nhập KeyLicense',
    'sidebarItems' => [
        ['href' => route('admin.portal.dashboard'), 'label' => 'Tổng quan'],
        ['href' => route('admin.portal.sessions'), 'label' => 'Phiên đăng nhập', 'active' => true],
        ['href' => route('admin.portal.api-keys'), 'label' => 'API Keys'],
        ['href' => route('admin.portal.coupons'), 'label' => 'Coupons'],
        ['href' => route('admin.portal.invoices'), 'label' => 'Hóa đơn'],
        ['href' => route('admin.portal.webhooks'), 'label' => 'Webhooks'],
        ['href' => route('admin.portal.metrics'), 'label' => 'Số liệu'],
        ['href' => route('admin.portal.trials'), 'label' => 'Trial'],
        ['href' => route('admin.portal.platform-config'), 'label' => 'Cấu hình'],
    ],
])

@section('content')
    <x-ui.header title="Quản lý phiên đăng nhập" subtitle="Theo dõi và thu hồi các phiên admin đang hoạt động">
        <x-ui.button :href="route('admin.portal.dashboard')" variant="alt">← Quay lại tổng quan</x-ui.button>
    </x-ui.header>

    @if (session('status'))
        <x-ui.notice type="success">{{ session('status') }}</x-ui.notice>
    @endif

    @if ($errors->any())
        <x-ui.notice type="danger">{{ $errors->first() }}</x-ui.notice>
    @endif

    <div class="grid">
        <section class="card stack">
            <x-ui.section-header title="Phiên đăng nhập" subtitle="Admin: {{ data_get($admin, 'email') }}" />

            <div class="actions">
                <form method="POST" action="{{ route('admin.portal.sessions.revoke_others') }}">
                    @csrf
                    <x-ui.button type="submit" variant="alt">Thu hồi tất cả trừ phiên hiện tại</x-ui.button>
                </form>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Device key</th>
                            <th>IP</th>
                            <th>User-Agent</th>
                            <th>Hoạt động gần nhất</th>
                            <th>Hết hạn</th>
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
                            <td>{{ optional($token->last_activity_at)?->format('Y-m-d H:i:s') ?? '-' }}</td>
                            <td>{{ optional($token->expires_at)?->format('Y-m-d H:i:s') ?? '-' }}</td>
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
                                        <x-ui.button type="submit" variant="danger">Thu hồi</x-ui.button>
                                    </form>
                                @else
                                    <span class="muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-ui.table-empty colspan="7">Không có phiên hoạt động.</x-ui.table-empty>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="stack">
            <section class="card">
                <x-ui.section-header title="Tổng quan phiên" subtitle="Tổng quan trạng thái phiên và logs." />
                <div class="stats">
                    <x-ui.stat :value="count($tokens)" label="Tổng phiên" />
                    <x-ui.stat :value="collect($tokens)->where('id', $current_token_id)->count()" label="Phiên hiện tại" />
                    <x-ui.stat :value="count($logs)" label="Audit logs" />
                </div>
            </section>

            <section class="card">
                <x-ui.section-header title="Audit log" subtitle="30 bản ghi gần nhất" />
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Thời gian</th>
                                <th>Sự kiện</th>
                                <th>Actor</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td>{{ optional($log->created_at)?->format('Y-m-d H:i:s') ?? '-' }}</td>
                                <td><span class="mono">{{ $log->event }}</span></td>
                                <td>{{ $log->actor_type }}</td>
                            </tr>
                        @empty
                            <x-ui.table-empty colspan="3">Chưa có audit log.</x-ui.table-empty>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </aside>
    </div>
@endsection
