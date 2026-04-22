<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'KeyLicense') }} - Admin Login</title>
    <meta name="description" content="Đăng nhập quản trị KeyLicense để quản lý licenses, products và activation flows.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#0B1020] font-sans text-slate-100 antialiased">
    <div class="relative min-h-screen overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(232,176,75,0.14),transparent_30%),radial-gradient(circle_at_bottom_right,rgba(92,59,138,0.12),transparent_24%),linear-gradient(180deg,rgba(255,255,255,0.02),transparent_55%)]"></div>
        <div class="absolute inset-x-0 top-0 h-px bg-linear-to-r from-transparent via-[#E8B04B] to-transparent opacity-40"></div>
        <div class="absolute left-10 top-20 h-72 w-72 rounded-full bg-[#E8B04B]/10 blur-3xl"></div>
        <div class="absolute right-0 top-1/2 h-96 w-96 rounded-full bg-[#5C3B8A]/12 blur-3xl"></div>

        <div class="relative z-10 mx-auto flex min-h-screen max-w-7xl items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
            <div class="w-full max-w-md">
                <div class="mb-6 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-linear-to-br from-[#E8B04B] to-[#F2C469] text-lg font-black text-[#0B1020] shadow-lg shadow-amber-500/30 ring-1 ring-white/10">
                        K
                    </div>
                    <h1 class="mt-4 text-xl font-semibold tracking-tight text-white">
                        KeyLicense Admin
                    </h1>
                    <p class="mt-1 text-sm text-slate-400">
                        Đăng nhập để quản trị license, sản phẩm và activation.
                    </p>
                </div>

                <div class="relative">
                    <div class="absolute -inset-3 rounded-4xl bg-linear-to-r from-[#E8B04B]/10 via-transparent to-[#5C3B8A]/10 blur-2xl"></div>

                    <x-ui.card class="relative rounded-4xl">
                        <form method="POST" action="{{ route('admin.login') }}" class="space-y-4">
                            @csrf

                            <x-ui.input
                                name="username"
                                type="email"
                                label="Email đăng nhập"
                                placeholder="admin@keylicense.com.vn"
                                autocomplete="username"
                                prefix="@"
                                required
                            />

                            <x-ui.input
                                name="password"
                                type="password"
                                label="Mật khẩu"
                                placeholder="Nhập mật khẩu"
                                autocomplete="current-password"
                                prefix="*"
                                required
                            />

                            <div class="flex items-center justify-between gap-3">
                                <x-ui.checkbox name="remember" label="Ghi nhớ đăng nhập" />
                                <span class="text-[11px] text-slate-500">Secure admin access</span>
                            </div>

                            <x-ui.button type="submit" class="w-full">
                                Đăng nhập
                            </x-ui.button>
                        </form>

                        <p class="mt-5 border-t border-white/10 pt-4 text-center text-[11px] text-slate-500">
                            Bảo mật bởi KeyLicense · Vui lòng chỉ đăng nhập trên thiết bị tin cậy.
                        </p>
                    </x-ui.card>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
