<?php

namespace App\Services\Licensing;

use App\Models\Activation;
use App\Models\LicenseKey;
use App\Services\Sdk\Dto\ChallengeResult;
use Illuminate\Support\Str;

class OfflineChallengeService
{
    public function request(array $data): ChallengeResult
    {
        $license = LicenseKey::query()
            ->where('license_key', hash('sha256', $data['license_key']))
            ->first();

        if (! $license) {
            return new ChallengeResult(false, null, null, ['message' => 'License key not found.']);
        }

        $activation = Activation::query()->firstOrCreate(
            [
                'license_id' => $license->id,
                'product_code' => $data['product_code'],
                'domain' => $data['domain'] ?? null,
                'environment' => $data['environment'] ?? null,
            ],
            [
                'activation_code' => (string) Str::uuid(),
                'status' => 'pending',
                'activated_at' => now(),
                'last_validated_at' => now(),
            ]
        );

        $challenge = (string) Str::uuid();

        return new ChallengeResult(true, $activation->activation_code, now()->addHours(24)->toISOString(), [
            'status' => 'challenge_issued',
            'message' => 'Offline challenge issued.',
            'license_id' => $license->id,
            'activation_id' => $activation->activation_code,
            'challenge' => $challenge,
            'issued_at' => now()->toISOString(),
        ]);
    }

    public function confirm(array $data): ChallengeResult
    {
        $license = LicenseKey::query()
            ->where('license_key', hash('sha256', $data['license_key']))
            ->first();

        if (! $license) {
            return new ChallengeResult(false, null, null, ['message' => 'License key not found.']);
        }

        $activation = Activation::query()
            ->where('license_id', $license->id)
            ->where('activation_code', $data['activation_id'])
            ->first();

        if (! $activation) {
            return new ChallengeResult(false, null, null, ['message' => 'Activation record not found.']);
        }

        $activation->forceFill([
            'status' => 'active',
            'last_validated_at' => now(),
        ])->save();

        return new ChallengeResult(true, $activation->activation_code, null, [
            'status' => 'challenge_confirmed',
            'message' => 'Offline challenge confirmed.',
            'license_id' => $license->id,
            'activation_id' => $activation->activation_code,
            'confirmed_at' => now()->toISOString(),
        ]);
    }
}
