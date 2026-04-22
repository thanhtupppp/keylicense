@props(['products'])

<form method="GET" action="{{ route('admin.licenses.index') }}" class="space-y-4">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <x-ui.input name="search" label="Tìm kiếm" value="{{ request('search') }}" placeholder="Key, tên khách hàng, email, sản phẩm..." />

        <x-ui.select name="product_id" label="Sản phẩm" :options="$products->pluck('name', 'id')->all()" placeholder="Tất cả sản phẩm" :value="request('product_id')" />

        <x-ui.select name="license_model" label="Mô hình" :options="[
            'per-device' => 'Per-Device',
            'per-user' => 'Per-User',
            'floating' => 'Floating',
        ]" placeholder="Tất cả mô hình" :value="request('license_model')" />

        <x-ui.select name="status" label="Trạng thái" :options="[
            'inactive' => 'Inactive',
            'active' => 'Active',
            'expired' => 'Expired',
            'suspended' => 'Suspended',
            'revoked' => 'Revoked',
        ]" placeholder="Tất cả trạng thái" :value="request('status')" />

        <x-ui.input name="date_from" type="date" label="Từ ngày" value="{{ request('date_from') }}" />
        <x-ui.input name="date_to" type="date" label="Đến ngày" value="{{ request('date_to') }}" />
    </div>

    <div class="flex flex-wrap gap-3">
        <x-ui.button type="submit">Tìm kiếm</x-ui.button>
        <a href="{{ route('admin.licenses.index') }}" class="rounded-full border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/25 hover:bg-white/10">Xóa bộ lọc</a>
    </div>
</form>
