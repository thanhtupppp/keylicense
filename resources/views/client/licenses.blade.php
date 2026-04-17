@extends('layouts.client', [
    'title' => 'License của tôi | KeyLicense',
    'description' => 'Danh sách license, trạng thái và thông tin sử dụng',
])

@section('content')
    <div class="topbar">
        <div class="brand">
            <span class="mark"></span>
            <div>
                <h1 class="title">License của tôi</h1>
                <div class="muted">Danh sách license và trạng thái đang sử dụng</div>
            </div>
        </div>
        <x-ui.button :href="route('client.portal')" variant="alt">← Cổng khách hàng</x-ui.button>
    </div>

    <div class="grid cols-2">
        <section class="card stack">
            <x-ui.section-header title="Danh sách license" subtitle="Xem trạng thái, ngày hết hạn và số activation đang hoạt động." />
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>License</th>
                            <th>Sản phẩm</th>
                            <th>Gói</th>
                            <th>Trạng thái</th>
                            <th>Hết hạn</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="mono">PROD1-****-****-IJKL4</td>
                            <td>SEO Plugin Pro</td>
                            <td>Annual</td>
                            <td><span class="badge ok">Hoạt động</span></td>
                            <td>2027-04-13</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="stack">
            <section class="card">
                <x-ui.section-header title="Tổng quan" subtitle="Thông tin nhanh về license hiện tại." />
                <div class="stats">
                    <x-ui.stat value="2" label="Activation đang hoạt động" />
                    <x-ui.stat value="3" label="Tối đa sites" />
                    <x-ui.stat value="7 ngày" label="Grace period" />
                </div>
            </section>

            <section class="card">
                <x-ui.section-header title="Hành động" subtitle="Mở chi tiết, tải hóa đơn hoặc cập nhật thông tin." />
                <div class="actions">
                    <x-ui.button :href="route('client.license-detail', ['id' => 1])">Xem chi tiết</x-ui.button>
                    <x-ui.button :href="route('client.invoices')" variant="alt">Hóa đơn</x-ui.button>
                </div>
            </section>
        </aside>
    </div>
@endsection
