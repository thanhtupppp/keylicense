<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\ApiKeyAuditLog;
use App\Models\AdminUser;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    public function issue(Request $request): JsonResponse
    {
        $actor = $this->admin($request);

        if (! $actor) {
            return ApiResponse::error('UNAUTHORIZED', 'Admin authentication required.', 401);
        }

        if ($actor->role !== 'super_admin') {
            return ApiResponse::error('FORBIDDEN', 'Only super admin can manage API keys.', 403);
        }

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'scope' => ['nullable', 'string', 'max:32'],
        ]);

        $plainKey = Str::random(64);

        $apiKey = ApiKey::query()->create([
            'name' => $payload['name'],
            'api_key' => hash('sha256', $plainKey),
            'scope' => $payload['scope'] ?? 'client',
            'is_active' => true,
        ]);

        ApiKeyAuditLog::query()->create([
            'api_key_id' => $apiKey->id,
            'action' => 'issue',
            'actor_admin_user_id' => $actor->id,
            'metadata' => [
                'scope' => $apiKey->scope,
                'name' => $apiKey->name,
            ],
            'created_at' => now(),
        ]);

        return ApiResponse::success([
            'api_key' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'scope' => $apiKey->scope,
                'is_active' => $apiKey->is_active,
                'plain_text_key' => $plainKey,
            ],
        ], 201);
    }

    public function rotate(Request $request, string $id): JsonResponse
    {
        $actor = $this->admin($request);

        if (! $actor) {
            return ApiResponse::error('UNAUTHORIZED', 'Admin authentication required.', 401);
        }

        if ($actor->role !== 'super_admin') {
            return ApiResponse::error('FORBIDDEN', 'Only super admin can manage API keys.', 403);
        }

        $apiKey = ApiKey::query()->find($id);

        if (! $apiKey) {
            return ApiResponse::error('API_KEY_NOT_FOUND', 'API key not found.', 404);
        }

        $plainKey = Str::random(64);
        $apiKey->forceFill([
            'api_key' => hash('sha256', $plainKey),
            'is_active' => true,
        ])->save();

        ApiKeyAuditLog::query()->create([
            'api_key_id' => $apiKey->id,
            'action' => 'rotate',
            'actor_admin_user_id' => $actor->id,
            'metadata' => [
                'scope' => $apiKey->scope,
                'name' => $apiKey->name,
            ],
            'created_at' => now(),
        ]);

        return ApiResponse::success([
            'api_key' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'scope' => $apiKey->scope,
                'is_active' => $apiKey->is_active,
                'plain_text_key' => $plainKey,
            ],
        ]);
    }

    public function revoke(Request $request, string $id): JsonResponse
    {
        $actor = $this->admin($request);

        if (! $actor) {
            return ApiResponse::error('UNAUTHORIZED', 'Admin authentication required.', 401);
        }

        if ($actor->role !== 'super_admin') {
            return ApiResponse::error('FORBIDDEN', 'Only super admin can manage API keys.', 403);
        }

        $apiKey = ApiKey::query()->find($id);

        if (! $apiKey) {
            return ApiResponse::error('API_KEY_NOT_FOUND', 'API key not found.', 404);
        }

        $apiKey->forceFill(['is_active' => false])->save();

        ApiKeyAuditLog::query()->create([
            'api_key_id' => $apiKey->id,
            'action' => 'revoke',
            'actor_admin_user_id' => $actor->id,
            'metadata' => [
                'scope' => $apiKey->scope,
                'name' => $apiKey->name,
            ],
            'created_at' => now(),
        ]);

        return ApiResponse::success([
            'api_key' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'scope' => $apiKey->scope,
                'is_active' => $apiKey->is_active,
            ],
        ]);
    }

    private function admin(Request $request): ?AdminUser
    {
        $admin = $request->attributes->get('admin_user') ?? $request->user('admin') ?? $request->user();

        return $admin instanceof AdminUser ? $admin : null;
    }
}
