@extends('layouts.admin', [
    'title' => 'Quản lý Coupons | KeyLicense',
    'description' => 'Quản lý mã giảm giá và trial extensions',
    'sidebarItems' => [
        ['href' => route('admin.portal.dashboard'), 'label' => 'Tổng quan'],
        ['href' => route('admin.portal.sessions'), 'label' => 'Phiên đăng nhập'],
        ['href' => route('admin.portal.api-keys'), 'label' => 'API Keys'],
        ['href' => route('admin.portal.coupons'), 'label' => 'Coupons', 'active' => true],
        ['href' => route('admin.portal.invoices'), 'label' => 'Hóa đơn'],
        ['href' => route('admin.portal.webhooks'), 'label' => 'Webhooks'],
        ['href' => route('admin.portal.metrics'), 'label' => 'Số liệu'],
        ['href' => route('admin.portal.trials'), 'label' => 'Trial'],
        ['href' => route('admin.portal.platform-config'), 'label' => 'Cấu hình'],
    ],
])

@section('content')
    <x-ui.header title="Quản lý Coupons" subtitle="Tạo và quản lý mã giảm giá theo plan/product">
        <x-ui.button :href="route('admin.portal.dashboard')" variant="alt">← Quay lại tổng quan</x-ui.button>
    </x-ui.header>

    @if (session('status'))
        <x-ui.notice type="success">{{ session('status') }}</x-ui.notice>
    @endif

    @php($coupons = $coupons ?? collect())

    <div class="grid cols-3">
        <x-ui.stat value="{{ $coupons->where('is_active', true)->count() }}" label="Đang hoạt động" />
        <x-ui.stat value="{{ $coupons->where('redemptions_count', '>', 0)->count() }}" label="Đã dùng" />
        <x-ui.stat value="{{ $coupons->filter(fn ($coupon) => optional($coupon->ends_at)?->diffInDays(now(), false) > -7)->count() }}" label="Sắp hết hạn" />
    </div>

    <div class="grid cols-2" style="margin-top:16px;">
        <section class="card stack">
            <x-ui.section-header title="Danh sách coupons" subtitle="Mã giảm giá dùng cho thanh toán, trial extension và free plan." />
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Mã</th>
                            <th>Loại</th>
                            <th>Giá trị</th>
                            <th>Phạm vi</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($coupons as $coupon)
                            <tr>
                                <td class="mono">{{ $coupon->code }}</td>
                                <td>{{ $coupon->discount_type }}</td>
                                <td>{{ $coupon->discount_value }}{{ $coupon->currency ? ' ' . $coupon->currency : ($coupon->discount_type === 'percent' ? '%' : '') }}</td>
                                <td>{{ $coupon->scope ?? 'any' }}</td>
                                <td>
                                    @if ($coupon->is_active)
                                        <span class="badge ok">Hoạt động</span>
                                    @else
                                        <span class="badge current">Tắt</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($coupon->is_active)
                                        <form method="POST" action="{{ route('admin.portal.coupons.deactivate', ['id' => $coupon->id]) }}">
                                            @csrf
                                            <x-ui.button type="submit" variant="danger">Tắt</x-ui.button>
                                        </form>
                                    @else
                                        <span class="muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-ui.table-empty colspan="6">Chưa có coupon nào.</x-ui.table-empty>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="stack">
            <section class="card">
                <x-ui.section-header title="Tạo coupon" subtitle="Dùng cho chiến dịch, trial extension hoặc free plan." />
                <form method="POST" action="{{ route('admin.portal.coupons.store') }}" class="grid" style="gap:12px;">
                    @csrf
                    <x-ui.input label="Mã coupon" name="code" placeholder="LAUNCH50" required />
                    <x-ui.input label="Tên coupon" name="name" placeholder="Launch campaign 50%" required />
                    <x-ui.input label="Loại giảm giá" name="discount_type" placeholder="percent" required />
                    <x-ui.input label="Giá trị" name="discount_value" placeholder="50" required />
                    <x-ui.input label="Phạm vi" name="scope" placeholder="any" />
                    <x-ui.input label="Currency" name="currency" placeholder="VND" />
                    <x-ui.input label="Max redemptions" name="max_redemptions" placeholder="100" />
                    <x-ui.button type="submit">Tạo coupon</x-ui.button>
                </form>
            </section>

            <section class="card">
                <x-ui.section-header title="Gợi ý logic" subtitle="Coupons phải gắn với plan/product hoặc áp dụng toàn cục." />
                <ul style="margin:0;padding-left:18px;line-height:1.8;color:#dbeafe;">
                    <li>Kiểm tra số lượt dùng trước khi áp dụng.</li>
                    <li>Hỗ trợ trial extension và fixed amount.</li>
                    <li>Lưu usage theo customer và order.</li>
                </ul>
            </section>
        </aside>
    </div>
@endsection
