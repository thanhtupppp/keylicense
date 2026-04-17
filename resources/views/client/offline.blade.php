@extends('layouts.client', [
    'title' => 'Ngoại tuyến | KeyLicense',
    'description' => 'Yêu cầu và xác nhận challenge ngoại tuyến',
])

@section('content')
    <div class="topbar">
        <div class="brand">
            <span class="mark"></span>
            <div>
                <h1 class="title">Ngoại tuyến</h1>
                <div class="muted">Yêu cầu và xác nhận challenge khi không có internet</div>
            </div>
        </div>
        <x-ui.button :href="route('client.portal')" variant="alt">← Cổng khách hàng</x-ui.button>
    </div>

    <div class="grid cols-2">
        <section class="card">
            <div class="tabs">
                <button type="button" class="tab active" data-mode="request">Yêu cầu</button>
                <button type="button" class="tab" data-mode="confirm">Xác nhận</button>
            </div>
            <form id="offline-form" class="grid" style="gap:14px;">
                <input type="hidden" name="challenge_id" value="">
                <x-ui.input label="License key" name="license_key" placeholder="PROD1-ABCD2-EFGH3-IJKL4" required />
                <x-ui.input label="Mã sản phẩm" name="product_code" placeholder="PLUGIN_SEO" required />
                <x-ui.input label="Domain" name="domain" placeholder="example.com" required />
                <x-ui.input label="Nonce" name="nonce" placeholder="random_client_nonce_abc" />
                <x-ui.input label="Hostname" name="device[hostname]" placeholder="example.com" />
                <x-ui.input label="OS" name="device[os]" placeholder="linux" />
                <x-ui.textarea label="Dữ liệu challenge / response" name="challenge_payload" id="challenge_payload" placeholder="Dán dữ liệu challenge hoặc response token vào đây"></x-ui.textarea>
                <x-ui.button class="button alt" type="submit" id="offline-submit">Tạo challenge ngoại tuyến</x-ui.button>
            </form>
        </section>
        <aside class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
                <h2 style="margin-top:0;">Kết quả phản hồi</h2>
                <x-ui.button type="button" variant="alt" id="offline-copy-btn">Sao chép</x-ui.button>
            </div>
            <x-ui.toast id="offline-status" type="info"></x-ui.toast>
            <div id="offline-result" class="codebox" data-role="result">Chưa có phản hồi.</div>
            <div class="codebox" style="margin-top:14px;">POST /api/v1/client/licenses/offline/request<br>POST /api/v1/client/licenses/offline/confirm</div>
        </aside>
    </div>
@endsection

@push('scripts')
<script>
(() => {
    const tabs = document.querySelectorAll('[data-mode]');
    const form = document.getElementById('offline-form');
    const submit = document.getElementById('offline-submit');
    const result = document.getElementById('offline-result');
    const status = document.getElementById('offline-status');
    const copyBtn = document.getElementById('offline-copy-btn');
    const challengeField = document.getElementById('challenge_payload').closest('.field');
    let mode = 'request';

    const endpoints = {
        request: '/api/v1/client/licenses/offline/request',
        confirm: '/api/v1/client/licenses/offline/confirm',
    };

    const syncMode = () => {
        tabs.forEach(btn => btn.classList.toggle('active', btn.dataset.mode === mode));
        submit.textContent = mode === 'request' ? 'Tạo challenge ngoại tuyến' : 'Xác nhận challenge';
        challengeField.style.display = 'grid';
    };

    tabs.forEach(btn => btn.addEventListener('click', () => { mode = btn.dataset.mode; syncMode(); }));
    syncMode();

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const payload = Object.fromEntries(new FormData(form).entries());
        const restore = window.ClientUI.setLoading(submit, 'Đang xử lý...');
        window.ClientUI.setPanelState?.(document.querySelector('.card:last-child'), 'loading', 'Đang xử lý...');
        result.textContent = 'Đang xử lý...';

        const body = mode === 'request'
            ? {
                license_key: payload.license_key,
                product_code: payload.product_code,
                domain: payload.domain,
                nonce: payload.nonce || undefined,
                device: {
                    hostname: payload['device[hostname]'] || undefined,
                    os: payload['device[os]'] || undefined,
                },
            }
            : {
                challenge_id: payload.challenge_id || undefined,
                response_token: payload.challenge_payload || undefined,
            };

        try {
            const response = await fetch(endpoints[mode], {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(body),
            });
            const data = await response.json().catch(() => ({}));
            result.textContent = JSON.stringify(data, null, 2);
            if (!response.ok) throw new Error(data?.error?.message || data?.message || 'Luồng ngoại tuyến thất bại');
            window.ClientUI.setToast(status, 'success', data?.message || 'Thành công.');
        } catch (error) {
            window.ClientUI.setToast(status, 'danger', error.message || 'Không thể kết nối API.');
        } finally {
            restore();
        }
    });

    copyBtn?.addEventListener('click', async () => {
        const payload = Object.fromEntries(new FormData(form).entries());
        await navigator.clipboard.writeText(JSON.stringify(payload, null, 2));
        window.ClientUI.setToast(status, 'success', 'Đã sao chép payload.');
    });
})();
</script>
@endpush
