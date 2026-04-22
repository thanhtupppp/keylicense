@props(['product'])

<div class="flex flex-wrap items-center gap-3">
    <a href="{{ route('admin.products.edit', $product) }}" class="inline-flex items-center justify-center rounded-full bg-linear-to-r from-[#F8B803] to-[#F0ACB8] px-5 py-3 text-sm font-semibold text-[#050814] shadow-lg shadow-[#F8B803]/20 transition hover:brightness-105">
        Chỉnh sửa
    </a>
    <a href="{{ route('admin.products.index') }}" class="inline-flex items-center justify-center rounded-full border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/25 hover:bg-white/10">
        Quay lại
    </a>
</div>
