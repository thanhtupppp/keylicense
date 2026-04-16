<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reseller;
use App\Models\ResellerKeyAssignment;
use App\Models\ResellerKeyPool;
use App\Models\ResellerUser;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ResellerController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success([
            'resellers' => Reseller::query()->withCount(['keyPools', 'users'])->latest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:128', 'unique:resellers,slug'],
            'contact_email' => ['required', 'email', 'max:255'],
            'commission_type' => ['sometimes', Rule::in(['percent', 'fixed_per_key'])],
            'commission_value' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::in(['active', 'suspended', 'terminated'])],
            'metadata' => ['sometimes', 'array'],
        ]);

        $reseller = Reseller::query()->create([
            ...$payload,
            'slug' => $this->resolveSlug($payload),
            'commission_type' => $payload['commission_type'] ?? 'percent',
            'commission_value' => $payload['commission_value'] ?? 0,
            'status' => $payload['status'] ?? 'active',
        ]);

        return ApiResponse::success([
            'reseller' => $reseller,
        ], 201);
    }

    public function createPool(Request $request, string $resellerId): JsonResponse
    {
        $payload = $request->validate([
            'plan_id' => ['required', 'uuid', 'exists:plans,id'],
            'total_keys' => ['required', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $pool = ResellerKeyPool::query()->create([
            'reseller_id' => $resellerId,
            'plan_id' => $payload['plan_id'],
            'total_keys' => $payload['total_keys'],
            'used_keys' => 0,
            'expires_at' => $payload['expires_at'] ?? null,
            'created_by' => null,
        ]);

        return ApiResponse::success([
            'pool' => $pool,
        ], 201);
    }

    public function pools(string $resellerId): JsonResponse
    {
        return ApiResponse::success([
            'reseller_id' => $resellerId,
            'pools' => ResellerKeyPool::query()->where('reseller_id', $resellerId)->withCount('assignments')->latest()->get(),
        ]);
    }

    public function poolKeys(string $resellerId, string $poolId): JsonResponse
    {
        Reseller::query()->findOrFail($resellerId);
        ResellerKeyPool::query()->where('reseller_id', $resellerId)->findOrFail($poolId);

        return ApiResponse::success([
            'reseller_id' => $resellerId,
            'pool_id' => $poolId,
            'assignments' => ResellerKeyAssignment::query()->where('pool_id', $poolId)->latest('assigned_at')->get(),
        ]);
    }

    public function assignPool(Request $request, string $resellerId, string $poolId): JsonResponse
    {
        $payload = $request->validate([
            'license_key_id' => ['required', 'uuid', 'exists:license_keys,id'],
            'assigned_to_email' => ['nullable', 'email', 'max:255'],
        ]);

        $pool = ResellerKeyPool::query()->where('reseller_id', $resellerId)->findOrFail($poolId);

        $assignment = ResellerKeyAssignment::query()->create([
            'pool_id' => $pool->id,
            'license_key_id' => $payload['license_key_id'],
            'assigned_to_email' => $payload['assigned_to_email'] ?? null,
            'assigned_at' => now(),
        ]);

        $pool->increment('used_keys');

        return ApiResponse::success([
            'assignment' => $assignment,
            'remaining_keys' => max(0, $pool->total_keys - ($pool->used_keys + 1)),
        ], 201);
    }

    public function reports(string $resellerId): JsonResponse
    {
        $reseller = Reseller::query()->with('keyPools.assignments')->findOrFail($resellerId);

        return ApiResponse::success([
            'reseller_id' => $reseller->id,
            'pools_count' => $reseller->keyPools()->count(),
            'assigned_keys_count' => $reseller->keyPools->sum(fn ($pool) => $pool->assignments->count()),
            'used_keys_total' => $reseller->keyPools->sum('used_keys'),
        ]);
    }

    public function auth(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = ResellerUser::query()->where('email', $payload['email'])->first();

        if (! $user || ! password_verify($payload['password'], (string) $user->password_hash)) {
            return ApiResponse::error('RESELLER_AUTH_FAILED', 'Invalid reseller credentials.', 403);
        }

        return ApiResponse::success([
            'token' => Str::random(64),
            'reseller_user' => $user,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveSlug(array $payload): string
    {
        return $payload['slug'] ?? Str::slug($payload['name']).'-'.Str::lower(Str::random(6));
    }
}
