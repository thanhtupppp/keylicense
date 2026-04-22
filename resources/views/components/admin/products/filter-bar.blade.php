<form method="GET" action="{{ route('admin.products.index') }}" class="grid gap-4 rounded-3xl border border-white/10 bg-white/5 p-4 md:grid-cols-3 xl:grid-cols-4">
    <div class="xl:col-span-2">
        <label for="search" class="mb-2 block text-sm font-medium text-slate-300">Tìm kiếm</label>
        <input
            id="search"
            name="search"
            type="text"
            value="{{ request('search') }}"
            placeholder="Tên sản phẩm hoặc slug..."
            class="w-full rounded-2xl border border-white/10 bg-[#0f172a] px-4 py-3 text-sm text-white outline-none transition placeholder:text-slate-500 focus:border-[#F8B803]"
        />
    </div>

    <div>
        <label for="status" class="mb-2 block text-sm font-medium text-slate-300">Trạng thái</label>
        <select
            id="status"
            name="status"
            class="w-full rounded-2xl border border-white/10 bg-[#0f172a] px-4 py-3 text-sm text-white outline-none transition focus:border-[#F8B803]"
        >
            <option value="">Tất cả</option>
            <option value="active" @selected(request('status') === 'active')>Đang hoạt động</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Ngừng hoạt động</option>
        </select>
    </div>

    <div class="flex items-end gap-3">
        <button type="submit" class="rounded-full bg-linear-to-r from-[#F8B803] to-[#F0ACB8] px-4 py-3 text-sm font-semibold text-[#050814] transition hover:opacity-95">
            Lọc
        </button>
        <a href="{{ route('admin.products.index') }}" class="rounded-full border border-white/10 bg-white/5 px-4 py-3 text-sm font-semibold text-slate-200 transition hover:border-white/25 hover:bg-white/10">
            Xóa lọc
        </a>
    </div>
</form>
