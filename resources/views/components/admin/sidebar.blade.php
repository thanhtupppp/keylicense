@props(['mobile' => false, 'titleId' => 'admin-sidebar-title'])

@if($mobile)
<aside
    x-show="mobileSidebarOpen"
    x-transition:enter="transform transition ease-out duration-350"
    x-transition:enter-start="-translate-x-full opacity-0"
    x-transition:enter-end="translate-x-0 opacity-100"
    x-transition:leave="transform transition ease-in duration-220"
    x-transition:leave-start="translate-x-0 opacity-100"
    x-transition:leave-end="-translate-x-full opacity-0"
    class="flex h-full w-full flex-col border-r border-white/10 bg-[#0B1020]/95 shadow-[24px_0_80px_rgba(0,0,0,0.45)] backdrop-blur-xl"
>
    <div class="flex items-center justify-between border-b border-white/10 px-6 py-5">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-linear-to-br from-[#F8B803] to-[#F0ACB8] text-sm font-black text-[#050814] shadow-lg shadow-[#F8B803]/20">K</div>
            <div class="min-w-0">
                <h2 id="{{ $titleId }}" class="truncate text-sm font-semibold uppercase tracking-[0.2em] text-[#F8B803]">KeyLicense</h2>
                <div class="truncate text-xs text-slate-300">Admin portal</div>
            </div>
        </div>

        <button type="button" @click="closeMobileSidebar()" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10" aria-label="Đóng menu">
            <span class="inline-flex rotate-0 transition-transform duration-300 hover:rotate-90"><x-admin.icon name="chevron" class="h-5 w-5 rotate-180" /></span>
        </button>
    </div>

    <nav class="flex-1 space-y-2 px-4 py-5">
        <x-admin.nav-item :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" title="Dashboard"><x-admin.icon name="dashboard" class="h-5 w-5" />Dashboard</x-admin.nav-item>
        <x-admin.nav-item :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')" title="Sản phẩm"><x-admin.icon name="products" class="h-5 w-5" />Sản phẩm</x-admin.nav-item>
        <x-admin.nav-item :href="route('admin.licenses.index')" :active="request()->routeIs('admin.licenses.*')" title="License"><x-admin.icon name="license" class="h-5 w-5" />License</x-admin.nav-item>
        <x-admin.nav-item :href="route('admin.audit-logs')" :active="request()->routeIs('admin.audit-logs')" title="Nhật ký"><x-admin.icon name="logs" class="h-5 w-5" />Nhật ký</x-admin.nav-item>
    </nav>

    <div class="border-t border-white/10 p-4 space-y-2">
        <x-admin.nav-item :href="route('admin.profile.edit')" :active="request()->routeIs('admin.profile.*')" title="Profile"><x-admin.icon name="profile" class="h-5 w-5" />Profile</x-admin.nav-item>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-medium text-slate-200 transition hover:border-white/25 hover:bg-white/10" title="Đăng xuất">
                <span class="inline-flex h-5 w-5 items-center justify-center"><x-admin.icon name="logout" class="h-5 w-5" /></span>
                <span class="ml-3">Đăng xuất</span>
            </button>
        </form>
    </div>
</aside>
@else
<aside
    x-bind:class="sidebarCollapsed ? 'w-28' : 'w-72'"
    class="hidden shrink-0 border-r border-white/10 bg-white/5 backdrop-blur-xl transition-[width] duration-300 ease-out lg:flex lg:flex-col"
>
    <button
        type="button"
        @click="toggleSidebar()"
        class="flex items-center gap-3 border-b border-white/10 px-6 py-5 text-left transition-colors hover:bg-white/5"
        x-bind:class="sidebarCollapsed ? 'justify-center px-2.5' : ''"
        aria-label="Thu gọn hoặc mở rộng thanh bên"
        x-bind:aria-pressed="sidebarCollapsed.toString()"
    >
        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-linear-to-br from-[#F8B803] to-[#F0ACB8] text-sm font-black text-[#050814] shadow-lg shadow-[#F8B803]/20">K</div>
        <div x-cloak x-show="!sidebarCollapsed" x-transition.opacity class="min-w-0">
            <div class="truncate text-sm font-semibold uppercase tracking-[0.2em] text-[#F8B803]">KeyLicense</div>
            <div class="truncate text-xs text-slate-300">Admin portal</div>
        </div>
        <span x-cloak x-show="sidebarCollapsed" class="ml-auto inline-flex h-6 w-6 items-center justify-center rounded-full border border-white/10 bg-white/5 text-xs text-slate-300 transition-transform duration-300 group-hover:rotate-12"><x-admin.icon name="chevron" class="h-4 w-4 rotate-90" /></span>
    </button>

    <nav class="flex-1 space-y-2 px-4 py-5">
        <x-admin.nav-item :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" title="Dashboard">
            <x-admin.icon name="dashboard" class="h-5 w-5 shrink-0" />
            <span x-cloak x-show="!sidebarCollapsed" x-transition.opacity>Dashboard</span>
        </x-admin.nav-item>
        <x-admin.nav-item :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')" title="Sản phẩm">
            <x-admin.icon name="products" class="h-5 w-5 shrink-0" />
            <span x-cloak x-show="!sidebarCollapsed" x-transition.opacity>Sản phẩm</span>
        </x-admin.nav-item>
        <x-admin.nav-item :href="route('admin.licenses.index')" :active="request()->routeIs('admin.licenses.*')" title="License">
            <x-admin.icon name="license" class="h-5 w-5 shrink-0" />
            <span x-cloak x-show="!sidebarCollapsed" x-transition.opacity>License</span>
        </x-admin.nav-item>
        <x-admin.nav-item :href="route('admin.audit-logs')" :active="request()->routeIs('admin.audit-logs')" title="Nhật ký">
            <x-admin.icon name="logs" class="h-5 w-5 shrink-0" />
            <span x-cloak x-show="!sidebarCollapsed" x-transition.opacity>Nhật ký</span>
        </x-admin.nav-item>
    </nav>

    <div class="border-t border-white/10 p-4 space-y-2">
        <x-admin.nav-item :href="route('admin.profile.edit')" :active="request()->routeIs('admin.profile.*')" title="Profile">
            <x-admin.icon name="profile" class="h-5 w-5 shrink-0" />
            <span x-cloak x-show="!sidebarCollapsed" x-transition.opacity>Profile</span>
        </x-admin.nav-item>

        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="group flex w-full items-center rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-medium text-slate-200 transition hover:border-white/25 hover:bg-white/10" x-bind:class="sidebarCollapsed ? 'justify-center px-3' : 'flex items-center'" title="Đăng xuất">
                <span class="inline-flex h-5 w-5 items-center justify-center shrink-0 transition-transform duration-300 group-hover:rotate-12"><x-admin.icon name="logout" class="h-5 w-5" /></span>
                <span class="ml-3" x-cloak x-show="!sidebarCollapsed" x-transition.opacity>Đăng xuất</span>
            </button>
        </form>
    </div>
</aside>
@endif
