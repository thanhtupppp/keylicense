@extends('layouts.admin')

@section('content')
    <x-slot name="header">
        <x-admin.page-header
            overline="Licenses"
            title="Chi tiết License: ****-****-****-{{ $license->key_last4 }}"
            description="Xem thông tin, trạng thái, activation và lịch sử vận hành của license."
        >
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.licenses.index') }}" class="rounded-full border border-white/10 bg-white/5 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:border-white/25 hover:bg-white/10">
                    Quay lại
                </a>
            </div>
        </x-admin.page-header>
    </x-slot>

    <div class="space-y-6">
        <x-ui.card>
            <div class="grid gap-6 md:grid-cols-2">
                <div class="space-y-4">
                    <div>
                        <div class="text-xs uppercase tracking-[0.2em] text-slate-500">License Key</div>
                        <div class="mt-1 font-mono text-sm text-slate-100">****-****-****-{{ $license->key_last4 }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Sản phẩm</div>
                        <a href="{{ route('admin.products.show', $license->product) }}" class="mt-1 inline-flex text-sm font-medium text-[#F8B803] hover:underline">
                            {{ $license->product->name }}
                        </a>
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Trạng thái</div>
                        <div class="mt-1 text-sm text-slate-100">{{ ucfirst($license->status) }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Ngày hết hạn</div>
                        <div class="mt-1 text-sm text-slate-100">
                            @if($license->expiry_date)
                                {{ $license->expiry_date->format('d/m/Y') }}
                            @else
                                <span class="text-slate-500">Vĩnh viễn</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-slate-100">Nhật ký license</h3>
                    <p class="mt-1 text-xs text-slate-400">Xem audit logs cho license này.</p>
                </div>
                <a href="{{ route('admin.audit-logs', ['subject_type' => 'license', 'subject_id' => $license->id]) }}" class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-white/25 hover:bg-white/10">
                    Xem audit logs
                </a>
            </div>
        </x-ui.card>
    </div>
@endsection
