@extends('layouts.admin', [
    'title' => 'Cài đặt | KeyLicense',
    'description' => 'Trang cài đặt hệ thống của KeyLicense',
    'sidebarItems' => [
        ['href' => route('admin.portal.dashboard'), 'label' => 'Tổng quan'],
        ['href' => route('admin.portal.sessions'), 'label' => 'Phiên đăng nhập'],
        ['href' => route('admin.portal.billing'), 'label' => 'Thanh toán'],
        ['href' => route('admin.portal.settings'), 'label' => 'Cài đặt', 'active' => true],
    ],
])

@section('content')
    <x-ui.header title="Cài đặt hệ thống" subtitle="Quản lý cấu hình nền tảng và hành vi chung">
        <x-ui.button :href="route('admin.portal.dashboard')" variant="alt">← Quay lại tổng quan</x-ui.button>
    </x-ui.header>

    <div class="grid cols-2">
        <x-ui.card>
            <x-ui.section-header title="Cấu hình chung" subtitle="Thiết lập các tham số vận hành của hệ thống." />
            <div class="grid" style="gap:14px;">
                <x-ui.input label="Tên nền tảng" name="platform_name" value="KeyLicense" />
                <x-ui.input label="Múi giờ" name="timezone" value="Asia/Ho_Chi_Minh" />
                <x-ui.input label="URL công khai" name="public_url" value="https://example.com" />
                <x-ui.button>Lưu cài đặt</x-ui.button>
            </div>
        </x-ui.card>

        <x-ui.card>
            <x-ui.section-header title="Cờ tính năng" subtitle="Bật/tắt nhanh các tính năng vận hành." />
            <div class="grid" style="gap:12px;">
                <label><input type="checkbox" checked> Cho phép kích hoạt license</label>
                <label><input type="checkbox" checked> Cho phép xác thực ngoại tuyến</label>
                <label><input type="checkbox"> Bật thông báo chargeback</label>
            </div>
        </x-ui.card>
    </div>
@endsection
