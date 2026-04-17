<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVersion;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductVersionController extends Controller
{
    public function index(string $productId): JsonResponse
    {
        return ApiResponse::success([
            'versions' => ProductVersion::query()->where('product_id', $productId)->latest()->get(),
        ]);
    }

    public function store(Request $request, string $productId): JsonResponse
    {
        $payload = $request->validate([
            'version' => ['required', 'string', 'max:64'],
            'build_number' => ['nullable', 'string', 'max:64'],
            'release_notes' => ['nullable', 'string'],
            'is_latest' => ['nullable', 'boolean'],
            'is_required' => ['nullable', 'boolean'],
        ]);

        $version = ProductVersion::query()->create([
            'product_id' => $productId,
            ...$payload,
            'is_latest' => (bool) ($payload['is_latest'] ?? false),
            'is_required' => (bool) ($payload['is_required'] ?? false),
        ]);

        return ApiResponse::success(['version' => $version], 201);
    }
}
