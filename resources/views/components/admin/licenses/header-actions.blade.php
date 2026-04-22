<div class="flex flex-wrap items-center gap-3">
    <a href="{{ route('admin.licenses.export', request()->query()) }}" class="inline-flex items-center justify-center rounded-full border border-emerald-400/20 bg-emerald-400/10 px-4 py-2.5 text-sm font-semibold text-emerald-200 transition hover:bg-emerald-400/15">
        Xuất CSV
    </a>
    <a href="{{ route('admin.licenses.create') }}" class="inline-flex items-center justify-center rounded-full bg-linear-to-r from-[#F8B803] to-[#F0ACB8] px-4 py-2.5 text-sm font-semibold text-[#050814] shadow-lg shadow-[#F8B803]/20 transition hover:brightness-105">
        Tạo License
    </a>
</div>
