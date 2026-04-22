<div class="flex flex-wrap items-center gap-3">
    <a href="{{ route('admin.products.create') }}" class="inline-flex items-center justify-center rounded-full bg-linear-to-r from-[#F8B803] to-[#F0ACB8] px-4 py-2.5 text-sm font-semibold text-[#050814] transition hover:opacity-95">
        Tạo sản phẩm
    </a>

    <a href="{{ route('admin.products.index') }}" class="inline-flex items-center justify-center rounded-full border border-white/10 bg-white/5 px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:border-white/25 hover:bg-white/10">
        Tải lại
    </a>

    <a href="{{ route('admin.products.index', request()->query()) }}" class="inline-flex items-center justify-center rounded-full border border-white/10 bg-white/5 px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:border-white/25 hover:bg-white/10">
        Xuất trạng thái hiện tại
    </a>
</div>
