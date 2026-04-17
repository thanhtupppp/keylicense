@extends('layouts.admin', [
    'title' => 'Thanh toán | KeyLicense',
    'description' => 'Trang quản lý thanh toán của KeyLicense',
    'sidebarItems' => [
        ['href' => route('admin.portal.dashboard'), 'label' => 'Tổng quan'],
        ['href' => route('admin.portal.sessions'), 'label' => 'Phiên đăng nhập'],
        ['href' => route('admin.portal.billing'), 'label' => 'Thanh toán', 'active' => true],
        ['href' => route('admin.portal.settings'), 'label' => 'Cài đặt'],
    ],
])

@section('content')
    <x-ui.header title="Thanh toán" subtitle="Quản lý hóa đơn, coupon, hoàn tiền và chargeback">
        <x-ui.button :href="route('admin.portal.dashboard')" variant="alt">← Quay lại tổng quan</x-ui.button>
    </x-ui.header>

    <div class="grid cols-2">
        <x-ui.card>
            <x-ui.section-header title="Tổng quan thanh toán" subtitle="Số liệu mô phỏng để khởi tạo giao diện quản trị." />
            <div class="stats">
                <x-ui.stat value="128" label="Hóa đơn" />
                <x-ui.stat value="24" label="Coupon" />
                <x-ui.stat value="3" label="Hoàn tiền" />
            </div>
        </x-ui.card>

        <x-ui.card>
            <x-ui.section-header title="Tác vụ nhanh" subtitle="Các hành động thường dùng trong quản trị thanh toán." />
            <div class="actions">
                <x-ui.button variant="alt">Tạo coupon</x-ui.button>
                <x-ui.button variant="alt">Ghi nhận hoàn tiền</x-ui.button>
                <x-ui.button variant="danger">Xử lý chargeback</x-ui.button>
            </div>
            <div class="codebox">
                /admin/billing/coupons<br>
                /admin/billing/refunds<br>
                /admin/billing/chargebacks
            </div>
        </x-ui.card>
    </div>
@endsection
