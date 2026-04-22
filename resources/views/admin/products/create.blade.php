@extends('layouts.admin')

@section('content')
    <x-slot name="header">
        <x-admin.page-header
            overline="Catalog"
            title="Tạo sản phẩm mới"
            description="Thêm sản phẩm để bắt đầu cấu hình vòng đời license."
        >
            <a href="{{ route('admin.products.index') }}" class="rounded-full border border-white/10 bg-white/5 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:border-white/25 hover:bg-white/10">
                Quay lại
            </a>
        </x-admin.page-header>
    </x-slot>

    <x-ui.card>
        <form method="POST" action="{{ route('admin.products.store') }}" class="space-y-5">
            @csrf

            <x-ui.input name="name" label="Tên sản phẩm *" value="{{ old('name') }}" required />
            <x-ui.input name="slug" label="Mã sản phẩm *" value="{{ old('slug') }}" placeholder="vi-du: my-product-v1" required />
            <x-ui.textarea name="description" label="Mô tả" value="{{ old('description') }}" rows="3" />
            <x-ui.input name="logo_url" type="url" label="URL Logo" value="{{ old('logo_url') }}" placeholder="https://example.com/logo.png" />

            <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-200">Platforms hỗ trợ</label>
                <div class="grid grid-cols-2 gap-2 md:grid-cols-3">
                    @foreach(['Windows', 'macOS', 'Linux', 'Android', 'iOS', 'Web'] as $platform)
                        <x-ui.checkbox name="platforms[]" :label="$platform" :checked="in_array($platform, old('platforms', []))" />
                    @endforeach
                </div>
            </div>

            <x-ui.input name="offline_token_ttl_hours" type="number" label="Thời gian sống Offline Token (giờ) *" value="{{ old('offline_token_ttl_hours', 24) }}" min="1" max="168" description="Từ 1 đến 168 giờ (7 ngày). Mặc định: 24 giờ." required />

            <div class="flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('admin.products.index') }}" class="rounded-full border border-white/10 bg-white/5 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:border-white/25 hover:bg-white/10">Hủy</a>
                <x-ui.button type="submit">Tạo sản phẩm</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
