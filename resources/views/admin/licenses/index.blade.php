@extends('layouts.admin')

@section('header')
    <x-admin.page-header
        overline="Licenses"
        title="Quản lý License"
        description="Theo dõi, lọc và điều phối vòng đời license theo sản phẩm, mô hình và trạng thái."
    >
        <x-admin.licenses.header-actions />
    </x-admin.page-header>
@endsection

@section('content')
    <div class="rounded-4xl border border-white/10 bg-[rgba(18,24,43,0.88)] p-6 shadow-[0_24px_80px_rgba(0,0,0,0.45)] backdrop-blur-2xl">
        <div class="mb-6">
            <x-admin.licenses.filter-bar :products="$products" />
        </div>

        @if($licenses->count())
            <x-admin.licenses.table :licenses="$licenses" />
        @else
            <x-ui.empty-state
                icon="document"
                title="Không có license phù hợp"
                description="Thử thay đổi bộ lọc hoặc tạo license mới."
            >
                <a href="{{ route('admin.licenses.create') }}" class="rounded-full bg-linear-to-r from-[#F8B803] to-[#F0ACB8] px-4 py-2 text-xs font-semibold text-[#050814]">Tạo license</a>
            </x-ui.empty-state>
        @endif
    </div>
@endsection
