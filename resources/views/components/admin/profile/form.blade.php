@props(['user'])

<div class="rounded-4xl border border-white/10 bg-[radial-gradient(circle_at_top,rgba(248,184,3,0.14),transparent_30%),linear-gradient(180deg,rgba(18,24,43,0.96),rgba(10,15,28,0.96))] p-6 shadow-[0_24px_80px_rgba(0,0,0,0.45)] backdrop-blur-2xl">
    <div class="mb-6 flex items-start gap-4 rounded-3xl border border-white/10 bg-white/5 p-4">
        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-linear-to-br from-[#F8B803] to-[#F0ACB8] text-xl font-black text-[#050814] shadow-lg shadow-[#F8B803]/20">
            {{ $initials }}
        </div>
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="text-lg font-semibold text-white">{{ $user->name }}</h3>
                <span class="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-300">Verified</span>
            </div>
            <p class="mt-1 break-all text-sm text-slate-400">{{ $user->email }}</p>
            <p class="mt-2 text-xs text-slate-500">Bắt buộc nhập mật khẩu hiện tại để xác nhận mọi thay đổi.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        <x-ui.input name="name" label="Tên hiển thị" value="{{ old('name', $user->name) }}" placeholder="Administrator" required />
        <x-ui.input name="email" type="email" label="Email đăng nhập" value="{{ old('email', $user->email) }}" placeholder="admin@keylicense.com.vn" required />

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-3xl border border-white/10 bg-white/5 p-4 text-sm text-slate-300">
                <div class="font-medium text-white">Kiểm tra email</div>
                <div class="mt-1 text-slate-400">Email phải chưa được dùng bởi tài khoản khác.</div>
            </div>
            <div class="rounded-3xl border border-white/10 bg-white/5 p-4 text-sm text-slate-300">
                <div class="font-medium text-white">Kiểm tra mật khẩu</div>
                <div class="mt-1 text-slate-400">Tối thiểu 12 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt.</div>
            </div>
        </div>

        <x-ui.input name="current_password" type="password" label="Mật khẩu hiện tại" placeholder="Nhập mật khẩu hiện tại" required />

        <div class="grid gap-5 sm:grid-cols-2">
            <x-ui.input name="password" type="password" label="Mật khẩu mới" placeholder="Để trống nếu không đổi" />
            <x-ui.input name="password_confirmation" type="password" label="Xác nhận mật khẩu mới" placeholder="Nhập lại mật khẩu mới" />
        </div>

        <div class="flex flex-wrap items-center gap-3 pt-2">
            <x-ui.button type="submit">Lưu thay đổi</x-ui.button>
            <a href="{{ route('admin.dashboard') }}" class="rounded-full border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/25 hover:bg-white/10">Quay lại dashboard</a>
        </div>
    </form>
</div>
