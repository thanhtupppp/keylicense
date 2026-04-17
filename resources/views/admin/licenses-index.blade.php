@extends('layouts.admin', [
    'title' => 'Danh sách license | KeyLicense',
    'description' => 'Tìm kiếm và lọc license theo trạng thái, product và plan',
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
    <x-ui.header title="Danh sách license" subtitle="Bộ lọc, tìm kiếm và phân trang">
        <x-ui.button :href="route('admin.portal.dashboard')" variant="alt">← Quay lại tổng quan</x-ui.button>
        <x-ui.button :href="route('admin.portal.licenses.detail', ['id' => 1])">Xem chi tiết</x-ui.button>
    </x-ui.header>

    <form class="card stack" method="GET" action="{{ route('admin.portal.licenses') }}">
        <x-ui.section-header title="Bộ lọc" subtitle="Tìm license theo trạng thái, product và plan." />
        <div class="grid cols-4">
            <x-ui.input label="Tìm kiếm" name="q" placeholder="License key, customer, product" />
            <x-ui.input label="Trạng thái" name="status" placeholder="active" />
            <x-ui.input label="Product" name="product" placeholder="PLUGIN_SEO" />
            <x-ui.input label="Plan" name="plan" placeholder="SEO_PRO_ANNUAL" />
        </div>
        <div class="actions">
            <x-ui.button type="submit">Lọc</x-ui.button>
            <x-ui.button variant="alt" :href="route('admin.portal.licenses')">Xóa lọc</x-ui.button>
        </div>
    </form>

    <div class="grid cols-3" style="margin-top:16px;">
        <x-ui.stat value="1240" label="Tổng license" />
        <x-ui.stat value="87" label="Sắp hết hạn" />
        <x-ui.stat value="23" label="Bị thu hồi" />
    </div>

    <section class="card stack" style="margin-top:16px;">
        <x-ui.section-header title="Kết quả" subtitle="Danh sách license được phân trang." />
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>License</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Trạng thái</th>
                        <th>Hết hạn</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="mono">PROD1-****-****-IJKL4</td>
                        <td>customer@example.com</td>
                        <td>PLUGIN_SEO</td>
                        <td><span class="badge ok">Hoạt động</span></td>
                        <td>2027-04-13</td>
                        <td><x-ui.button :href="route('admin.portal.licenses.detail', ['id' => 1])" variant="alt">Chi tiết</x-ui.button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="actions" style="justify-content:space-between;">
            <span class="muted">Trang 1 / 12</span>
            <div class="actions">
                <x-ui.button variant="alt">Trước</x-ui.button>
                <x-ui.button variant="alt">Sau</x-ui.button>
            </div>
        </div>
    </section>
@endsection
