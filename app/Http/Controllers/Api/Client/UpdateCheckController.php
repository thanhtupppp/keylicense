<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Entitlement;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateCheckController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'license_key' => ['required', 'string'],
            'product_code' => ['required', 'string'],
            'current_version' => ['required', 'string'],
        ]);

        $entitlement = Entitlement::query()
            ->with('plan.product.versions')
            ->whereHas('plan.product', static function ($query) use ($payload): void {
                $query->where('code', $payload['product_code']);
            })
            ->whereHas('licenses', static function ($query) use ($payload): void {
                $query->where('license_key', hash('sha256', $payload['license_key']));
            })
            ->first();

        if (! $entitlement) {
            return ApiResponse::error('LICENSE_NOT_FOUND', 'License key not found.', 403);
        }

        $latest = $entitlement->plan?->product?->versions?->where('is_latest', true)->sortByDesc('version')->first();
        $required = $entitlement->plan?->product?->versions?->where('is_required', true)->sortByDesc('version')->first();

        return ApiResponse::success([
            'update_available' => (bool) $latest && $latest->version !== $payload['current_version'],
            'required_update' => (bool) $required && version_compare($payload['current_version'], $required->version, '<'),
            'latest_version' => $latest?->version,
            'required_version' => $required?->version,
            'release_notes' => $latest?->release_notes,
        ]);
    }
}
