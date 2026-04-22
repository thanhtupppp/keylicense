@props(['user', 'initials'])

<x-admin.page-header
    overline="Account"
    title="Hồ sơ quản trị"
    description="Cập nhật tên hiển thị, email đăng nhập và mật khẩu với lớp bảo mật riêng cho admin."
>
    <div class="flex flex-wrap items-center gap-3">
        <div class="inline-flex items-center gap-3 rounded-full border border-[#F8B803]/20 bg-[#F8B803]/10 px-4 py-2 text-sm font-medium text-[#F8B803] shadow-lg shadow-[#F8B803]/10">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#F8B803]/15 text-sm font-black text-[#F8B803]">{{ $initials }}</span>
            <span>{{ $user->name }}</span>
        </div>
        <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm text-slate-300">
            <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
            Admin account
        </div>
    </div>
</x-admin.page-header>
