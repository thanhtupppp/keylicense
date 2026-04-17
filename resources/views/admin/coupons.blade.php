@extends('layouts.admin', [
    'title' => 'Quản lý Coupons | KeyLicense',
    'description' => 'Quản lý mã giảm giá và trial extensions',
    'sidebarItems' => [
        ['href' => route('admin.portal.dashboard'), 'label' => 'Tổng quan'],
        ['href' => route('admin.portal.sessions'), 'label' => 'Phiên đăng nhập'],
        ['href' => route('admin.portal.api-keys'), 'label' => 'API Keys'],
        ['href' => route('admin.portal.coupons'), 'label' => 'Coupons', 'active' => true],
        ['href' => route('admin.portal.invoices'), 'label' => 'Hóa đơn'],
        ['href' => route('admin.portal.webhooks'), 'label' => 'Webhooks'],
        ['href' => route('admin.portal.metrics'), 'label' => 'Số liệu'],
        ['href' => route('admin.portal.trials'), 'label' => 'Trial'],
        ['href' => route('admin.portal.platform-config'), 'label' => 'Cấu hình'],
    ],
])

@section('content')
    <x-ui.header title="Quản lý Coupons" subtitle="Tạo và quản lý mã giảm giá theo plan/product">
        <x-ui.button :href="route('admin.portal.dashboard')" variant="alt">← Quay lại tổng quan</x-ui.button>
        <x-ui.button>Coupon mới</x-ui.button>
    </x-ui.header>

    <div class="grid cols-3">
        <x-ui.stat value="24" label="Đang hoạt động" />
        <x-ui.stat value="8" label="Đã dùng" />
        <x-ui.stat value="2" label="Sắp hết hạn" />
    </div>

    <div class="grid cols-2" style="margin-top:16px;">
        <section class="card stack">
            <x-ui.section-header title="Danh sách coupons" subtitle="Mã giảm giá dùng cho thanh toán, trial extension và free plan." />
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Mã</th>
                            <th>Loại</th>
                            <th>Giá trị</th>
                            <th>Phạm vi</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="mono">LAUNCH50</td>
                            <td>percent</td>
                            <td>50%</td>
                            <td>any</td>
                            <td><span class="badge ok">Hoạt động</span></td>
                            <td><x-ui.button variant="danger">Tắt</x-ui.button></td>
                        </tr>
                        <tr>
                            <td class="mono">TRIAL14</td>
                            <td>trial_extension</td>
                            <td>14 ngày</td>
                            <td>PLUGIN_SEO</td>
                            <td><span class="badge current">Giới hạn</span></td>
                            <td><x-ui.button variant="alt">Xem lượt dùng</x-ui.button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="stack">
            <section class="card">
                <x-ui.section-header title="Tạo coupon" subtitle="Dùng cho chiến dịch, trial extension hoặc free plan." />
                <div class="grid" style="gap:12px;">
                    <x-ui.input label="Mã coupon" name="code" placeholder="LAUNCH50" />
                    <x-ui.input label="Loại giảm giá" name="discount_type" placeholder="percent" />
                    <x-ui.input label="Giá trị" name="discount_value" placeholder="50" />
                    <x-ui.button>Tạo coupon</x-ui.button>
                </div>
            </section>

            <section class="card">
                <x-ui.section-header title="Gợi ý logic" subtitle="Coupons phải gắn với plan/product hoặc áp dụng toàn cục." />
                <ul style="margin:0;padding-left:18px;line-height:1.8;color:#dbeafe;">
                    <li>Kiểm tra số lượt dùng trước khi áp dụng.</li>
                    <li>Hỗ trợ trial extension và fixed amount.</li>
                    <li>Lưu usage theo customer và order.</li>
                </ul>
            </section>
        </aside>
    </div>
@endsection
