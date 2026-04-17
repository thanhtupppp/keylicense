<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPortalCouponController extends Controller
{
    public function index(): View
    {
        return view('admin.coupons', [
            'coupons' => Coupon::query()->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:128'],
            'name' => ['required', 'string', 'max:255'],
            'discount_type' => ['required', 'string', 'max:32'],
            'discount_value' => ['required', 'integer', 'min:0'],
            'currency' => ['sometimes', 'nullable', 'string', 'max:8'],
            'max_redemptions' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        Coupon::query()->create([
            ...$data,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return redirect()->route('admin.portal.coupons')->with('status', 'Đã tạo coupon mới.');
    }

    public function deactivate(string $id): RedirectResponse
    {
        $coupon = Coupon::query()->findOrFail($id);
        $coupon->update(['is_active' => false]);

        return redirect()->route('admin.portal.coupons')->with('status', 'Đã tắt coupon.');
    }
}
