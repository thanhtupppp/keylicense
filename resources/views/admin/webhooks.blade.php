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

    <section class="card stack">
        <x-ui.section-header title="Bộ lọc" subtitle="Tìm webhook config hoặc delivery theo event, status và endpoint." />
        <div class="grid cols-4">
            <x-ui.input label="Tìm kiếm" name="q" placeholder="license.revoked, del_abc123" />
            <x-ui.input label="Sự kiện" name="event" placeholder="license.revoked" />
            <x-ui.input label="Trạng thái" name="status" placeholder="200 / failed" />
            <x-ui.input label="Endpoint" name="endpoint" placeholder="https://example.com/webhook" />
        </div>
        <div class="actions">
            <x-ui.button>Lọc</x-ui.button>
            <x-ui.button variant="alt">Xóa lọc</x-ui.button>
        </div>
    </section>

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
                        <tr>
                            <td class="mono">https://example.com/webhook</td>
                            <td>license.revoked, license.renewed</td>
                            <td><span class="badge ok">Hoạt động</span></td>
                            <td><x-ui.button :href="route('admin.portal.webhook-delivery', ['id' => 1])" variant="alt">Xem delivery</x-ui.button></td>
                        </tr>
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
                        <tr>
                            <td class="mono">del_abc123</td>
                            <td>license.revoked</td>
                            <td><span class="badge ok">200</span></td>
                            <td><x-ui.button :href="route('admin.portal.webhook-delivery', ['id' => 1])" variant="alt">Chi tiết</x-ui.button></td>
                        </tr>
                        <tr>
                            <td class="mono">del_def456</td>
                            <td>license.renewed</td>
                            <td><span class="badge current">500</span></td>
                            <td><x-ui.button variant="alt">Retry</x-ui.button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="actions" style="justify-content:space-between;margin-top:16px;">
        <span class="muted">Trang 1 / 5</span>
        <div class="actions">
            <x-ui.button variant="alt">Trước</x-ui.button>
            <x-ui.button variant="alt">Sau</x-ui.button>
        </div>
    </div>
@endsection
