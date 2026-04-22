<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'KeyLicense') }} - Admin</title>
    <meta name="description" content="{{ $description ?? 'KeyLicense admin portal for managing licenses, products, and activations.' }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>[x-cloak]{display:none !important;}</style>
</head>
<body class="min-h-screen bg-[#050814] font-sans text-slate-100 antialiased">
    <div
        x-data="adminLayout()"
        x-init="init()"
        @keydown.escape.window="mobileSidebarOpen = false"
        class="relative min-h-screen overflow-hidden"
    >
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(248,184,3,0.12),transparent_32%),radial-gradient(circle_at_bottom_right,rgba(240,172,184,0.12),transparent_28%),linear-gradient(180deg,rgba(255,255,255,0.02),transparent_55%)] md:bg-[radial-gradient(circle_at_top,rgba(248,184,3,0.16),transparent_32%),radial-gradient(circle_at_bottom_right,rgba(240,172,184,0.16),transparent_28%),linear-gradient(180deg,rgba(255,255,255,0.02),transparent_55%)]"></div>
        <div class="absolute inset-x-0 top-0 h-px bg-linear-to-r from-transparent via-[#F8B803] to-transparent opacity-40"></div>
        <div class="absolute left-10 top-24 hidden h-72 w-72 rounded-full bg-[#F8B803]/10 blur-3xl md:block"></div>
        <div class="absolute right-0 top-1/2 hidden h-96 w-96 rounded-full bg-[#F0ACB8]/10 blur-3xl md:block"></div>

        <div class="relative z-10 flex min-h-screen">
            <x-admin.sidebar />

            <div
                x-show="mobileSidebarOpen"
                x-cloak
                aria-hidden="true"
                class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden"
                @click="closeMobileSidebar()"
            ></div>

            <div
                x-show="mobileSidebarOpen"
                x-cloak
                role="dialog"
                aria-modal="true"
                aria-labelledby="mobile-sidebar-title"
                x-transition:enter="transform transition ease-out duration-300"
                x-transition:enter-start="-translate-x-full opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100"
                x-transition:leave="transform transition ease-in duration-200"
                x-transition:leave-start="translate-x-0 opacity-100"
                x-transition:leave-end="-translate-x-full opacity-0"
                class="fixed inset-y-0 left-0 z-50 w-[85vw] max-w-sm lg:hidden"
            >
                <x-admin.sidebar mobile titleId="mobile-sidebar-title" />
            </div>

            <div class="flex min-w-0 flex-1 flex-col">
                <x-admin.topbar :title="$title ?? null" />

                @hasSection('header')
                <section class="border-b border-white/10 bg-white/5 backdrop-blur-xl">
                    <div class="w-full px-4 py-8 sm:px-6 lg:px-8 2xl:px-10 3xl:px-12">
                        @yield('header')
                    </div>
                </section>
                @endif

                <main class="flex-1 bg-[#050814]/95">
                    <div class="w-full space-y-4 px-4 py-6 sm:px-6 lg:px-8 2xl:px-10 3xl:px-12">
                        <div x-data="flashToastStack()" x-init="init()">
                            <x-ui.flash />
                        </div>
                        @yield('content')
                    </div>
                </main>
            </div>
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
