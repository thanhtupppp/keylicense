@extends('layouts.admin', [
    'title' => 'Trial licenses | KeyLicense',
    'description' => 'Quản lý trial license flow và chống abuse',
    'sidebarItems' => [
        ['href' => route('admin.portal.dashboard'), 'label' => 'Tổng quan'],
        ['href' => route('admin.portal.sessions'), 'label' => 'Phiên đăng nhập'],
        ['href' => route('admin.portal.api-keys'), 'label' => 'API Keys'],
        ['href' => route('admin.portal.coupons'), 'label' => 'Coupons'],
        ['href' => route('admin.portal.invoices'), 'label' => 'Hóa đơn'],
        ['href' => route('admin.portal.webhooks'), 'label' => 'Webhooks'],
        ['href' => route('admin.portal.metrics'), 'label' => 'Số liệu'],
        ['href' => route('admin.portal.trials'), 'label' => 'Trial', 'active' => true],
        ['href' => route('admin.portal.platform-config'), 'label' => 'Cấu hình'],
    ],
])

@section('content')
    <x-ui.header title="Trial licenses" subtitle="Theo dõi trial usage, conversion và abuse prevention">
        <x-ui.button :href="route('admin.portal.dashboard')" variant="alt">← Quay lại tổng quan</x-ui.button>
    </x-ui.header>

    <div class="grid cols-3">
        <x-ui.stat value="45" label="Trial đang chạy" />
        <x-ui.stat value="18%" label="Conversion" />
        <x-ui.stat value="3" label="Bị chặn abuse" />
    </div>

    <section class="card" style="margin-top:16px;">
        <x-ui.section-header title="Quy tắc trial" subtitle="Giới hạn theo email, domain và IP để chống lạm dụng." />
        <ul style="margin:0;padding-left:18px;line-height:1.8;color:#dbeafe;">
            <li>Một email chỉ dùng trial một lần cho mỗi product.</li>
            <li>Cùng domain đã dùng thì chặn tạo trial mới.</li>
            <li>Mail rác và IP vượt ngưỡng sẽ bị block.</li>
        </ul>
    </section>
@endsection
