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

    @if (session('status'))
        <x-ui.notice type="success">{{ session('status') }}</x-ui.notice>
    @endif

    <div class="grid cols-2">
        <section class="card stack">
            <x-ui.section-header title="Đăng nhập" subtitle="Dùng email và mật khẩu để vào portal." />
            <form method="POST" action="{{ route('client.auth.login') }}" class="grid" style="gap:12px;">
                @csrf
                <x-ui.input label="Email" name="email" placeholder="customer@example.com" required />
                <x-ui.input label="Mật khẩu" name="password" type="password" placeholder="••••••••" required />
                <x-ui.button type="submit">Đăng nhập</x-ui.button>
            </form>
        </section>

        <section class="card stack">
            <x-ui.section-header title="Đăng ký nhanh" subtitle="Tạo tài khoản customer mới." />
            <form method="POST" action="{{ route('client.auth.register') }}" class="grid" style="gap:12px;">
                @csrf
                <x-ui.input label="Họ tên" name="name" placeholder="Nguyễn Văn A" />
                <x-ui.input label="Email" name="email" placeholder="customer@example.com" required />
                <x-ui.input label="Mật khẩu" name="password" type="password" placeholder="••••••••" required />
                <x-ui.input label="Mã xác minh" name="verification_code" placeholder="123456" required />
                <x-ui.button type="submit" variant="alt">Tạo tài khoản</x-ui.button>
            </form>
        </section>
    </div>
@endsection
