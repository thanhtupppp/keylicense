@props(['products'])

@if(method_exists($products, 'count') && $products->count() === 0)
    <x-ui.empty-state
        title="Không có sản phẩm nào"
        description="Không tìm thấy sản phẩm nào phù hợp với bộ lọc hiện tại."
        action-label="Tạo sản phẩm"
        action-href="{{ route('admin.products.create') }}"
    />
@else
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-white/10 text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-[0.2em] text-slate-400">
                    <th class="px-4 py-4">Sản phẩm</th>
                    <th class="px-4 py-4">Mã sản phẩm</th>
                    <th class="px-4 py-4">Trạng thái</th>
                    <th class="px-4 py-4">Số License</th>
                    <th class="px-4 py-4">TTL Token (giờ)</th>
                    <th class="px-4 py-4">Ngày tạo</th>
                    <th class="px-4 py-4 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @foreach($products as $product)
                    <tr class="align-top hover:bg-white/5" @if(request('created') == $product->id) data-product-id="{{ $product->id }}" @endif>
                        <td class="px-4 py-5">
                            <div class="flex items-center gap-3">
                                @if($product->logo_url)
                                    <img class="h-10 w-10 rounded-full object-cover ring-1 ring-white/10" src="{{ $product->logo_url }}" alt="{{ $product->name }}">
                                @endif
                                <div>
                                    <div class="text-sm font-medium text-white">{{ $product->name }}</div>
                                    @if($product->description)
                                        <div class="text-sm text-slate-400">{{ Str::limit($product->description, 50) }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-5 text-sm text-slate-200"><code class="rounded-lg border border-white/10 bg-[#07111f] px-2 py-1 text-xs text-[#F8B803]">{{ $product->slug }}</code></td>
                        <td class="px-4 py-5"><span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $product->status === 'active' ? 'bg-emerald-400/15 text-emerald-300' : 'bg-rose-400/15 text-rose-300' }}">{{ $product->status === 'active' ? 'Hoạt động' : 'Không hoạt động' }}</span></td>
                        <td class="px-4 py-5 text-sm text-slate-200">{{ $product->licenses_count }}</td>
                        <td class="px-4 py-5 text-sm text-slate-200">{{ $product->offline_token_ttl_hours }}</td>
                        <td class="px-4 py-5 text-sm text-slate-400">{{ $product->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-5 text-right text-sm font-medium">
                            <div class="flex flex-wrap justify-end gap-2">
                                <a href="{{ route('admin.products.show', $product) }}" class="rounded-full border border-white/10 bg-white/5 px-3 py-2 text-slate-200 transition hover:bg-white/10">Xem</a>
                                <a href="{{ route('admin.products.edit', $product) }}" class="rounded-full border border-white/10 bg-white/5 px-3 py-2 text-slate-200 transition hover:bg-white/10">Sửa</a>
                                <form method="POST" action="{{ route('admin.products.toggle-status', $product) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-full border border-white/10 bg-white/5 px-3 py-2 text-slate-200 transition hover:bg-white/10">
                                        {{ $product->status === 'active' ? 'Vô hiệu' : 'Kích hoạt' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-full border border-rose-400/20 bg-rose-400/10 px-3 py-2 text-rose-200 transition hover:bg-rose-400/15">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($products->hasPages())
        <div class="border-t border-white/10 px-6 py-4">{{ $products->appends(request()->query())->links() }}</div>
    @endif
@endif
