<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function index(Request $request): View
    {
        $query = Product::withCount('licenses');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search') ?? '';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.products.index', compact('products'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): View
    {
        return view('admin.products.create');
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Generate unique API key
        $validated['api_key'] = $this->generateUniqueApiKey();

        // Set default status if not provided
        $validated['status'] ??= 'active';

        $product = Product::create($validated);

        AuditLogger::productCreated($product, [
            'actor_name' => $request->user()?->email,
            'severity' => 'info',
            'result' => 'success',
        ]);

        return redirect()
            ->route('admin.products.index', [
                'created' => $product->id,
            ])
            ->with('success', "Đã tạo sản phẩm: {$product->name}");
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): View
    {
        $product->loadCount('licenses');

        return view('admin.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product): View
    {
        return view('admin.products.edit', compact('product'));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();

        $product->update($validated);

        AuditLogger::productUpdated($product, [
            'actor_name' => $request->user()?->email,
            'changes' => $product->getChanges(),
            'severity' => 'info',
            'result' => 'success',
        ]);

        return redirect()
            ->route('admin.products.show', $product)
            ->with('success', 'Sản phẩm đã được cập nhật thành công.');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        // Check if product has associated licenses
        $licenseCount = $product->licenses()->count();

        if ($licenseCount > 0) {
            return redirect()
                ->route('admin.products.index')
                ->with('error', "Không thể xóa sản phẩm này vì có {$licenseCount} license đang liên kết.");
        }

        // Soft delete the product
        $product->delete();

        AuditLogger::productDeleted($product, [
            'actor_name' => request()->user()?->email,
            'severity' => 'warning',
            'result' => 'success',
        ]);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Sản phẩm đã được xóa thành công.');
    }

    /**
     * Toggle the status of the specified product.
     */
    public function toggleStatus(Product $product): RedirectResponse
    {
        $newStatus = $product->status === 'active' ? 'inactive' : 'active';
        $oldStatus = $product->status;

        $product->update(['status' => $newStatus]);

        AuditLogger::log('product.status_changed', $product, [
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'actor_name' => request()->user()?->email,
            'severity' => 'info',
            'result' => 'success',
        ]);

        $statusText = $newStatus === 'active' ? 'kích hoạt' : 'vô hiệu hóa';

        return redirect()
            ->back()
            ->with('success', "Sản phẩm đã được {$statusText} thành công.");
    }

    /**
     * Generate a unique API key for the product.
     */
    private function generateUniqueApiKey(): string
    {
        do {
            $apiKey = 'pk_' . Str::random(32);
        } while (Product::where('api_key', $apiKey)->exists());

        return $apiKey;
    }
}
