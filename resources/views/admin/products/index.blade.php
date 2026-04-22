@extends('layouts.admin')

@section('header')
    <x-admin.page-header
        overline="Catalog"
        title="Sản phẩm"
        description="Quản lý danh sách sản phẩm và cấu hình license cho từng sản phẩm."
    >
        <x-admin.products.header-actions />
    </x-admin.page-header>
@endsection

@section('content')
    @php
        $createdProductId = request('created');
    @endphp

    <div class="space-y-4">
        <x-admin.products.filter-bar />

        <x-ui.card>
            @if($products->count())
                <x-admin.products.table :products="$products" />
            @else
                <x-ui.empty-state
                    icon="folder"
                    title="Chưa có sản phẩm nào"
                    description="Tạo sản phẩm đầu tiên để bắt đầu quản lý license."
                >
                    <a href="{{ route('admin.products.create') }}" class="rounded-full bg-linear-to-r from-[#F8B803] to-[#F0ACB8] px-4 py-2 text-xs font-semibold text-[#050814]">Tạo sản phẩm</a>
                </x-ui.empty-state>
            @endif
        </x-ui.card>
    </div>

    @if($createdProductId)
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const row = document.querySelector(`[data-product-id="{{ $createdProductId }}"]`);
                    if (row) {
                        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        row.classList.add('ring-2', 'ring-[#F8B803]', 'ring-offset-2', 'ring-offset-[#050814]');
                        setTimeout(() => row.classList.remove('ring-2', 'ring-[#F8B803]', 'ring-offset-2', 'ring-offset-[#050814]'), 5000);
                    }
                });
            </script>
        @endpush
    @endif
@endsection
