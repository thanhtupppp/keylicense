@extends('layouts.client', [
    'title' => 'Cổng khách hàng | KeyLicense',
    'description' => 'Tổng quan luồng khách hàng của KeyLicense',
])

@section('content')
    <div class="topbar">
        <div class="brand">
            <span class="mark"></span>
            <div>
                <h1 class="title">Cổng khách hàng</h1>
                <div class="muted">Vòng đời license và luồng kích hoạt</div>
            </div>
        </div>
        <x-ui.button :href="route('client.activate')">Bắt đầu kích hoạt</x-ui.button>
    </div>

    <x-ui.card>
        <div class="grid cols-2" style="align-items:center;">
            <div>
                <x-ui.pill style="margin-bottom:14px;display:inline-flex;">API khách hàng đã sẵn sàng</x-ui.pill>
                <h2 style="margin:0 0 12px;font-size:clamp(2rem,4vw,3.6rem);line-height:1;">Một nơi cho toàn bộ luồng license của khách hàng.</h2>
                <p class="muted" style="line-height:1.8;max-width:60ch;">
                    Từ kích hoạt, xác thực, challenge ngoại tuyến đến thu hồi, mọi bước đều được thiết kế thành
                    các màn hình riêng để dễ dùng, dễ demo và dễ mở rộng.
                </p>
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:18px;">
                    <x-ui.button :href="route('client.activate')">Kích hoạt license</x-ui.button>
                    <x-ui.button :href="route('client.validate')" variant="alt">Xác thực license</x-ui.button>
                    <x-ui.button :href="route('client.offline')" variant="alt">Luồng ngoại tuyến</x-ui.button>
                </div>
            </div>
            <x-ui.card padding="18px" style="background:rgba(2,6,23,.42);">
                <div class="grid cols-3">
                    <x-ui.stat value="5" label="Màn hình khách hàng" />
                    <x-ui.stat value="1 API" label="Luồng thống nhất" />
                    <x-ui.stat value="Ngoại tuyến" label="Hỗ trợ challenge" />
                </div>
                <div style="margin-top:16px;" class="codebox">
                    POST /api/v1/client/licenses/activate<br>
                    POST /api/v1/client/licenses/validate<br>
                    POST /api/v1/client/licenses/deactivate<br>
                    POST /api/v1/client/licenses/offline/request<br>
                    POST /api/v1/client/licenses/offline/confirm
                </div>
            </x-ui.card>
        </div>
    </x-ui.card>

    <section style="margin-top:16px;" class="grid cols-3">
        <x-ui.card>
            <h3 style="margin-top:0;">Kích hoạt</h3>
            <p class="muted">Nhập license key và thông tin thiết bị để nhận mã kích hoạt.</p>
        </x-ui.card>
        <x-ui.card>
            <h3 style="margin-top:0;">Xác thực</h3>
            <p class="muted">Kiểm tra trạng thái license theo chu kỳ hoặc khi ứng dụng khởi động.</p>
        </x-ui.card>
        <x-ui.card>
            <h3 style="margin-top:0;">Ngoại tuyến</h3>
            <p class="muted">Tạo challenge, xác nhận sau khi có kết nối trở lại.</p>
        </x-ui.card>
    </section>
@endsection
