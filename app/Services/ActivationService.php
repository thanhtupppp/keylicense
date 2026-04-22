<?php

namespace App\Services;

use App\Exceptions\SeatsExhaustedException;
use App\Support\AuditLogger;
use App\Models\Activation;
use App\Models\FloatingSeat;
use App\Models\License;
use App\States\License\ActiveState;
use App\States\License\ExpiredState;
use App\States\License\InactiveState;
use App\States\License\RevokedState;
use App\States\License\SuspendedState;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ActivationService
{
    public function __construct()
    {
    }
    /**
     * Activate a license for a device or user.
     *
     * Handles three license models:
     * - per-device: unique constraint on (license_id, device_fp_hash)
     * - per-user: unique constraint on (license_id, user_identifier)
     * - floating: allocates a seat with lockForUpdate() to prevent race conditions
     *
     * @param License $license
     * @param string $fingerprint The plaintext device fingerprint
     * @param string|null $userIdentifier The user identifier (for per-user licenses)
     * @param string $ip The client IP address
     * @return Activation
     * @throws SeatsExhaustedException
     */
    public function activate(License $license, string $fingerprint, ?string $userIdentifier, string $ip): Activation
    {
        // Hash the device fingerprint
        $deviceFpHash = hash('sha256', $fingerprint);

        // Check if activation already exists (for idempotency)
        $existingActivation = null;
        if ($license->license_model === 'per-device') {
            $existingActivation = Activation::where('license_id', $license->id)
                ->where('device_fp_hash', $deviceFpHash)
                ->where('type', 'per-device')
                ->first();
        } elseif ($license->license_model === 'per-user') {
            $existingActivation = Activation::where('license_id', $license->id)
                ->where('type', 'per-user')
                ->first();
        } elseif ($license->license_model === 'floating') {
            $existingActivation = Activation::where('license_id', $license->id)
                ->where('device_fp_hash', $deviceFpHash)
                ->where('type', 'floating')
                ->first();
        }

        // If activation already exists, return it without logging (idempotent)
        if ($existingActivation) {
            return $existingActivation;
        }

        // Perform new activation in transaction
        $activation = DB::transaction(function () use ($license, $fingerprint, $userIdentifier, $ip, $deviceFpHash) {
            // Validate license state before activation
            $this->validateLicenseStateForActivation($license);

            // Handle per-device license
            if ($license->license_model === 'per-device') {
                $activation = $this->activatePerDevice($license, $deviceFpHash);
            }
            // Handle per-user license
            elseif ($license->license_model === 'per-user') {
                $activation = $this->activatePerUser($license, $userIdentifier);
            }
            // Handle floating license
            elseif ($license->license_model === 'floating') {
                $activation = $this->activateFloating($license, $deviceFpHash);
            } else {
                throw new \Exception('Invalid license model');
            }

            // Log audit event for new activation
            $this->logActivationSuccess($activation, $license, $fingerprint, $userIdentifier, $ip);

            return $activation;
        });

        return $activation;
    }

    /**
     * Log a successful activation to the audit log.
     *
     * @param Activation $activation
     * @param License $license
     * @param string $fingerprint
     * @param string|null $userIdentifier
     * @param string $ip
     * @return void
     */
    private function logActivationSuccess(
        Activation $activation,
        License $license,
        string $fingerprint,
        ?string $userIdentifier,
        string $ip
    ): void {
        AuditLogger::log('activation.success', null, [
            'subject_type' => 'license',
            'subject_id' => $license->id,
            'license_id' => $license->id,
            'key_hash' => $license->key_hash,
            'activation_id' => $activation->id,
            'license_model' => $license->license_model,
            'ip' => $ip,
            'device_fp_hash' => $license->license_model === 'per-device' || $license->license_model === 'floating'
                ? hash('sha256', $fingerprint)
                : null,
            'user_identifier' => $license->license_model === 'per-user' ? $userIdentifier : null,
            'result' => 'success',
            'severity' => 'info',
        ]);
    }

    /**
     * Validate that the license is in a valid state for activation.
     *
     * @param License $license
     * @throws \Exception
     */
    private function validateLicenseStateForActivation(License $license): void
    {
        if ($license->status instanceof RevokedState) {
            throw new \Exception('LICENSE_REVOKED');
        }
        if ($license->status instanceof SuspendedState) {
            throw new \Exception('LICENSE_SUSPENDED');
        }
        if ($license->status instanceof ExpiredState) {
            throw new \Exception('LICENSE_EXPIRED');
        }
    }

    /**
     * Activate a per-device license.
     *
     * @param License $license
     * @param string $deviceFpHash
     * @return Activation
     * @throws \Exception
     */
    private function activatePerDevice(License $license, string $deviceFpHash): Activation
    {
        // Check if there's an existing activation for this license
        $existingActivation = Activation::where('license_id', $license->id)
            ->where('type', 'per-device')
            ->first();

        // If there's an existing activation with a different device fingerprint, reject
        if ($existingActivation && $existingActivation->device_fp_hash !== $deviceFpHash) {
            throw new \Exception('DEVICE_MISMATCH');
        }

        try {
            $activation = Activation::create([
                'license_id' => $license->id,
                'device_fp_hash' => $deviceFpHash,
                'user_identifier' => null,
                'type' => 'per-device',
                'activated_at' => Carbon::now(),
                'is_active' => true,
            ]);
        } catch (QueryException $e) {
            // Handle unique constraint violation idempotently
            if ($this->isUniqueConstraintViolation($e)) {
                $activation = Activation::where('license_id', $license->id)
                    ->where('device_fp_hash', $deviceFpHash)
                    ->first();
            } else {
                throw $e;
            }
        }

        // Activate license if not already active
        if (!($license->status instanceof ActiveState)) {
            $license->status = new ActiveState($license);
            $license->save();
        }

        return $activation;
    }

    /**
     * Activate a per-user license.
     *
     * @param License $license
     * @param string|null $userIdentifier
     * @return Activation
     * @throws \Exception
     */
    private function activatePerUser(License $license, ?string $userIdentifier): Activation
    {
        // Check if there's an existing activation for this license
        $existingActivation = Activation::where('license_id', $license->id)
            ->where('type', 'per-user')
            ->first();

        // If there's an existing activation with a different user identifier, reject
        if ($existingActivation && $existingActivation->user_identifier !== $userIdentifier) {
            throw new \Exception('USER_MISMATCH');
        }

        try {
            $activation = Activation::create([
                'license_id' => $license->id,
                'device_fp_hash' => null,
                'user_identifier' => $userIdentifier,
                'type' => 'per-user',
                'activated_at' => Carbon::now(),
                'is_active' => true,
            ]);
        } catch (QueryException $e) {
            // Handle unique constraint violation idempotently
            if ($this->isUniqueConstraintViolation($e)) {
                $activation = Activation::where('license_id', $license->id)
                    ->where('user_identifier', $userIdentifier)
                    ->first();
            } else {
                throw $e;
            }
        }

        // Activate license if not already active
        if (!($license->status instanceof ActiveState)) {
            $license->status = new ActiveState($license);
            $license->save();
        }

        return $activation;
    }

    /**
     * Activate a floating license.
     *
     * @param License $license
     * @param string $deviceFpHash
     * @return Activation
     * @throws SeatsExhaustedException
     */
    private function activateFloating(License $license, string $deviceFpHash): Activation
    {
        // Check if there's an existing activation for this device fingerprint
        $existingActivation = Activation::where('license_id', $license->id)
            ->where('device_fp_hash', $deviceFpHash)
            ->where('type', 'floating')
            ->first();

        // If activation already exists, return it (idempotency)
        if ($existingActivation) {
            return $existingActivation;
        }

        // Lock the license row to prevent concurrent seat allocation
        $lockedLicense = License::lockForUpdate()->find($license->id);

        // Count active floating seats
        $activeSeats = FloatingSeat::where('license_id', $license->id)->count();

        // Check if seats are exhausted
        if ($activeSeats >= $lockedLicense->max_seats) {
            throw new \Exception('SEATS_EXHAUSTED');
        }

        // Create activation record
        try {
            $activation = Activation::create([
                'license_id' => $license->id,
                'device_fp_hash' => $deviceFpHash,
                'user_identifier' => null,
                'type' => 'floating',
                'activated_at' => Carbon::now(),
                'is_active' => true,
            ]);
        } catch (QueryException $e) {
            // Handle unique constraint violation idempotently
            if ($this->isUniqueConstraintViolation($e)) {
                $activation = Activation::where('license_id', $license->id)
                    ->where('device_fp_hash', $deviceFpHash)
                    ->where('type', 'floating')
                    ->first();
            } else {
                throw $e;
            }
        }

        // Create floating seat
        try {
            FloatingSeat::create([
                'license_id' => $license->id,
                'activation_id' => $activation->id,
                'device_fp_hash' => $deviceFpHash,
                'last_heartbeat_at' => Carbon::now(),
            ]);
        } catch (QueryException $e) {
            // Handle unique constraint violation on (license_id, device_fp_hash)
            if (!$this->isUniqueConstraintViolation($e)) {
                throw $e;
            }
            // Seat already exists for this device, which is fine
        }

        // Activate license if not already active
        if (!($license->status instanceof ActiveState)) {
            $license->status = new ActiveState($license);
            $license->save();
        }

        return $activation;
    }

    /**
     * Deactivate a license for a device or user.
     *
     * For floating licenses: deletes the FloatingSeat
     * For per-device/per-user: sets is_active = false and transitions license to inactive
     *
     * @param License $license
     * @param string $fingerprint The plaintext device fingerprint
     * @return bool
     */
    public function deactivate(License $license, string $fingerprint): bool
    {
        return DB::transaction(function () use ($license, $fingerprint) {
            $deviceFpHash = hash('sha256', $fingerprint);

            if ($license->license_model === 'floating') {
                // Delete the floating seat
                $deleted = FloatingSeat::where('license_id', $license->id)
                    ->where('device_fp_hash', $deviceFpHash)
                    ->delete();

                return $deleted > 0;
            }

            // For per-device and per-user licenses
            $activation = null;

            if ($license->license_model === 'per-device') {
                $activation = Activation::where('license_id', $license->id)
                    ->where('device_fp_hash', $deviceFpHash)
                    ->first();
            } elseif ($license->license_model === 'per-user') {
                $activation = Activation::where('license_id', $license->id)
                    ->where('is_active', true)
                    ->first();
            }

            if (!$activation) {
                return false;
            }

            // Deactivate the activation
            $activation->update(['is_active' => false]);

            // Transition license to inactive
            $license->status = new InactiveState($license);
            $license->save();

            return true;
        });
    }

    /**
     * Check if a QueryException is due to a unique constraint violation.
     *
     * @param QueryException $e
     * @return bool
     */
    private function isUniqueConstraintViolation(QueryException $e): bool
    {
        // Check for MySQL unique constraint violation (error code 1062)
        return $e->getCode() === '23000' || str_contains($e->getMessage(), 'UNIQUE constraint failed');
    }
}
