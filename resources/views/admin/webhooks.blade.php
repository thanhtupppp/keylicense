@extends('layouts.admin', [
    'title' => 'Webhooks | KeyLicense',
    'description' => 'Quản lý webhook configs và deliveries',
    'sidebarItems' => [
        ['href' => route('admin.portal.dashboard'), 'label' => 'Tổng quan'],
        ['href' => route('admin.portal.sessions'), 'label' => 'Phiên đăng nhập'],
        ['href' => route('admin.portal.api-keys'), 'label' => 'API Keys'],
        ['href' => route('admin.portal.coupons'), 'label' => 'Coupons'],
        ['href' => route('admin.portal.invoices'), 'label' => 'Hóa đơn'],
        ['href' => route('admin.portal.webhooks'), 'label' => 'Webhooks', 'active' => true],
        ['href' => route('admin.portal.metrics'), 'label' => 'Số liệu'],
        ['href' => route('admin.portal.trials'), 'label' => 'Trial'],
        ['href' => route('admin.portal.platform-config'), 'label' => 'Cấu hình'],
    ],
])

@section('content')
    <x-ui.header title="Webhooks" subtitle="Theo dõi cấu hình và lịch sử delivery">
        <x-ui.button :href="route('admin.portal.dashboard')" variant="alt">← Quay lại tổng quan</x-ui.button>
    </x-ui.header>

    @php
        $configs = $configs ?? collect();
        $deliveries = $deliveries ?? collect();
    @endphp

    <div class="grid cols-2" style="margin-top:16px;">
        <section class="card stack">
            <x-ui.section-header title="Webhook configs" subtitle="Khai báo URL, secret và event subscriptions." />
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>URL</th>
                            <th>Sự kiện</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($configs as $config)
                            <tr>
                                <td class="mono">{{ $config->url }}</td>
                                <td>{{ implode(', ', $config->events ?? []) }}</td>
                                <td>
                                    @if ($config->is_active)
                                        <span class="badge ok">Hoạt động</span>
                                    @else
                                        <span class="badge current">Tắt</span>
                                    @endif
                                </td>
                                <td>
                                    <x-ui.button :href="route('admin.portal.webhook-delivery', ['id' => $config->id])" variant="alt">Xem delivery</x-ui.button>
                                </td>
                            </tr>
                        @empty
                            <x-ui.table-empty colspan="4">Chưa có webhook config nào.</x-ui.table-empty>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card stack">
            <x-ui.section-header title="Deliveries" subtitle="Lịch sử gửi webhook và retry." />
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Delivery</th>
                            <th>Event</th>
                            <th>Status</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($deliveries as $delivery)
                            <tr>
                                <td class="mono">{{ $delivery->id }}</td>
                                <td>{{ $delivery->event }}</td>
                                <td><span class="badge {{ ($delivery->status_code ?? 0) >= 200 && ($delivery->status_code ?? 0) < 300 ? 'ok' : 'current' }}">{{ $delivery->status_code ?? 'N/A' }}</span></td>
                                <td><x-ui.button :href="route('admin.portal.webhook-delivery', ['id' => $delivery->id])" variant="alt">Chi tiết</x-ui.button></td>
                            </tr>
                        @empty
                            <x-ui.table-empty colspan="4">Chưa có delivery nào.</x-ui.table-empty>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
