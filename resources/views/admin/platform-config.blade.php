@extends('layouts.admin', [
    'title' => 'Cấu hình nền tảng | KeyLicense',
    'description' => 'Quản lý platform configuration và feature flags',
    'sidebarItems' => [
        ['href' => route('admin.portal.dashboard'), 'label' => 'Tổng quan'],
        ['href' => route('admin.portal.sessions'), 'label' => 'Phiên đăng nhập'],
        ['href' => route('admin.portal.api-keys'), 'label' => 'API Keys'],
        ['href' => route('admin.portal.coupons'), 'label' => 'Coupons'],
        ['href' => route('admin.portal.invoices'), 'label' => 'Hóa đơn'],
        ['href' => route('admin.portal.webhooks'), 'label' => 'Webhooks'],
        ['href' => route('admin.portal.metrics'), 'label' => 'Số liệu'],
        ['href' => route('admin.portal.trials'), 'label' => 'Trial'],
        ['href' => route('admin.portal.platform-config'), 'label' => 'Cấu hình', 'active' => true],
    ],
])

@section('content')
    <x-ui.header title="Cấu hình nền tảng" subtitle="Quản lý các khóa cấu hình và feature flags của platform">
        <x-ui.button :href="route('admin.portal.dashboard')" variant="alt">← Quay lại tổng quan</x-ui.button>
    </x-ui.header>

    <div class="grid cols-2">
        <section class="card stack">
            <x-ui.section-header title="Default config" subtitle="Giá trị mặc định của grace period, trial và session." />
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Key</th>
                            <th>Giá trị</th>
                            <th>Loại</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="mono">default_grace_period_days</td>
                            <td>7</td>
                            <td>integer</td>
                        </tr>
                        <tr>
                            <td class="mono">maintenance_mode</td>
                            <td>false</td>
                            <td>boolean</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <x-ui.section-header title="Feature flags" subtitle="Bật/tắt các module theo nhu cầu triển khai." />
            <div class="grid" style="gap:12px;">
                <label><input type="checkbox"> Bật metered licensing</label>
                <label><input type="checkbox"> Bật reseller portal</label>
                <label><input type="checkbox" checked> Bật affiliate program</label>
            </div>
            <div style="margin-top:16px;">
                <x-ui.button>Lưu cấu hình</x-ui.button>
            </div>
        </section>
    </div>
@endsection
