<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'KeyLicense') }}</title>
    <meta name="description" content="{{ $description ?? 'Nền tảng quản lý license, sản phẩm và kích hoạt bản quyền cho doanh nghiệp.' }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#050814] text-slate-100 antialiased font-sans">
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(248,184,3,0.18),transparent_34%),radial-gradient(circle_at_bottom_right,rgba(240,172,184,0.14),transparent_28%)]"></div>
        <div class="absolute inset-x-0 top-0 h-px bg-linear-to-r from-transparent via-[#F8B803] to-transparent opacity-40"></div>

        <header class="relative z-10 border-b border-white/10 bg-white/5 backdrop-blur-xl">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ url('/') }}" class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-linear-to-br from-[#F8B803] to-[#F0ACB8] text-[#050814] font-black shadow-lg shadow-[#F8B803]/20">
                        K
                    </div>
                    <div>
                        <div class="text-sm font-semibold tracking-[0.2em] text-[#F8B803] uppercase">KeyLicense</div>
                        <div class="text-xs text-slate-300">License management platform</div>
                    </div>
                </a>

                <nav class="hidden items-center gap-6 md:flex">
                    <a href="#features" class="text-sm text-slate-300 transition hover:text-white">Tính năng</a>
                    <a href="#workflow" class="text-sm text-slate-300 transition hover:text-white">Quy trình</a>
                    <a href="#contact" class="text-sm text-slate-300 transition hover:text-white">Liên hệ</a>
                    <a href="{{ route('admin.login') }}" class="rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm font-medium text-white transition hover:border-[#F8B803]/40 hover:bg-[#F8B803]/10">
                        Admin login
                    </a>
                </nav>
            </div>
        </header>

        <main class="relative z-10">
            {{ $slot }}
        </main>

        <footer class="relative z-10 border-t border-white/10 bg-black/20">
            <div class="mx-auto max-w-7xl px-4 py-8 text-sm text-slate-400 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p>&copy; {{ date('Y') }} KeyLicense. All rights reserved.</p>
                    <p>Secure license issuing, activation, and management.</p>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
