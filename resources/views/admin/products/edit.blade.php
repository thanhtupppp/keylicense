@extends('layouts.admin')

@section('content')
    <x-slot name="header">
        <x-admin.page-header
            overline="Catalog"
            title="Chỉnh sửa sản phẩm: {{ $product->name }}"
            description="Cập nhật thông tin, logo, platforms hỗ trợ và TTL token."
        >
            <a href="{{ route('admin.products.show', $product) }}" class="inline-flex items-center justify-center rounded-full border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/25 hover:bg-white/10">
                Quay lại
            </a>
        </x-admin.page-header>
    </x-slot>

    <x-ui.card>
        <form method="POST" action="{{ route('admin.products.update', $product) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <x-ui.input name="name" label="Tên sản phẩm *" value="{{ old('name', $product->name) }}" placeholder="KeyLicense Pro" required />
            <x-ui.input name="slug" label="Mã sản phẩm *" value="{{ old('slug', $product->slug) }}" placeholder="vi-du: my-product-v1" required />
            <x-ui.textarea name="description" label="Mô tả" value="{{ old('description', $product->description) }}" rows="3" />
            <x-ui.input name="logo_url" type="url" label="URL Logo" value="{{ old('logo_url', $product->logo_url) }}" placeholder="https://example.com/logo.png" />

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-200">Platforms hỗ trợ</label>
                <div class="grid grid-cols-2 gap-2 md:grid-cols-3">
                    @foreach(['Windows', 'macOS', 'Linux', 'Android', 'iOS', 'Web'] as $platform)
                        <label class="flex items-center rounded-2xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-slate-200">
                            <input type="checkbox" name="platforms[]" value="{{ $platform }}" {{ in_array($platform, old('platforms', $product->platforms ?? [])) ? 'checked' : '' }} class="rounded border-white/20 bg-[#07111f] text-[#F8B803] focus:ring-[#F8B803]/20">
                            <span class="ml-2">{{ $platform }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <x-ui.input name="offline_token_ttl_hours" type="number" label="Thời gian sống Offline Token (giờ) *" value="{{ old('offline_token_ttl_hours', $product->offline_token_ttl_hours) }}" min="1" max="168" />

            <div class="rounded-2xl border border-white/10 bg-[#07111f] p-3">
                <label class="block text-sm font-medium text-slate-400">API Key (chỉ đọc)</label>
                <code class="mt-2 block break-all text-sm text-slate-200">{{ $product->api_key }}</code>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('admin.products.show', $product) }}" class="rounded-full border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/25 hover:bg-white/10">Hủy</a>
                <x-ui.button type="submit">Cập nhật sản phẩm</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
