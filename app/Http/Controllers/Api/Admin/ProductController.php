<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($builder) use ($search): void {
                $builder->where('code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('category', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return ApiResponse::success([
            'products' => $query->latest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'code' => ['required', 'string', 'max:64', 'unique:products,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);

        $product = Product::query()->create([
            ...$payload,
            'status' => $payload['status'] ?? 'active',
        ]);

        return ApiResponse::success(['product' => $product->toArray()], 201);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::success([
            'product' => Product::query()->findOrFail($id),
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $product = Product::query()->findOrFail($id);

        $payload = $request->validate([
            'code' => ['sometimes', 'string', 'max:64', Rule::unique('products', 'code')->ignore($product->getKey())],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'category' => ['sometimes', 'nullable', 'string', 'max:64'],
            'status' => ['sometimes', 'string', 'max:32'],
        ]);

        $product->fill($payload)->save();

        return ApiResponse::success(['product' => $product->fresh()]);
    }

    public function destroy(string $id): JsonResponse
    {
        $product = Product::query()->findOrFail($id);
        $product->delete();

        return ApiResponse::success(['deleted' => true]);
    }
}
