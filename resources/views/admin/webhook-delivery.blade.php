@extends('layouts.admin', [
    'title' => 'Chi tiết webhook delivery | KeyLicense',
    'description' => 'Xem payload, response và retry history của webhook delivery',
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
    <x-ui.header title="Chi tiết webhook delivery" subtitle="Payload, headers, response và retry history">
        <x-ui.button :href="route('admin.portal.webhooks')" variant="alt">← Trở về Webhooks</x-ui.button>
        <x-ui.button variant="alt">Retry ngay</x-ui.button>
    </x-ui.header>

    <div class="grid cols-2">
        <section class="card stack">
            <x-ui.section-header title="Delivery info" subtitle="Thông tin lần gửi webhook." />
            <div class="codebox">
                delivery_id: {{ $deliveryId }}<br>
                event_type: license.revoked<br>
                status_code: 200<br>
                attempt_count: 1<br>
                delivered_at: 2026-04-13 10:00
            </div>
        </section>

        <aside class="card stack">
            <x-ui.section-header title="Response" subtitle="Nội dung phản hồi từ endpoint nhận." />
            <div class="codebox">
                response_body: OK<br>
                retryable: no<br>
                config_id: wh_001
            </div>
        </aside>
    </div>

    <section class="card stack" style="margin-top:16px;">
        <x-ui.section-header title="Payload" subtitle="Dữ liệu webhook đã gửi." />
        <div class="codebox">
            {"license_key":"PROD1-****-****-IJKL4","status":"revoked","reason":"fraud_detected"}
        </div>
    </section>
@endsection
