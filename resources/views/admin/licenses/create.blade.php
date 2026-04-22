@extends('layouts.admin')

@section('header')
    <x-admin.page-header
        overline="Licenses"
        title="Tạo License Mới"
        description="Cấp license cho sản phẩm theo mô hình phù hợp."
    >
        <a href="{{ route('admin.licenses.index') }}" class="rounded-full border border-white/10 bg-white/5 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:border-white/25 hover:bg-white/10">
            Quay lại
        </a>
    </x-admin.page-header>
@endsection

@section('content')
    @php
        $productOptions = $products->mapWithKeys(function ($product) {
            return [$product->id => $product->name . ' (' . $product->slug . ')'];
        })->all();
    @endphp

    <x-ui.card>
        @if($products->isEmpty())
            <x-ui.alert type="warning" title="Chưa có sản phẩm khả dụng">
                Bạn cần tạo ít nhất một sản phẩm đang hoạt động trước khi cấp license.
            </x-ui.alert>

            <div class="mt-4 flex flex-wrap gap-3">
                <a href="{{ route('admin.products.create') }}" class="rounded-full bg-linear-to-r from-[#F8B803] to-[#F0ACB8] px-5 py-3 text-sm font-semibold text-[#050814] transition hover:opacity-95">
                    Tạo sản phẩm ngay
                </a>
                <a href="{{ route('admin.products.index') }}" class="rounded-full border border-white/10 bg-white/5 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:border-white/25 hover:bg-white/10">
                    Xem danh sách sản phẩm
                </a>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.licenses.store') }}" class="space-y-5 {{ $products->isEmpty() ? 'mt-6 opacity-70 pointer-events-none' : '' }}">
            @csrf

            <x-ui.select
                name="product_id"
                label="Sản phẩm *"
                :options="$productOptions"
                placeholder="Chọn sản phẩm"
                :value="old('product_id')"
                :disabled="$products->isEmpty()"
                required
            />

            <x-ui.select
                name="license_model"
                label="Mô hình cấp phép *"
                :options="[
                    'per-device' => 'Per-Device (1 thiết bị)',
                    'per-user' => 'Per-User (1 người dùng)',
                    'floating' => 'Floating (nhiều thiết bị đồng thời)',
                ]"
                placeholder="Chọn mô hình"
                :value="old('license_model')"
                required
            />

            <x-ui.input name="quantity" type="number" label="Số lượng license *" value="{{ old('quantity', 1) }}" min="1" max="100" description="Từ 1 đến 100 license trong một lần tạo" required />

            <div id="max_seats_field" class="hidden">
                <x-ui.input name="max_seats" type="number" label="Số lượng seat tối đa *" value="{{ old('max_seats') }}" min="1" description="Số lượng thiết bị có thể sử dụng đồng thời" />
            </div>

            <x-ui.input name="expiry_date" type="date" label="Ngày hết hạn" value="{{ old('expiry_date') }}" description="Để trống nếu muốn license vĩnh viễn" />

            <div class="border-t border-white/10 pt-6">
                <h3 class="mb-4 text-lg font-medium text-white">Thông tin khách hàng (tùy chọn)</h3>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-ui.input name="customer_name" label="Tên khách hàng" value="{{ old('customer_name') }}" maxlength="255" />
                    <x-ui.input name="customer_email" type="email" label="Email khách hàng" value="{{ old('customer_email') }}" maxlength="255" />
                </div>
            </div>

            <x-ui.textarea name="notes" label="Ghi chú nội bộ" value="{{ old('notes') }}" rows="3" maxlength="1000" description="Tối đa 1000 ký tự. Ghi chú này chỉ hiển thị trong giao diện Admin." />

            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.licenses.index') }}" class="rounded-full border border-white/10 bg-white/5 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:border-white/25 hover:bg-white/10">Hủy</a>
                <x-ui.button type="submit">Tạo License</x-ui.button>
            </div>
        </form>
    </x-ui.card>

@endsection
