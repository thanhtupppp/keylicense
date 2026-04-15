<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'full_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $token = Str::random(64);

        $customer = Customer::query()->create([
            'email' => $data['email'],
            'full_name' => $data['full_name'] ?? null,
            'phone' => null,
            'metadata' => [
                'onboarding' => [
                    'step' => 'verify_email',
                    'completed' => false,
                ],
            ],
            'verification_token' => $token,
            'verification_expires_at' => now()->addDay(),
        ]);

        return ApiResponse::success([
            'customer' => $customer->fresh(),
            'verification_token' => $token,
        ], 201);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $customer = Customer::query()->where('verification_token', $data['token'])->firstOrFail();

        $customer->update([
            'email_verified_at' => now(),
            'verification_token' => null,
            'verification_expires_at' => null,
            'metadata' => array_merge($customer->metadata ?? [], [
                'onboarding' => [
                    'step' => 'activate_license',
                    'completed' => false,
                ],
            ]),
        ]);

        return ApiResponse::success(['verified' => true]);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        return ApiResponse::success(['resent' => true]);
    }
}
