<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success([
            'invoices' => Invoice::query()->latest()->get(),
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
        ]);

        $invoice = Invoice::query()->create([
            'order_id' => $orderId,
            'customer_id' => $data['customer_id'] ?? null,
            'org_id' => $data['org_id'] ?? null,
            'invoice_number' => $data['invoice_number'] ?? ('INV-'.now()->format('Y').'-00001'),
            'status' => 'issued',
            'subtotal_cents' => $data['subtotal_cents'] ?? 0,
            'tax_cents' => $data['tax_cents'] ?? 0,
            'discount_cents' => $data['discount_cents'] ?? 0,
            'total_cents' => $data['total_cents'] ?? 0,
            'currency' => $data['currency'] ?? 'USD',
            'tax_rate' => $data['tax_rate'] ?? 0,
            'billing_address' => $data['billing_address'] ?? [],
        ]);

        return ApiResponse::success(['invoice' => $invoice], 201);
    }
}
