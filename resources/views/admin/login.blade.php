@extends('layouts.admin', [
    'title' => 'Admin Login | KeyLicense',
    'description' => 'Đăng nhập vào admin portal của KeyLicense',
])

@section('content')
    <main style="display:grid;place-items:center;min-height:calc(100vh - 56px);padding:24px 0;">
        <x-ui.card style="width:100%;max-width:460px;padding:30px;">
            <div class="brand" style="margin-bottom:24px;">
                <span class="mark"></span>
                <div>
                    <h1 class="title">Đăng nhập Admin</h1>
                    <div class="muted">KeyLicense Control Panel</div>
                </div>
            </div>

            @if ($errors->any())
                <x-ui.notice type="danger">{{ $errors->first() }}</x-ui.notice>
            @endif

            <form method="POST" action="{{ route('admin.portal.login.submit') }}" style="margin-top:22px;display:grid;gap:14px;">
                @csrf
                <x-ui.input label="Email" name="email" type="email" :value="old('email')" placeholder="admin@company.com" autocomplete="email" required />
                <x-ui.input label="Mật khẩu" name="password" type="password" placeholder="••••••••" autocomplete="current-password" required />

                <label style="display:flex;align-items:center;gap:10px;">
                    <input id="remember" name="remember" type="checkbox" value="1" {{ old('remember') ? 'checked' : '' }} style="width:18px;height:18px;accent-color:#60a5fa;" />
                    <span>Ghi nhớ đăng nhập</span>
                </label>

                <x-ui.button type="submit">Đăng nhập</x-ui.button>
            </form>

            <div class="muted" style="margin-top:18px;padding-top:18px;border-top:1px solid rgba(148,163,184,.14);font-size:.92rem;">
                Tài khoản dev mặc định: <span class="mono">admin@internal.local</span> / <span class="mono">secret-password</span>
            </div>
        </x-ui.card>
    </main>
@endsection
