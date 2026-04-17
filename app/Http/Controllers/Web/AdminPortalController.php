<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Entitlement;
use App\Models\Invoice;
use App\Models\LicenseKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPortalController extends Controller
{
    public function licenses(Request $request): View
    {
        $query = LicenseKey::query()->with(['entitlement.plan.product', 'activations']);

        if ($term = trim((string) $request->string('q'))) {
            $query->where(function ($subQuery) use ($term): void {
                $subQuery->where('license_key', 'like', "%{$term}%")
                    ->orWhere('key_display', 'like', "%{$term}%");
            });
        }

        if ($status = trim((string) $request->string('status'))) {
            $query->where('status', $status);
        }

        return view('admin.licenses-index', [
            'licenses' => $query->latest()->paginate(10)->withQueryString(),
        ]);
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

    public function webhookDelivery(string $id): View
    {
        return view('admin.webhook-delivery', ['deliveryId' => $id]);
    }
}
