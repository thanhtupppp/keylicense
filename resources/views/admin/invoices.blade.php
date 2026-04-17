@extends('layouts.admin', [
    'title' => 'Hóa đơn & Billing | KeyLicense',
    'description' => 'Quản lý hóa đơn, billing history và invoice items',
    'sidebarItems' => [
        ['href' => route('admin.portal.dashboard'), 'label' => 'Tổng quan'],
        ['href' => route('admin.portal.sessions'), 'label' => 'Phiên đăng nhập'],
        ['href' => route('admin.portal.api-keys'), 'label' => 'API Keys'],
        ['href' => route('admin.portal.coupons'), 'label' => 'Coupons'],
        ['href' => route('admin.portal.invoices'), 'label' => 'Hóa đơn', 'active' => true],
        ['href' => route('admin.portal.webhooks'), 'label' => 'Webhooks'],
        ['href' => route('admin.portal.metrics'), 'label' => 'Số liệu'],
        ['href' => route('admin.portal.trials'), 'label' => 'Trial'],
        ['href' => route('admin.portal.platform-config'), 'label' => 'Cấu hình'],
    ],
])

@section('content')
    <x-ui.header title="Hóa đơn & Billing" subtitle="Theo dõi hóa đơn, trạng thái thanh toán và invoice items">
        <x-ui.button :href="route('admin.portal.dashboard')" variant="alt">← Quay lại tổng quan</x-ui.button>
        <x-ui.button :href="route('admin.portal.invoice-detail', ['id' => 1])" variant="alt">Xem chi tiết hóa đơn</x-ui.button>
    </x-ui.header>

    <section class="card stack">
        <x-ui.section-header title="Bộ lọc" subtitle="Lọc theo trạng thái, số hóa đơn và khách hàng." />
        <div class="grid cols-4">
            <x-ui.input label="Tìm kiếm" name="q" placeholder="INV-2026, customer@example.com" />
            <x-ui.input label="Trạng thái" name="status" placeholder="paid" />
            <x-ui.input label="Khách hàng" name="customer" placeholder="customer@example.com" />
            <x-ui.input label="Khoảng ngày" name="period" placeholder="2026-04-01 → 2026-04-30" />
        </div>
        <div class="actions">
            <x-ui.button>Lọc</x-ui.button>
            <x-ui.button variant="alt">Xóa lọc</x-ui.button>
        </div>
    </section>

    <div class="grid cols-3" style="margin-top:16px;">
        <x-ui.stat value="128" label="Tổng hóa đơn" />
        <x-ui.stat value="94" label="Đã thanh toán" />
        <x-ui.stat value="7" label="Đang chờ" />
    </div>

    <div class="grid cols-2" style="margin-top:16px;">
        <section class="card stack">
            <x-ui.section-header title="Danh sách hóa đơn" subtitle="Hóa đơn theo order, customer hoặc organization." />
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Số hóa đơn</th>
                            <th>Trạng thái</th>
                            <th>Tổng</th>
                            <th>Ngày phát hành</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="mono">INV-2026-00001</td>
                            <td><span class="badge ok">Đã thanh toán</span></td>
                            <td>199.00</td>
                            <td>2026-04-13</td>
                            <td>
                                <div class="actions">
                                    <x-ui.button :href="route('admin.portal.invoice-detail', ['id' => 1])" variant="alt">Chi tiết</x-ui.button>
                                    <x-ui.button variant="alt">Tải xuống</x-ui.button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="mono">INV-2026-00002</td>
                            <td><span class="badge current">Chờ thanh toán</span></td>
                            <td>49.00</td>
                            <td>2026-04-12</td>
                            <td><x-ui.button variant="danger">Huỷ</x-ui.button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="stack">
            <section class="card">
                <x-ui.section-header title="Billing address" subtitle="Dữ liệu địa chỉ xuất hóa đơn và thuế." />
                <div class="grid" style="gap:12px;">
                    <x-ui.input label="Tên khách hàng" name="full_name" placeholder="Nguyễn Văn A" />
                    <x-ui.input label="Công ty" name="company" placeholder="ACME Ltd" />
                    <x-ui.input label="Quốc gia" name="country" placeholder="VN" />
                </div>
            </section>
        </aside>
    </div>

    <div class="actions" style="justify-content:space-between;margin-top:16px;">
        <span class="muted">Trang 1 / 12</span>
        <div class="actions">
            <x-ui.button variant="alt">Trước</x-ui.button>
            <x-ui.button variant="alt">Sau</x-ui.button>
        </div>
    </div>
@endsection
