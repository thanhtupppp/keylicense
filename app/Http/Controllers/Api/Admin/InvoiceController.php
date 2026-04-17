<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingAddress;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Subscription;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class InvoiceController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success([
            'invoices' => Invoice::query()->latest('created_at')->get(['*']),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::success([
            'invoice' => Invoice::query()->findOrFail($id),
        ]);
    }

    public function void(string $id): JsonResponse
    {
        $invoice = Invoice::query()->findOrFail($id);
        $invoice->update(['status' => 'void']);

        return ApiResponse::success(['invoice' => $invoice->fresh()]);
    }

    public function createFromOrder(string $orderId, Request $request): JsonResponse
    {
        $data = $request->validate([
            'subscription_id' => ['sometimes', 'uuid'],
            'customer_id' => ['sometimes', 'uuid'],
            'org_id' => ['sometimes', 'uuid'],
            'invoice_number' => ['sometimes', 'string', 'max:64'],
            'subtotal_cents' => ['sometimes', 'integer', 'min:0'],
            'tax_cents' => ['sometimes', 'integer', 'min:0'],
            'discount_cents' => ['sometimes', 'integer', 'min:0'],
            'total_cents' => ['sometimes', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'max:8'],
            'tax_rate' => ['sometimes', 'numeric', 'min:0'],
            'billing_address' => ['sometimes', 'array'],
            'billing_address.name' => ['sometimes', 'string', 'max:255'],
            'billing_address.line1' => ['sometimes', 'string', 'max:255'],
            'billing_address.line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'billing_address.city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'billing_address.state' => ['sometimes', 'nullable', 'string', 'max:255'],
            'billing_address.postal_code' => ['sometimes', 'nullable', 'string', 'max:32'],
            'billing_address.country' => ['sometimes', 'nullable', 'string', 'size:2'],
            'billing_address.phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'billing_address.tax_id' => ['sometimes', 'nullable', 'string', 'max:64'],
            'billing_address.metadata' => ['sometimes', 'nullable', 'array'],
            'items' => ['sometimes', 'array'],
            'items.*.description' => ['required_with:items', 'string'],
            'items.*.quantity' => ['sometimes', 'integer', 'min:1'],
            'items.*.unit_price_cents' => ['sometimes', 'integer', 'min:0'],
            'items.*.tax_cents' => ['sometimes', 'integer', 'min:0'],
            'items.*.discount_cents' => ['sometimes', 'integer', 'min:0'],
            'items.*.total_cents' => ['sometimes', 'integer', 'min:0'],
            'items.*.metadata' => ['sometimes', 'array'],
        ]);

        $billingAddress = $data['billing_address'] ?? [];
        $subscription = isset($data['subscription_id'])
            ? Subscription::query()->with('entitlement.plan.product')->findOrFail($data['subscription_id'])
            : null;

        $invoice = Invoice::query()->create([
            'order_id' => $orderId,
            'customer_id' => $data['customer_id'] ?? null,
            'org_id' => $data['org_id'] ?? null,
            'invoice_number' => $data['invoice_number'] ?? ('INV-'.now()->format('Y').'-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT)),
            'status' => 'issued',
            'subtotal_cents' => $data['subtotal_cents'] ?? 0,
            'tax_cents' => $data['tax_cents'] ?? 0,
            'discount_cents' => $data['discount_cents'] ?? 0,
            'total_cents' => $data['total_cents'] ?? 0,
            'currency' => $data['currency'] ?? 'USD',
            'tax_rate' => $data['tax_rate'] ?? 0,
            'billing_address' => $billingAddress,
        ]);

        if ($billingAddress !== []) {
            BillingAddress::query()->updateOrCreate(
                [
                    'customer_id' => $invoice->customer_id,
                    'org_id' => $invoice->org_id,
                    'is_default' => true,
                ],
                Arr::only($billingAddress, ['name', 'line1', 'line2', 'city', 'state', 'postal_code', 'country', 'phone', 'tax_id', 'metadata']) + ['is_default' => true]
            );
        }

        $items = $data['items'] ?? [];
        if ($items === [] && $subscription) {
            $items = [[
                'description' => $subscription->entitlement?->plan?->name ?? 'Subscription renewal',
                'quantity' => 1,
                'unit_price_cents' => $data['subtotal_cents'] ?? 0,
                'tax_cents' => $data['tax_cents'] ?? 0,
                'discount_cents' => $data['discount_cents'] ?? 0,
                'total_cents' => $data['total_cents'] ?? ($data['subtotal_cents'] ?? 0) + ($data['tax_cents'] ?? 0) - ($data['discount_cents'] ?? 0),
                'metadata' => [
                    'subscription_id' => $subscription->id,
                    'product_code' => $subscription->entitlement?->plan?->product?->code,
                ],
            ]];
        }

        foreach ($items as $item) {
            InvoiceItem::query()->create([
                'invoice_id' => $invoice->id,
                'description' => $item['description'],
                'quantity' => $item['quantity'] ?? 1,
                'unit_price_cents' => $item['unit_price_cents'] ?? 0,
                'tax_cents' => $item['tax_cents'] ?? 0,
                'discount_cents' => $item['discount_cents'] ?? 0,
                'total_cents' => $item['total_cents'] ?? (($item['unit_price_cents'] ?? 0) * ($item['quantity'] ?? 1)),
                'metadata' => $item['metadata'] ?? [],
            ]);
        }

        return ApiResponse::success([
            'invoice' => $invoice->fresh(),
            'items_count' => count($items),
        ], 201);
    }
}
