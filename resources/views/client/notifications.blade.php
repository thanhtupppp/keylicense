@extends('layouts.client', [
    'title' => 'Thông báo của tôi | KeyLicense',
    'description' => 'Cấu hình nhận thông báo và trạng thái nội dung',
])

@section('content')
    <div class="topbar">
        <div class="brand">
            <span class="mark"></span>
            <div>
                <h1 class="title">Thông báo của tôi</h1>
                <div class="muted">Bật/tắt notification theo nhu cầu</div>
            </div>
        </div>
        <x-ui.button :href="route('client.portal')" variant="alt">← Cổng khách hàng</x-ui.button>
    </div>

    <section class="card stack">
        <x-ui.section-header title="Notification preferences" subtitle="Quản lý email notifications và security alerts." />
        <form id="notification-form" class="grid" style="gap:12px;">
            <label><input type="checkbox" name="license_expiring_30d" value="1" checked> Nhận thông báo hết hạn 30 ngày</label>
            <label><input type="checkbox" name="license_expiring_7d" value="1" checked> Nhận thông báo hết hạn 7 ngày</label>
            <label><input type="checkbox" name="security_alert" value="1" checked disabled> Cảnh báo bảo mật bắt buộc</label>
            <x-ui.button type="submit">Lưu cấu hình</x-ui.button>
        </form>
    </section>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('notification-form');
    if (!form || !window.ClientUI) return;
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submitBtn = form.querySelector('button[type="submit"]');
        const restore = window.ClientUI.setLoading(submitBtn, 'Đang lưu...');
        const payload = Object.fromEntries(new FormData(form).entries());
        try {
            const response = await fetch('/api/v1/customer/notification-preferences', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data?.error?.message || data?.message || 'Không thể lưu cấu hình');
            window.ClientUI.setToast(document.querySelector('[data-role="toast"]') || document.body, 'success', data?.message || 'Đã lưu cấu hình.');
        } catch (error) {
            window.ClientUI.setToast(document.querySelector('[data-role="toast"]') || document.body, 'danger', error.message);
        } finally {
            restore();
        }
    });
})();
</script>
@endpush
