@extends('layouts.admin')

@section('content')
    <x-slot name="header">
        <x-admin.page-header
            overline="Catalog"
            title="{{ $product->name }}"
            description="Thông tin chi tiết sản phẩm, trạng thái và cấu hình license."
        >
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.products.edit', $product) }}" class="rounded-full border border-white/10 bg-white/5 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:border-white/25 hover:bg-white/10">Chỉnh sửa</a>
                <a href="{{ route('admin.products.index') }}" class="rounded-full border border-white/10 bg-white/5 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:border-white/25 hover:bg-white/10">Quay lại</a>
            </div>
        </x-admin.page-header>
    </x-slot>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-ui.card>
            <div class="space-y-3 text-sm text-slate-300">
                <p><span class="text-slate-500">Slug:</span> {{ $product->slug }}</p>
                <p><span class="text-slate-500">Trạng thái:</span> {{ $product->status }}</p>
                <p><span class="text-slate-500">TTL token:</span> {{ $product->offline_token_ttl_hours }} giờ</p>
            </div>
        </x-ui.card>

        <x-ui.card>
            <p class="text-sm text-slate-300">{{ $product->description }}</p>
        </x-ui.card>
    </div>

    <x-ui.card class="mt-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-100">Nhật ký hoạt động liên quan</h3>
                <p class="mt-1 text-xs text-slate-400">Xem các thay đổi trên sản phẩm này trong audit logs.</p>
            </div>
            <a href="{{ route('admin.audit-logs', ['subject_type' => 'product', 'subject_id' => $product->id]) }}" class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-white/25 hover:bg-white/10">
                Xem audit logs
            </a>
        </div>
    </x-ui.card>
@endsection
