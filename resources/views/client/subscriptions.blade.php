@extends('layouts.client', [
    'title' => 'Subscription của tôi | KeyLicense',
    'description' => 'Trạng thái subscription và thanh toán định kỳ',
])

@section('content')
    <div class="topbar">
        <div class="brand">
            <span class="mark"></span>
            <div>
                <h1 class="title">Subscription của tôi</h1>
                <div class="muted">Theo dõi chu kỳ thanh toán và trạng thái gia hạn</div>
            </div>
        </div>
        <x-ui.button :href="route('client.portal')" variant="alt">← Cổng khách hàng</x-ui.button>
    </div>

    <div class="grid cols-2">
        <section class="card stack">
            <x-ui.section-header title="Danh sách subscription" subtitle="Thông tin chu kỳ và trạng thái billing." />
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Trạng thái</th>
                            <th>Bắt đầu</th>
                            <th>Kết thúc chu kỳ</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>SEO Pro Annual</td>
                            <td><span class="badge ok">Active</span></td>
                            <td>2026-04-13</td>
                            <td>2027-04-13</td>
                            <td><x-ui.button variant="danger">Huỷ</x-ui.button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="card">
            <x-ui.section-header title="Dunning" subtitle="Theo dõi trạng thái past due và nhắc thanh toán." />
            <div class="stats">
                <x-ui.stat value="Không" label="Past due" />
                <x-ui.stat value="Tự động" label="Gia hạn" />
                <x-ui.stat value="Bật" label="Nhắc thanh toán" />
            </div>
        </aside>
    </div>
@endsection
