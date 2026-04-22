<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\Product;
use App\Services\LicenseKeyGenerator;
use App\Services\LicenseService;
use App\Services\ActivationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LicenseController extends Controller
{
    public function __construct(
        private LicenseKeyGenerator $licenseKeyGenerator,
        private LicenseService $licenseService,
        private ActivationService $activationService
    ) {}

    /**
     * Display a listing of licenses with search and filter functionality.
     */
    public function index(Request $request): View
    {
        $query = License::with(['product', 'activations'])
            ->withCount(['activations' => function ($query) {
                $query->where('is_active', true);
            }]);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('key_last4', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($productQuery) use ($search) {
                        $productQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('slug', 'like', "%{$search}%");
                    });
            });
        }

        // Product filter
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->get('product_id'));
        }

        // License model filter
        if ($request->filled('license_model')) {
            $query->where('license_model', $request->get('license_model'));
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        $licenses = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get products for filter dropdown
        $products = Product::orderBy('name')->get();

        return view('admin.licenses.index', compact('licenses', 'products'));
    }

    /**
     * Show the form for creating new licenses (batch creation).
     */
    public function create(): View
    {
        $products = Product::where('status', 'active')->orderBy('name')->get();

        return view('admin.licenses.create', compact('products'));
    }

    /**
     * Store newly created licenses in storage (batch creation).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'license_model' => 'required|in:per-device,per-user,floating',
            'quantity' => 'required|integer|min:1|max:100',
            'max_seats' => 'nullable|integer|min:1|required_if:license_model,floating',
            'expiry_date' => 'nullable|date|after:today',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $product = Product::findOrFail($request->product_id);
        $quantity = $request->quantity;

        try {
            DB::beginTransaction();

            // Generate license keys
            $licenseKeys = $this->licenseKeyGenerator->generateBatch($quantity);
            $createdLicenses = [];

            foreach ($licenseKeys as $key) {
                $license = License::create([
                    'product_id' => $product->id,
                    'key_hash' => $this->licenseKeyGenerator->hashKey($key),
                    'key_last4' => $this->licenseKeyGenerator->getKeyLast4($key),
                    'license_model' => $request->license_model,
                    'status' => 'inactive',
                    'max_seats' => $request->license_model === 'floating' ? $request->max_seats : null,
                    'expiry_date' => $request->expiry_date ? Carbon::parse($request->expiry_date) : null,
                    'customer_name' => $request->customer_name,
                    'customer_email' => $request->customer_email,
                    'notes' => $request->notes,
                ]);

                $createdLicenses[] = [
                    'license' => $license,
                    'plaintext_key' => $key,
                ];
            }

            DB::commit();

            // Store the plaintext keys in session to display them once
            session()->flash('created_licenses', $createdLicenses);

            return redirect()
                ->route('admin.licenses.batch-created')
                ->with('success', "Đã tạo thành công {$quantity} license key.");
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi tạo license: ' . $e->getMessage());
        }
    }

    /**
     * Display the batch creation results with plaintext keys (shown only once).
     */
    public function batchCreated(): View
    {
        $createdLicenses = session('created_licenses');

        if (!$createdLicenses) {
            return redirect()
                ->route('admin.licenses.index')
                ->with('error', 'Không tìm thấy thông tin license vừa tạo.');
        }

        return view('admin.licenses.batch-created', compact('createdLicenses'));
    }

    /**
     * Display the specified license with detailed information.
     */
    public function show(License $license): View
    {
        $license->load([
            'product',
            'activations' => function ($query) {
                $query->orderBy('activated_at', 'desc');
            },
            'floatingSeats' => function ($query) {
                $query->orderBy('last_heartbeat_at', 'desc');
            }
        ]);

        return view('admin.licenses.show', compact('license'));
    }

    /**
     * Export licenses to CSV.
     */
    public function export(Request $request): Response
    {
        $query = License::with('product');

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('key_last4', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($productQuery) use ($search) {
                        $productQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('slug', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->get('product_id'));
        }

        if ($request->filled('license_model')) {
            $query->where('license_model', $request->get('license_model'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        $licenses = $query->orderBy('created_at', 'desc')->get();

        // Generate CSV content
        $csvContent = "Key,Product,Model,Status,Expiry Date,Created At\n";

        foreach ($licenses as $license) {
            $maskedKey = '****-****-****-' . $license->key_last4;
            $expiryDate = $license->expiry_date ? $license->expiry_date->format('Y-m-d') : 'Never';
            $createdAt = $license->created_at->format('Y-m-d H:i:s');

            $csvContent .= sprintf(
                "%s,%s,%s,%s,%s,%s\n",
                $maskedKey,
                $license->product->name,
                $license->license_model,
                $license->status,
                $expiryDate,
                $createdAt
            );
        }

        $filename = 'licenses_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Revoke a license.
     */
    public function revoke(License $license): RedirectResponse
    {
        try {
            $this->licenseService->revoke($license);

            return redirect()
                ->back()
                ->with('success', 'License đã được thu hồi thành công.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Không thể thu hồi license: ' . $e->getMessage());
        }
    }

    /**
     * Suspend a license.
     */
    public function suspend(License $license): RedirectResponse
    {
        try {
            $this->licenseService->suspend($license);

            return redirect()
                ->back()
                ->with('success', 'License đã được tạm khóa thành công.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Không thể tạm khóa license: ' . $e->getMessage());
        }
    }

    /**
     * Restore a license.
     */
    public function restore(License $license): RedirectResponse
    {
        try {
            $this->licenseService->restore($license);

            return redirect()
                ->back()
                ->with('success', 'License đã được khôi phục thành công.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Không thể khôi phục license: ' . $e->getMessage());
        }
    }

    /**
     * Renew a license (update expiry date).
     */
    public function renew(Request $request, License $license): RedirectResponse
    {
        $request->validate([
            'expiry_date' => 'required|date|after:today',
        ]);

        try {
            $newExpiryDate = Carbon::parse($request->expiry_date);
            $this->licenseService->renew($license, $newExpiryDate);

            return redirect()
                ->back()
                ->with('success', 'License đã được gia hạn thành công.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Không thể gia hạn license: ' . $e->getMessage());
        }
    }

    /**
     * Un-revoke a license.
     */
    public function unrevoke(License $license): RedirectResponse
    {
        try {
            $this->licenseService->unrevoke($license);

            return redirect()
                ->back()
                ->with('success', 'License đã được phục hồi thành công.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Không thể phục hồi license: ' . $e->getMessage());
        }
    }

    /**
     * Revoke a specific activation.
     */
    public function revokeActivation(License $license, Request $request): RedirectResponse
    {
        $request->validate([
            'activation_id' => 'required|exists:activations,id',
        ]);

        try {
            $activation = $license->activations()->findOrFail($request->activation_id);

            if ($license->license_model === 'floating') {
                // For floating licenses, just remove the seat
                $license->floatingSeats()
                    ->where('activation_id', $activation->id)
                    ->delete();

                return redirect()
                    ->back()
                    ->with('success', 'Seat đã được giải phóng thành công.');
            } else {
                // For per-device/per-user, deactivate and reset license to inactive
                $activation->update(['is_active' => false]);

                // Reset license to inactive to allow reactivation on different device/user
                $license->status = 'inactive';
                $license->save();

                return redirect()
                    ->back()
                    ->with('success', 'Activation đã được thu hồi thành công. License có thể được kích hoạt lại trên thiết bị/tài khoản khác.');
            }
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Không thể thu hồi activation: ' . $e->getMessage());
        }
    }
}
