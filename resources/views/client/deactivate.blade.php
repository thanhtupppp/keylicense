@extends('layouts.client', [
    'title' => 'Thu hồi license | KeyLicense',
    'description' => 'Thu hồi license bằng API thật',
])

@section('content')
    <div class="topbar">
        <div class="brand">
            <span class="mark"></span>
            <div>
                <h1 class="title">Thu hồi license</h1>
                <div class="muted">Thu hồi an toàn khi người dùng đổi máy hoặc ngừng sử dụng</div>
            </div>
        </div>
        <x-ui.button :href="route('client.portal')" variant="alt">← Cổng khách hàng</x-ui.button>
    </div>

    <div class="grid cols-2">
        <section class="card">
            <h2 style="margin-top:0;">Biểu mẫu thu hồi</h2>
            <form id="deactivate-form" class="grid" style="gap:14px;">
                <x-ui.input label="License key" name="license_key" placeholder="PROD1-ABCD2-EFGH3-IJKL4" required />
                <x-ui.input label="Activation ID" name="activation_id" placeholder="act_abc123" required />
                <x-ui.input label="Lý do" name="reason" placeholder="Người dùng đổi máy" />
                <x-ui.button type="submit" variant="danger">Thu hồi ngay</x-ui.button>
            </form>
        </section>

        <x-ui.response-panel title="Kết quả phản hồi" toast-id="deactivate-status" result-id="deactivate-result" copy-id="deactivate-copy-btn">
            <div class="stat" style="margin-top:14px;"><strong>An toàn</strong><span class="muted">Thu hồi kích hoạt một cách sạch sẽ</span></div>
            <div class="stat" style="margin-top:12px;"><strong>Nhật ký</strong><span class="muted">Ghi lại sự kiện vào hệ thống</span></div>
        </x-ui.response-panel>
    </div>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('deactivate-form');
    const panel = form?.closest('.grid')?.querySelector?.('[data-role="result"]')?.closest('.card');
    const result = document.getElementById('deactivate-result');
    const status = document.getElementById('deactivate-status');
    const copyBtn = document.getElementById('deactivate-copy-btn');
    const submitBtn = form.querySelector('button[type="submit"]');
    const endpoint = '/api/v1/client/licenses/deactivate';

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const payload = Object.fromEntries(new FormData(form).entries());
        result.textContent = 'Đang xử lý...';
        const restore = window.ClientUI.setLoading(submitBtn, 'Đang xử lý...');
        window.ClientUI.setPanelState?.(panel, 'loading', 'Đang gọi API thu hồi...');

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await response.json().catch(() => ({}));
            result.textContent = JSON.stringify(data, null, 2);
            if (!response.ok) {
                window.ClientUI.setPanelState?.(panel, 'error', data?.error?.message || data?.message || 'Thu hồi thất bại');
                throw new Error(data?.error?.message || data?.message || 'Thu hồi thất bại');
            }
            window.ClientUI.setToast(status, 'success', data?.message || 'Thu hồi thành công.');
            window.ClientUI.setPanelState?.(panel, 'success', data?.message || 'Thu hồi thành công.');
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
