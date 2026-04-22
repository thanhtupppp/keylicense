@props(['licenses'])

@if(method_exists($licenses, 'count') && $licenses->count() === 0)
    <x-ui.empty-state
        title="Không có license nào"
        description="Không tìm thấy license nào phù hợp với bộ lọc hiện tại."
    />
@else
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-white/10 text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-[0.2em] text-slate-400">
                    <th class="px-4 py-4">License Key</th>
                    <th class="px-4 py-4">Sản phẩm</th>
                    <th class="px-4 py-4">Mô hình</th>
                    <th class="px-4 py-4">Trạng thái</th>
                    <th class="px-4 py-4">Activations</th>
                    <th class="px-4 py-4">Hết hạn</th>
                    <th class="px-4 py-4">Khách hàng</th>
                    <th class="px-4 py-4">Ngày tạo</th>
                    <th class="px-4 py-4">Hành động</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @foreach($licenses as $license)
                    <tr class="align-top hover:bg-white/5">
                        <td class="px-4 py-5 font-mono text-xs text-[#F8B803]">****-****-****-{{ $license->key_last4 }}</td>
                        <td class="px-4 py-5 text-slate-200">{{ $license->product->name }}</td>
                        <td class="px-4 py-5">
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $license->license_model === 'per-device' ? 'bg-blue-400/15 text-blue-300' : ($license->license_model === 'per-user' ? 'bg-emerald-400/15 text-emerald-300' : 'bg-[#A855F7]/15 text-[#D8B4FE]') }}">
                                {{ ucfirst($license->license_model) }}
                                @if($license->license_model === 'floating' && $license->max_seats)
                                    ({{ $license->max_seats }} seats)
                                @endif
                            </span>
                        </td>
                        <td class="px-4 py-5">
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $license->status === 'active' ? 'bg-emerald-400/15 text-emerald-300' : ($license->status === 'inactive' ? 'bg-slate-400/15 text-slate-300' : ($license->status === 'expired' ? 'bg-amber-400/15 text-amber-300' : ($license->status === 'suspended' ? 'bg-orange-400/15 text-orange-300' : 'bg-rose-400/15 text-rose-300'))) }}">
                                {{ ucfirst($license->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-5 text-slate-200">{{ $license->activations_count }}</td>
                        <td class="px-4 py-5 text-slate-200">
                            @if($license->expiry_date)
                                {{ $license->expiry_date->format('d/m/Y') }}
                                @if($license->expiry_date->isPast())
                                    <span class="text-rose-300">(Đã hết hạn)</span>
                                @endif
                            @else
                                <span class="text-slate-500">Vĩnh viễn</span>
                            @endif
                        </td>
                        <td class="px-4 py-5 text-slate-200">
                            @if($license->customer_name)
                                <div>{{ $license->customer_name }}</div>
                                @if($license->customer_email)
                                    <div class="text-xs text-slate-500">{{ $license->customer_email }}</div>
                                @endif
                            @else
                                <span class="text-slate-500">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-5 text-slate-400">{{ $license->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-5">
                            <a href="{{ route('admin.licenses.show', $license) }}" class="rounded-full border border-white/10 bg-white/5 px-3 py-2 text-xs font-medium text-slate-200 transition hover:bg-white/10">Chi tiết</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($licenses->hasPages())
        <div class="border-t border-white/10 px-6 py-4">{{ $licenses->appends(request()->query())->links() }}</div>
    @endif
@endif
