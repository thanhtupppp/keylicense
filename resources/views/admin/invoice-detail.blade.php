@extends('layouts.admin', [
    'title' => 'Chi tiết hóa đơn | KeyLicense',
    'description' => 'Xem hóa đơn, invoice items và billing address',
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
    <x-ui.header title="Chi tiết hóa đơn" subtitle="Thông tin hóa đơn, invoice items và trạng thái thanh toán">
        <x-ui.button :href="route('admin.portal.invoices')" variant="alt">← Trở về danh sách</x-ui.button>
        <form method="POST" action="{{ route('admin.portal.invoices.void', ['id' => data_get($invoice, 'id')]) }}">
            @csrf
            <x-ui.button type="submit" variant="danger">Huỷ hóa đơn</x-ui.button>
        </form>
    </x-ui.header>

    @if (session('status'))
        <x-ui.notice type="success">{{ session('status') }}</x-ui.notice>
    @endif

    <div class="grid cols-2">
        <section class="card stack">
            <x-ui.section-header title="Thông tin hóa đơn" subtitle="Dữ liệu chính của invoice." />
            <div class="codebox">
                invoice_number: {{ data_get($invoice, 'invoice_number') }}<br>
                status: {{ data_get($invoice, 'status') }}<br>
                subtotal_cents: {{ data_get($invoice, 'subtotal_cents') }}<br>
                tax_cents: {{ data_get($invoice, 'tax_cents') }}<br>
                total_cents: {{ data_get($invoice, 'total_cents') }}
            </div>
        </section>

        <aside class="card stack">
            <x-ui.section-header title="Billing address" subtitle="Địa chỉ xuất hóa đơn." />
            <div class="codebox">
                {{ data_get($invoice, 'billing_address.name', 'N/A') }}<br>
                {{ data_get($invoice, 'billing_address.line1', 'N/A') }}<br>
                {{ data_get($invoice, 'billing_address.city', 'N/A') }}<br>
                {{ data_get($invoice, 'billing_address.country', 'N/A') }}<br>
                tax_id: {{ data_get($invoice, 'billing_address.tax_id', 'N/A') }}
            </div>
        </aside>
    </div>

    <section class="card stack" style="margin-top:16px;">
        <x-ui.section-header title="Invoice items" subtitle="Các dòng sản phẩm trong hóa đơn." />
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Mô tả</th>
                        <th>Số lượng</th>
                        <th>Đơn giá</th>
                        <th>Tổng</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>SEO Pro Annual</td>
                        <td>1</td>
                        <td>199.00</td>
                        <td>199.00</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
@endsection
