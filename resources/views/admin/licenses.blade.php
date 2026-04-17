@extends('layouts.admin', [
    'title' => 'Chi tiết license | KeyLicense',
    'description' => 'Xem chi tiết license, entitlement và activation',
    'sidebarItems' => [
        ['href' => route('admin.portal.dashboard'), 'label' => 'Tổng quan'],
        ['href' => route('admin.portal.sessions'), 'label' => 'Phiên đăng nhập'],
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
    <x-ui.header title="Chi tiết license" subtitle="Trạng thái license, policy, entitlement và activation">
        <x-ui.button :href="route('admin.portal.licenses')" variant="alt">← Danh sách license</x-ui.button>
        <div class="actions">
            <form method="POST" action="{{ route('admin.portal.licenses.suspend', ['id' => data_get($license, 'id')]) }}">
                @csrf
                <x-ui.button type="submit" variant="alt">Tạm khóa</x-ui.button>
            </form>
            <form method="POST" action="{{ route('admin.portal.licenses.extend', ['id' => data_get($license, 'id')]) }}">
                @csrf
                <x-ui.button type="submit" variant="alt">Gia hạn</x-ui.button>
            </form>
            <form method="POST" action="{{ route('admin.portal.licenses.revoke', ['id' => data_get($license, 'id')]) }}">
                @csrf
                <x-ui.button type="submit" variant="danger">Thu hồi license</x-ui.button>
            </form>
        </div>
    </x-ui.header>

    @if (session('status'))
        <x-ui.notice type="success">{{ session('status') }}</x-ui.notice>
    @endif

    <div class="grid cols-3">
        <x-ui.stat value="{{ data_get($license, 'status', 'N/A') }}" label="Trạng thái" />
        <x-ui.stat value="{{ optional(data_get($license, 'expires_at'))?->format('Y-m-d') ?? 'N/A' }}" label="Hết hạn" />
        <x-ui.stat value="{{ data_get($license, 'activations', collect())->count() }}" label="Activation" />
    </div>

    <div class="grid cols-2" style="margin-top:16px;">
        <section class="card stack">
            <x-ui.section-header title="Thông tin license" subtitle="Dữ liệu key và trạng thái hiện tại." />
            <div class="codebox">
                key_display: {{ data_get($license, 'key_display') }}<br>
                product_code: {{ data_get($license, 'entitlement.plan.product.code') ?? 'N/A' }}<br>
                plan_code: {{ data_get($license, 'entitlement.plan.code') ?? 'N/A' }}<br>
                customer: {{ data_get($license, 'entitlement.customer.email') ?? 'N/A' }}<br>
                entitlement_id: {{ data_get($license, 'entitlement_id') }}
            </div>
        </section>

        <aside class="stack">
            <section class="card">
                <x-ui.section-header title="Policy snapshot" subtitle="Snapshot policy tại thời điểm issue." />
                <div class="codebox">
                    offline_allowed: false<br>
                    grace_period_days: 7<br>
                    max_activations: 3<br>
                    features: EXPORT_CSV=true, MAX_KEYWORDS=500
                </div>
            </section>

            <section class="card">
                <x-ui.section-header title="Entitlement" subtitle="Lịch sử gói và chu kỳ billing." />
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Plan</th>
                                <th>Trạng thái</th>
                                <th>Ngày</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ data_get($license, 'entitlement.plan.name') ?? 'N/A' }}</td>
                                <td><span class="badge ok">{{ data_get($license, 'entitlement.status', 'active') }}</span></td>
                                <td>{{ optional(data_get($license, 'entitlement.starts_at'))?->format('Y-m-d') ?? 'N/A' }} → {{ optional(data_get($license, 'entitlement.expires_at'))?->format('Y-m-d') ?? 'N/A' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="actions">
                    <x-ui.button :href="route('admin.portal.entitlements.detail', ['id' => data_get($license, 'entitlement_id')])" variant="alt">Xem entitlement</x-ui.button>
                </div>
            </section>
        </aside>
    </div>

    <section class="card stack" style="margin-top:16px;">
        <x-ui.section-header title="Danh sách activation" subtitle="Thiết bị đang dùng license." />
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Thiết bị</th>
                        <th>Fingerprint</th>
                        <th>IP</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (data_get($license, 'activations', collect()) as $activation)
                        <tr>
                            <td>{{ data_get($activation, 'device_name') ?? data_get($activation, 'device_hostname') ?? 'N/A' }}</td>
                            <td class="mono">{{ data_get($activation, 'device_fingerprint') ?? 'N/A' }}</td>
                            <td>{{ data_get($activation, 'ip_address') ?? 'N/A' }}</td>
                            <td><span class="badge ok">{{ data_get($activation, 'status', 'active') }}</span></td>
                            <td>
                                <form method="POST" action="{{ route('admin.portal.licenses.revoke', ['id' => data_get($license, 'id')]) }}">
                                    @csrf
                                    <x-ui.button type="submit" variant="danger">Thu hồi activation</x-ui.button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <x-ui.table-empty colspan="5">Chưa có activation nào.</x-ui.table-empty>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
