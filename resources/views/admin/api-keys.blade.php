@extends('layouts.admin', [
    'title' => 'Quản lý API Keys | KeyLicense',
    'description' => 'Quản lý khóa API cho sản phẩm nội bộ',
    'sidebarItems' => [
        ['href' => route('admin.portal.dashboard'), 'label' => 'Tổng quan'],
        ['href' => route('admin.portal.sessions'), 'label' => 'Phiên đăng nhập'],
        ['href' => route('admin.portal.api-keys'), 'label' => 'API Keys', 'active' => true],
        ['href' => route('admin.portal.coupons'), 'label' => 'Coupons'],
        ['href' => route('admin.portal.invoices'), 'label' => 'Hóa đơn'],
        ['href' => route('admin.portal.webhooks'), 'label' => 'Webhooks'],
        ['href' => route('admin.portal.metrics'), 'label' => 'Số liệu'],
        ['href' => route('admin.portal.trials'), 'label' => 'Trial'],
        ['href' => route('admin.portal.platform-config'), 'label' => 'Cấu hình'],
    ],
])

@section('content')
    <x-ui.header title="Quản lý API Keys" subtitle="Cấp, xoay vòng và thu hồi khóa API theo môi trường">
        <x-ui.button :href="route('admin.portal.dashboard')" variant="alt">← Quay lại tổng quan</x-ui.button>
        <x-ui.button>Thêm key mới</x-ui.button>
    </x-ui.header>

    <div class="grid cols-3">
        <x-ui.stat value="12" label="Đang hoạt động" />
        <x-ui.stat value="3" label="Sắp hết hạn" />
        <x-ui.stat value="1" label="Đã thu hồi" />
    </div>

    <div class="grid cols-2" style="margin-top:16px;">
        <section class="card stack">
            <x-ui.section-header title="Danh sách API Keys" subtitle="Khóa được dùng cho client SDK và tích hợp sản phẩm." />
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Tên</th>
                            <th>Môi trường</th>
                            <th>Tiền tố</th>
                            <th>Lần dùng cuối</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Production Key</td>
                            <td>production</td>
                            <td class="mono">lp_live_</td>
                            <td>2026-04-13 10:00</td>
                            <td><span class="badge ok">Hoạt động</span></td>
                            <td>
                                <div class="actions">
                                    <x-ui.button variant="alt">Xoay vòng</x-ui.button>
                                    <x-ui.button variant="danger">Thu hồi</x-ui.button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>Staging Key</td>
                            <td>staging</td>
                            <td class="mono">lp_test_</td>
                            <td>2026-04-11 08:20</td>
                            <td><span class="badge current">Sắp hết hạn</span></td>
                            <td>
                                <div class="actions">
                                    <x-ui.button variant="alt">Xem</x-ui.button>
                                    <x-ui.button variant="danger">Thu hồi</x-ui.button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="stack">
            <section class="card">
                <x-ui.section-header title="Tạo API Key" subtitle="Trả plaintext một lần duy nhất khi tạo mới." />
                <div class="grid" style="gap:12px;">
                    <x-ui.input label="Tên khóa" name="key_name" placeholder="Production Key" />
                    <x-ui.input label="Môi trường" name="environment" placeholder="production" />
                    <x-ui.input label="Phạm vi" name="scopes" placeholder="activate,validate,heartbeat" />
                    <x-ui.button>Tạo khóa</x-ui.button>
                </div>
            </section>

            <section class="card">
                <x-ui.section-header title="Quy ước an toàn" subtitle="Không hiển thị plaintext sau khi tạo, chỉ lưu hashed key." />
                <ul style="margin:0;padding-left:18px;line-height:1.8;color:#dbeafe;">
                    <li>Không log API key đầy đủ.</li>
                    <li>Chỉ gửi qua HTTPS.</li>
                    <li>Rotate ngay khi lộ khóa.</li>
                </ul>
            </section>
        </aside>
    </div>
@endsection
