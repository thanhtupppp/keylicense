@extends('layouts.admin', [
    'title' => 'Số liệu & Dashboard | KeyLicense',
    'description' => 'Dashboard KPI, revenue và activation metrics',
    'sidebarItems' => [
        ['href' => route('admin.portal.dashboard'), 'label' => 'Tổng quan'],
        ['href' => route('admin.portal.sessions'), 'label' => 'Phiên đăng nhập'],
        ['href' => route('admin.portal.api-keys'), 'label' => 'API Keys'],
        ['href' => route('admin.portal.coupons'), 'label' => 'Coupons'],
        ['href' => route('admin.portal.invoices'), 'label' => 'Hóa đơn'],
        ['href' => route('admin.portal.webhooks'), 'label' => 'Webhooks'],
        ['href' => route('admin.portal.metrics'), 'label' => 'Số liệu', 'active' => true],
        ['href' => route('admin.portal.trials'), 'label' => 'Trial'],
        ['href' => route('admin.portal.platform-config'), 'label' => 'Cấu hình'],
    ],
])

@section('content')
    <x-ui.header title="Số liệu & Dashboard" subtitle="KPI tổng quan, doanh thu, license và activation">
        <x-ui.button :href="route('admin.portal.dashboard')" variant="alt">← Quay lại tổng quan</x-ui.button>
    </x-ui.header>

    <div class="grid cols-3">
        <x-ui.stat value="1,240" label="License đang hoạt động" />
        <x-ui.stat value="3,102" label="Activation đang hoạt động" />
        <x-ui.stat value="58.2M" label="ARR (cents)" />
    </div>

    <div class="grid cols-2" style="margin-top:16px;">
        <section class="card">
            <x-ui.section-header title="Xu hướng license" subtitle="Số lượng phát hành và thu hồi theo ngày." />
            <div class="codebox">
                2026-04-01  issued: 12, revoked: 1<br>
                2026-04-02  issued: 8, revoked: 0<br>
                2026-04-03  issued: 14, revoked: 2
            </div>
        </section>

        <section class="card">
            <x-ui.section-header title="Sức khỏe hệ thống" subtitle="Theo dõi trạng thái cache, queue và grace period." />
            <div class="stats">
                <x-ui.stat value="12" label="Grace period" />
                <x-ui.stat value="2" label="Cảnh báo abuse" />
                <x-ui.stat value="99.9%" label="Uptime" />
            </div>
        </section>
    </div>
@endsection
