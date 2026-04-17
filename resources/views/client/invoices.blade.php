@extends('layouts.client', [
    'title' => 'Hóa đơn của tôi | KeyLicense',
    'description' => 'Lịch sử hóa đơn và billing history của khách hàng',
])

@section('content')
    <div class="topbar">
        <div class="brand">
            <span class="mark"></span>
            <div>
                <h1 class="title">Hóa đơn của tôi</h1>
                <div class="muted">Lịch sử thanh toán và tải hóa đơn</div>
            </div>
        </div>
        <x-ui.button :href="route('client.portal')" variant="alt">← Cổng khách hàng</x-ui.button>
    </div>

    <div class="grid cols-2">
        <section class="card stack">
            <x-ui.section-header title="Danh sách hóa đơn" subtitle="Xem hóa đơn đã phát hành và trạng thái thanh toán." />
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Số hóa đơn</th>
                            <th>Trạng thái</th>
                            <th>Tổng</th>
                            <th>Ngày</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="mono">INV-2026-00001</td>
                            <td><span class="badge ok">Đã thanh toán</span></td>
                            <td>199.00</td>
                            <td>2026-04-13</td>
                            <td><x-ui.button variant="alt">Tải xuống</x-ui.button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="card stack">
            <x-ui.section-header title="Billing address" subtitle="Địa chỉ xuất hóa đơn và thông tin thuế." />
            <div class="codebox">
                Nguyễn Văn A<br>
                ACME Ltd<br>
                Hà Nội, VN
            </div>
            <x-ui.button variant="alt">Cập nhật địa chỉ</x-ui.button>
        </aside>
    </div>
@endsection
