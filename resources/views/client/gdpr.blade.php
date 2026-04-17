@extends('layouts.client', [
    'title' => 'GDPR & Dữ liệu của tôi | KeyLicense',
    'description' => 'Yêu cầu xuất dữ liệu, xoá dữ liệu và quản lý bảo mật',
])

@section('content')
    <div class="topbar">
        <div class="brand">
            <span class="mark"></span>
            <div>
                <h1 class="title">GDPR & Dữ liệu của tôi</h1>
                <div class="muted">Xuất dữ liệu, xóa dữ liệu và quyền riêng tư</div>
            </div>
        </div>
        <x-ui.button :href="route('client.portal')" variant="alt">← Cổng khách hàng</x-ui.button>
    </div>

    <div class="grid cols-2">
        <section class="card stack">
            <x-ui.section-header title="Yêu cầu dữ liệu" subtitle="Tải xuống dữ liệu hoặc yêu cầu xóa/anonymize." />
            <form id="data-request-form" class="grid" style="gap:12px;">
                <x-ui.input label="Loại yêu cầu" name="request_type" value="portability" />
                <x-ui.textarea label="Ghi chú" name="notes" placeholder="Mô tả yêu cầu hoặc lý do">{{ old('notes') }}</x-ui.textarea>
                <div class="actions">
                    <x-ui.button type="submit" variant="alt">Yêu cầu xuất dữ liệu</x-ui.button>
                    <x-ui.button type="button" variant="danger" id="data-erasure-btn">Yêu cầu xóa dữ liệu</x-ui.button>
                </div>
            </form>
        </section>

        <aside class="card">
            <x-ui.section-header title="Chính sách lưu trữ" subtitle="Dữ liệu được giữ theo chính sách compliance." />
            <div class="codebox">
                audit_logs: 7 năm<br>
                heartbeat_logs: 90 ngày<br>
                notification_logs: 180 ngày<br>
                customer_sessions: 30 ngày sau khi hết hạn
            </div>
        </aside>
    </div>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('data-request-form');
    const eraseBtn = document.getElementById('data-erasure-btn');
    if (!window.ClientUI) return;

    const submitRequest = async (requestType) => {
        const submitBtn = form.querySelector('button[type="submit"]');
        const restore = window.ClientUI.setLoading(submitBtn, 'Đang gửi...');
        try {
            const payload = Object.fromEntries(new FormData(form).entries());
            payload.request_type = requestType;
            const response = await fetch('/api/v1/customer/data-requests', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data?.error?.message || data?.message || 'Không thể gửi yêu cầu');
            window.ClientUI.setToast(document.querySelector('[data-role="toast"]') || document.body, 'success', data?.message || 'Đã gửi yêu cầu.');
        } catch (error) {
            window.ClientUI.setToast(document.querySelector('[data-role="toast"]') || document.body, 'danger', error.message);
        } finally {
            restore();
        }
    };

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        await submitRequest('portability');
    });

    eraseBtn?.addEventListener('click', async () => {
        await submitRequest('erasure');
    });
})();
</script>
@endpush
