@extends('layouts.client', [
    'title' => 'Xác thực license | KeyLicense',
    'description' => 'Xác thực license bằng API thật',
])

@section('content')
    <div class="topbar">
        <div class="brand">
            <span class="mark"></span>
            <div>
                <h1 class="title">Xác thực license</h1>
                <div class="muted">Kiểm tra trạng thái và quyền sử dụng của license</div>
            </div>
        </div>
        <x-ui.button :href="route('client.portal')" variant="alt">← Cổng khách hàng</x-ui.button>
    </div>

    <div class="grid cols-2">
        <section class="card">
            <h2 style="margin-top:0;">Biểu mẫu xác thực</h2>
            <form id="validate-form" class="grid" style="gap:14px;">
                <x-ui.input label="License key" name="license_key" placeholder="LIC-XXXX-XXXX-XXXX" required />
                <x-ui.input label="Dấu vân tay thiết bị" name="device_fingerprint" placeholder="device-hash" required />
                <x-ui.input label="Môi trường" name="environment" placeholder="production" />
                <x-ui.button type="submit">Xác thực ngay</x-ui.button>
            </form>
        </section>

        <x-ui.response-panel title="Kết quả phản hồi" toast-id="validate-status" result-id="validate-result" copy-id="validate-copy-btn">
            <p class="muted">Màn hình này sẽ trả về trạng thái hợp lệ, quyền sử dụng và dữ liệu license sau khi gọi API.</p>
        </x-ui.response-panel>
    </div>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('validate-form');
    const panel = form?.closest('.grid')?.querySelector?.('[data-role="result"]')?.closest('.card');
    const result = document.getElementById('validate-result');
    const status = document.getElementById('validate-status');
    const copyBtn = document.getElementById('validate-copy-btn');
    const submitBtn = form.querySelector('button[type="submit"]');
    const endpoint = '/api/v1/client/licenses/validate';

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const payload = Object.fromEntries(new FormData(form).entries());
        result.textContent = 'Đang xác thực...';
        const restore = window.ClientUI.setLoading(submitBtn, 'Đang xác thực...');
        window.ClientUI.setPanelState?.(panel, 'loading', 'Đang gọi API xác thực...');

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await response.json().catch(() => ({}));
            result.textContent = JSON.stringify(data, null, 2);
            if (!response.ok) {
                window.ClientUI.setPanelState?.(panel, 'error', data?.message || 'Xác thực thất bại');
                throw new Error(data?.message || 'Xác thực thất bại');
            }
            window.ClientUI.setToast(status, 'success', data?.message || 'Xác thực thành công.');
            window.ClientUI.setPanelState?.(panel, 'success', data?.message || 'Xác thực thành công.');
        } catch (error) {
            window.ClientUI.setToast(status, 'danger', error.message || 'Không thể kết nối API.');
            window.ClientUI.setPanelState?.(panel, 'error', error.message || 'Không thể kết nối API.');
        } finally {
            restore();
        }
    });

    copyBtn?.addEventListener('click', async () => {
        await navigator.clipboard.writeText(JSON.stringify(Object.fromEntries(new FormData(form).entries()), null, 2));
        window.ClientUI.setToast(status, 'success', 'Đã sao chép payload.');
    });
})();
</script>
@endpush
