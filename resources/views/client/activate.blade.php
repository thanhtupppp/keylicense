@extends('layouts.client', [
    'title' => 'Kích hoạt license | KeyLicense',
    'description' => 'Kích hoạt license bằng API thật',
])

@section('content')
    <div class="topbar">
        <div class="brand">
            <span class="mark"></span>
            <div>
                <h1 class="title">Kích hoạt license</h1>
                <div class="muted">Nhập key, dấu vân tay thiết bị và thông tin ứng dụng</div>
            </div>
        </div>
        <x-ui.button :href="route('client.portal')" variant="alt">← Cổng khách hàng</x-ui.button>
    </div>

    <div class="grid cols-2">
        <section class="card">
            <x-ui.section-header title="Biểu mẫu kích hoạt" subtitle="Điền thông tin license để tạo phiên kích hoạt mới." />
            <form id="activate-form" class="grid" style="gap:14px;">
                @csrf
                <x-ui.input label="License key" name="license_key" placeholder="LIC-XXXX-XXXX-XXXX" required />
                <x-ui.input label="Mã sản phẩm" name="product_slug" placeholder="san-pham-cua-toi" required />
                <x-ui.input label="Dấu vân tay thiết bị" name="device_fingerprint" placeholder="device-hash" required />
                <x-ui.input label="Mã cài đặt" name="install_id" placeholder="install-uuid" />
                <x-ui.button type="submit">Gửi yêu cầu kích hoạt</x-ui.button>
            </form>
        </section>
        <x-ui.response-panel title="Phản hồi" toast-id="activate-status" result-id="activate-result" copy-id="activate-copy-btn">
            <p class="muted">Trang này gửi trực tiếp tới API thật và hiển thị phản hồi để demo hoặc kiểm thử nhanh.</p>
        </x-ui.response-panel>
    </div>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('activate-form');
    const panel = form?.closest('.grid')?.querySelector?.('[data-role="result"]')?.closest('.card');
    const result = document.getElementById('activate-result');
    const status = document.getElementById('activate-status');
    const copyBtn = document.getElementById('activate-copy-btn');
    const submitBtn = form.querySelector('button[type="submit"]');
    const endpoint = '/api/v1/client/licenses/activate';

    const getPayload = () => ({
        license_key: form.querySelector('[name="license_key"]').value,
        product_code: form.querySelector('[name="product_slug"]').value,
        device: {
            fingerprint: form.querySelector('[name="device_fingerprint"]').value,
        },
        install_id: form.querySelector('[name="install_id"]').value,
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const payload = getPayload();
        result.textContent = 'Đang gửi yêu cầu...';
        const restore = window.ClientUI.setLoading(submitBtn, 'Đang kích hoạt...');
        window.ClientUI.setPanelState?.(panel, 'loading', 'Đang gọi API kích hoạt...');

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await response.json().catch(() => ({}));
            result.textContent = JSON.stringify(data, null, 2);
            if (!response.ok) {
                window.ClientUI.setPanelState?.(panel, 'error', data?.message || 'Kích hoạt thất bại');
                throw new Error(data?.message || 'Kích hoạt thất bại');
            }
            window.ClientUI.setToast(status, 'success', data?.message || 'Kích hoạt thành công.');
            window.ClientUI.setPanelState?.(panel, 'success', data?.message || 'Kích hoạt thành công.');
        } catch (error) {
            window.ClientUI.setToast(status, 'danger', error.message || 'Không thể kết nối API.');
            window.ClientUI.setPanelState?.(panel, 'error', error.message || 'Không thể kết nối API.');
        } finally {
            restore();
        }
    });

    copyBtn?.addEventListener('click', async () => {
        const text = JSON.stringify(getPayload(), null, 2);
        await navigator.clipboard.writeText(text);
        window.ClientUI.setToast(status, 'success', 'Đã sao chép payload vào clipboard.');
    });
})();
</script>
@endpush
