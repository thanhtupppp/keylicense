@extends('layouts.admin', [
    'title' => 'Chi tiết entitlement | KeyLicense',
    'description' => 'Thông tin entitlement, plan và các license liên kết',
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
    <x-ui.header title="Chi tiết entitlement" subtitle="Thông tin gói, thời hạn và license liên kết">
        <x-ui.button :href="route('admin.portal.licenses')" variant="alt">← Danh sách license</x-ui.button>
    </x-ui.header>

    <div class="grid cols-3">
        <x-ui.stat value="{{ data_get($entitlement, 'status', 'N/A') }}" label="Trạng thái" />
        <x-ui.stat value="{{ optional(data_get($entitlement, 'expires_at'))?->format('Y-m-d') ?? 'N/A' }}" label="Hết hạn" />
        <x-ui.stat value="{{ data_get($entitlement, 'max_activations', 0) }}" label="Max activations" />
    </div>

    <div class="grid cols-2" style="margin-top:16px;">
        <section class="card stack">
            <x-ui.section-header title="Thông tin entitlement" subtitle="Tổng quan gói và khách hàng." />
            <div class="codebox">
                entitlement_id: {{ data_get($entitlement, 'id') }}<br>
                plan: {{ data_get($entitlement, 'plan.name') }}<br>
                product: {{ data_get($entitlement, 'plan.product.code') }}<br>
                customer_id: {{ data_get($entitlement, 'customer_id') ?? 'N/A' }}<br>
                org_id: {{ data_get($entitlement, 'org_id') ?? 'N/A' }}
            </div>
        </section>

        <aside class="stack">
            <section class="card">
                <x-ui.section-header title="Billing window" subtitle="Mốc bắt đầu và kết thúc entitlement." />
                <div class="codebox">
                    starts_at: {{ optional(data_get($entitlement, 'starts_at'))?->format('Y-m-d H:i') ?? 'N/A' }}<br>
                    expires_at: {{ optional(data_get($entitlement, 'expires_at'))?->format('Y-m-d H:i') ?? 'N/A' }}<br>
                    auto_renew: {{ data_get($entitlement, 'auto_renew') ? 'true' : 'false' }}
                </div>
            </section>
        </aside>
    </div>

    <section class="card stack" style="margin-top:16px;">
        <x-ui.section-header title="Licenses liên kết" subtitle="Danh sách license thuộc entitlement này." />
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>License</th>
                        <th>Trạng thái</th>
                        <th>Hết hạn</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (data_get($entitlement, 'licenses', collect()) as $license)
                        <tr>
                            <td class="mono">{{ data_get($license, 'key_display') ?? data_get($license, 'license_key') }}</td>
                            <td><span class="badge ok">{{ data_get($license, 'status') }}</span></td>
                            <td>{{ optional(data_get($license, 'expires_at'))?->format('Y-m-d') ?? 'N/A' }}</td>
                            <td><x-ui.button :href="route('admin.portal.licenses.detail', ['id' => data_get($license, 'id')])" variant="alt">Chi tiết</x-ui.button></td>
                        </tr>
                    @empty
                        <x-ui.table-empty colspan="4">Chưa có license nào.</x-ui.table-empty>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
