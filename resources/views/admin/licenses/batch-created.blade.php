@extends('layouts.admin')

@section('header')
    <x-admin.page-header
        overline="Licenses"
        title="License đã tạo thành công"
        description="Đây là lần duy nhất bạn có thể xem đầy đủ keys. Hãy sao lưu ngay."
    >
        <a href="{{ route('admin.licenses.index') }}" class="rounded-full border border-white/10 bg-white/5 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:border-white/25 hover:bg-white/10">
            Quay lại danh sách
        </a>
    </x-admin.page-header>
@endsection

@section('content')
    <div x-data="adminLicenseBatchCreated()" class="space-y-6">
        <x-ui.card>
            <x-ui.empty-state icon="shield" title="Lưu lại license keys ngay" description="Sau khi rời trang này, bạn sẽ chỉ còn thấy 4 ký tự cuối của mỗi key.">
                <button type="button" x-on:click="copyAllKeys(@js(collect($createdLicenses)->pluck('plaintext_key')->all()))" class="rounded-full border border-[#F8B803]/20 bg-[#F8B803]/10 px-5 py-3 text-sm font-semibold text-[#F8B803] transition hover:bg-[#F8B803]/15">
                    Sao chép tất cả Keys
                </button>
            </x-ui.empty-state>
        </x-ui.card>

        <x-ui.card>
            <div class="mb-4 flex items-center justify-between gap-3">
                <h3 class="text-lg font-semibold text-white">Đã tạo {{ count($createdLicenses) }} license thành công</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/10 text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-[0.2em] text-slate-400">
                            <th class="px-4 py-4">#</th>
                            <th class="px-4 py-4">License Key</th>
                            <th class="px-4 py-4">Sản phẩm</th>
                            <th class="px-4 py-4">Mô hình</th>
                            <th class="px-4 py-4">Hết hạn</th>
                            <th class="px-4 py-4">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @foreach($createdLicenses as $index => $item)
                            <tr class="hover:bg-white/5">
                                <td class="px-4 py-4 text-slate-200">{{ $index + 1 }}</td>
                                <td class="px-4 py-4 font-mono text-xs text-slate-100">
                                    <div class="flex items-center gap-2">
                                        <span id="key-{{ $index }}">{{ $item['plaintext_key'] }}</span>
                                        <button type="button" x-on:click="copyKey(@js($item['plaintext_key']))" class="rounded-full border border-white/10 bg-white/5 px-2 py-1 text-xs text-slate-300 transition hover:border-white/25 hover:bg-white/10">Copy</button>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-slate-200">{{ $item['license']->product->name }}</td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $item['license']->license_model === 'per-device' ? 'bg-blue-400/15 text-blue-300' : ($item['license']->license_model === 'per-user' ? 'bg-emerald-400/15 text-emerald-300' : 'bg-[#A855F7]/15 text-[#D8B4FE]') }}">
                                        {{ ucfirst($item['license']->license_model) }}
                                        @if($item['license']->license_model === 'floating' && $item['license']->max_seats)
                                            ({{ $item['license']->max_seats }} seats)
                                        @endif
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-slate-200">
                                    {{ $item['license']->expiry_date ? $item['license']->expiry_date->format('d/m/Y') : 'Vĩnh viễn' }}
                                </td>
                                <td class="px-4 py-4">
                                    <a href="{{ route('admin.licenses.show', $item['license']) }}" class="rounded-full border border-white/10 bg-white/5 px-3 py-2 text-xs font-semibold text-slate-200 transition hover:border-white/25 hover:bg-white/10">
                                        Chi tiết
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if(count($createdLicenses) > 0)
                <div class="mt-6 rounded-3xl border border-white/10 bg-white/5 p-5">
                    <h4 class="text-sm font-semibold text-white">Thông tin chung</h4>
                    <div class="mt-3 grid gap-2 text-sm text-slate-300 md:grid-cols-2">
                        <p><span class="text-slate-500">Sản phẩm:</span> {{ $createdLicenses[0]['license']->product->name }}</p>
                        <p><span class="text-slate-500">Mô hình:</span> {{ ucfirst($createdLicenses[0]['license']->license_model) }}</p>
                        @if($createdLicenses[0]['license']->license_model === 'floating')
                            <p><span class="text-slate-500">Số seats:</span> {{ $createdLicenses[0]['license']->max_seats }}</p>
                        @endif
                        @if($createdLicenses[0]['license']->expiry_date)
                            <p><span class="text-slate-500">Hết hạn:</span> {{ $createdLicenses[0]['license']->expiry_date->format('d/m/Y') }}</p>
                        @endif
                        @if($createdLicenses[0]['license']->customer_name)
                            <p><span class="text-slate-500">Khách hàng:</span> {{ $createdLicenses[0]['license']->customer_name }}</p>
                        @endif
                        @if($createdLicenses[0]['license']->customer_email)
                            <p><span class="text-slate-500">Email:</span> {{ $createdLicenses[0]['license']->customer_email }}</p>
                        @endif
                    </div>
                </div>
            @endif
        </x-ui.card>
    </div>
@endsection
