@extends('layouts.client', [
    'title' => 'Hồ sơ của tôi | KeyLicense',
    'description' => 'Quản lý thông tin tài khoản khách hàng',
])

@section('content')
    <div class="topbar">
        <div class="brand">
            <span class="mark"></span>
            <div>
                <h1 class="title">Hồ sơ của tôi</h1>
                <div class="muted">Cập nhật thông tin cá nhân và ngôn ngữ ưu tiên</div>
            </div>
        </div>
        <x-ui.button :href="route('client.portal')" variant="alt">← Cổng khách hàng</x-ui.button>
    </div>

    <div class="grid cols-2">
        <section class="card stack">
            <x-ui.section-header title="Thông tin hồ sơ" subtitle="Dữ liệu tài khoản và liên hệ." />
            <form id="profile-form" class="grid" style="gap:12px;">
                <x-ui.input label="Họ tên" name="full_name" value="Nguyễn Văn A" />
                <x-ui.input label="Email" name="email" value="customer@example.com" />
                <x-ui.input label="Điện thoại" name="phone" value="+84 912 345 678" />
                <x-ui.input label="Ngôn ngữ ưu tiên" name="preferred_language" value="vi" />
                <x-ui.button type="submit">Lưu thay đổi</x-ui.button>
            </form>
        </section>

        <aside class="card">
            <x-ui.section-header title="Tổng quan tài khoản" subtitle="Trạng thái onboarding và bảo mật." />
            <div class="stats">
                <x-ui.stat value="Đã xác thực" label="Email" />
                <x-ui.stat value="Hoàn thành" label="Onboarding" />
                <x-ui.stat value="Tắt" label="MFA" />
            </div>
            <div class="codebox" style="margin-top:16px;">PATCH /api/v1/customer/me</div>
        </aside>
    </div>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('profile-form');
    if (!form || !window.ClientUI) return;
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const payload = Object.fromEntries(new FormData(form).entries());
        const submitBtn = form.querySelector('button[type="submit"]');
        const restore = window.ClientUI.setLoading(submitBtn, 'Đang lưu...');
        try {
            const response = await fetch('/api/v1/customer/me', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data?.error?.message || data?.message || 'Không thể lưu hồ sơ');
            window.ClientUI.setToast(document.querySelector('[data-role="toast"]') || document.body, 'success', data?.message || 'Đã lưu hồ sơ.');
        } catch (error) {
            window.ClientUI.setToast(document.querySelector('[data-role="toast"]') || document.body, 'danger', error.message);
        } finally {
            restore();
        }
    });
})();
</script>
@endpush
