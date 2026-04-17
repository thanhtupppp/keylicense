@extends('layouts.client', [
    'title' => 'Đăng nhập khách hàng | KeyLicense',
    'description' => 'Đăng nhập và đăng ký tài khoản khách hàng',
])

@section('content')
    <div class="topbar">
        <div class="brand">
            <span class="mark"></span>
            <div>
                <h1 class="title">Đăng nhập khách hàng</h1>
                <div class="muted">Customer auth và onboarding</div>
            </div>
        </div>
        <x-ui.button :href="route('client.portal')" variant="alt">← Cổng khách hàng</x-ui.button>
    </div>

    <div class="grid cols-2">
        <section class="card stack">
            <x-ui.section-header title="Đăng nhập" subtitle="Dùng email và mật khẩu để vào portal." />
            <div class="grid" style="gap:12px;">
                <x-ui.input label="Email" name="email" placeholder="customer@example.com" />
                <x-ui.input label="Mật khẩu" name="password" type="password" placeholder="••••••••" />
                <x-ui.button>Đăng nhập</x-ui.button>
            </div>
        </section>

        <section class="card stack">
            <x-ui.section-header title="Đăng ký nhanh" subtitle="Tạo tài khoản customer mới." />
            <div class="grid" style="gap:12px;">
                <x-ui.input label="Họ tên" name="register_name" placeholder="Nguyễn Văn A" />
                <x-ui.input label="Email" name="register_email" placeholder="customer@example.com" />
                <x-ui.button variant="alt">Tạo tài khoản</x-ui.button>
            </div>
        </section>
    </div>
@endsection
