<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\LicenseKey;
use App\Models\Entitlement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPortalLicenseController extends Controller
{
    public function detail(string $id): View
    {
        return view('admin.licenses', [
            'license' => LicenseKey::query()->with(['entitlement.plan.product', 'activations'])->findOrFail($id),
        ]);
    }

    public function revoke(Request $request, string $id): RedirectResponse
    {
        $license = LicenseKey::query()->findOrFail($id);
        $license->update(['status' => 'revoked']);

        return redirect()->route('admin.portal.licenses.detail', ['id' => $license->id])->with('status', 'Đã thu hồi license.');
    }

    public function suspend(Request $request, string $id): RedirectResponse
    {
        $license = LicenseKey::query()->findOrFail($id);
        $license->update(['status' => 'suspended']);

        return redirect()->route('admin.portal.licenses.detail', ['id' => $license->id])->with('status', 'Đã tạm khóa license.');
    }

    public function extend(Request $request, string $id): RedirectResponse
    {
        $license = LicenseKey::query()->findOrFail($id);
        $license->update(['expires_at' => now()->addMonth()]);

        return redirect()->route('admin.portal.licenses.detail', ['id' => $license->id])->with('status', 'Đã gia hạn license 1 tháng.');
    }

    public function entitlementDetail(string $id): View
    {
        return view('admin.entitlement-detail', [
            'entitlement' => Entitlement::query()->with(['plan.product', 'licenses.activations'])->findOrFail($id),
        ]);
    }

    public function invoiceDetail(string $id): View
    {
        return view('admin.invoice-detail', [
            'invoice' => Invoice::query()->findOrFail($id),
        ]);
    }

    public function voidInvoice(Request $request, string $id): RedirectResponse
    {
        $invoice = Invoice::query()->findOrFail($id);
        $invoice->update(['status' => 'void']);

        return redirect()->route('admin.portal.invoice-detail', ['id' => $invoice->id])
            ->with('status', 'Đã huỷ hóa đơn.');
    }
}
