<?php

namespace App\Services;

use App\Exceptions\InvalidTransitionException;
use App\Exceptions\LicenseExpiredException;
use App\Models\License;
use App\States\License\ActiveState;
use App\States\License\ExpiredState;
use App\States\License\InactiveState;
use App\States\License\RevokedState;
use App\States\License\SuspendedState;
use App\Support\AuditLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LicenseService
{
    /**
     * Activate a license (transition from inactive to active).
     *
     * @param License $license
     * @return License
     * @throws InvalidTransitionException
     */
    public function activate(License $license): License
    {
        return DB::transaction(function () use ($license) {
            if (!$license->status->canActivate()) {
                throw new InvalidTransitionException(
                    class_basename($license->status::class),
                    'activate'
                );
            }

            $license->status = new ActiveState($license);
            $license->save();

            AuditLogger::licenseUpdated($license, [
                'event' => 'license.activated',
                'actor_name' => request()?->user()?->email,
                'severity' => 'info',
                'result' => 'success',
            ]);

            return $license;
        });
    }

    /**
     * Expire a license (transition from active to expired).
     *
     * @param License $license
     * @return License
     * @throws InvalidTransitionException
     */
    public function expire(License $license): License
    {
        return DB::transaction(function () use ($license) {
            // Check if current state allows expiration
            // Only active licenses can expire
            if (!($license->status instanceof ActiveState)) {
                throw new InvalidTransitionException(
                    class_basename($license->status::class),
                    'expire'
                );
            }

            $license->status = new ExpiredState($license);
            $license->save();

            return $license;
        });
    }

    /**
     * Suspend a license (transition from active to suspended).
     * Hook: Deactivates all activations and revokes all JTIs.
     *
     * @param License $license
     * @return License
     * @throws InvalidTransitionException
     */
    public function suspend(License $license): License
    {
        return DB::transaction(function () use ($license) {
            if (!$license->status->canSuspend()) {
                throw new InvalidTransitionException(
                    class_basename($license->status::class),
                    'suspend'
                );
            }

            // onSuspend hook: deactivate all activations
            $license->activations()->update(['is_active' => false]);

            // onSuspend hook: mark all JTIs as revoked
            $license->offlineTokenJtis()->update(['is_revoked' => true]);

            $license->status = new SuspendedState($license);
            $license->save();

            AuditLogger::licenseUpdated($license, [
                'event' => 'license.suspended',
                'actor_name' => request()?->user()?->email,
                'severity' => 'warning',
                'result' => 'success',
            ]);

            return $license;
        });
    }

    /**
     * Revoke a license (transition to revoked from active, inactive, or suspended).
     * Hook: Cancels all activations, marks JTIs invalid, deletes floating seats.
     *
     * @param License $license
     * @return License
     * @throws InvalidTransitionException
     */
    public function revoke(License $license): License
    {
        return DB::transaction(function () use ($license) {
            if (!$license->status->canRevoke()) {
                throw new InvalidTransitionException(
                    class_basename($license->status::class),
                    'revoke'
                );
            }

            // onRevoke hook: deactivate all activations
            $license->activations()->update(['is_active' => false]);

            // onRevoke hook: mark all JTIs as revoked
            $license->offlineTokenJtis()->update(['is_revoked' => true]);

            // onRevoke hook: delete all floating seats
            $license->floatingSeats()->delete();

            $license->status = new RevokedState($license);
            $license->save();

            AuditLogger::licenseRevoked($license, [
                'actor_name' => request()?->user()?->email,
                'severity' => 'warning',
                'result' => 'success',
            ]);

            return $license;
        });
    }

    /**
     * Restore a license (transition from suspended to active).
     * Hook: Checks expiry_date; throws exception if expired.
     *
     * @param License $license
     * @return License
     * @throws InvalidTransitionException
     * @throws LicenseExpiredException
     */
    public function restore(License $license): License
    {
        return DB::transaction(function () use ($license) {
            if (!$license->status->canRestore()) {
                throw new InvalidTransitionException(
                    class_basename($license->status::class),
                    'restore'
                );
            }

            // onRestore hook: check expiry_date
            if ($license->expiry_date !== null && $license->expiry_date->isPast()) {
                throw new LicenseExpiredException();
            }

            $license->status = new ActiveState($license);
            $license->save();

            AuditLogger::licenseUpdated($license, [
                'event' => 'license.restored',
                'actor_name' => request()?->user()?->email,
                'severity' => 'info',
                'result' => 'success',
            ]);

            return $license;
        });
    }

    /**
     * Renew a license (update expiry_date).
     * From expired: transitions to suspended.
     * From suspended: stays suspended but updates expiry_date.
     * From active: stays active and updates expiry_date.
     *
     * @param License $license
     * @param Carbon $newExpiryDate
     * @return License
     * @throws InvalidTransitionException
     */
    public function renew(License $license, Carbon $newExpiryDate): License
    {
        return DB::transaction(function () use ($license, $newExpiryDate) {
            if (!$license->status->canRenew()) {
                throw new InvalidTransitionException(
                    class_basename($license->status::class),
                    'renew'
                );
            }

            // Update expiry date
            $license->expiry_date = $newExpiryDate;

            // If expired, transition to suspended (per T2 requirement)
            if ($license->status instanceof ExpiredState) {
                $license->status = new SuspendedState($license);
            }
            // If suspended or active, keep the same state

            $license->save();

            return $license;
        });
    }

    /**
     * Un-revoke a license (transition from revoked to inactive).
     * Hook: Transitions to inactive and logs audit.
     *
     * @param License $license
     * @return License
     * @throws InvalidTransitionException
     */
    public function unrevoke(License $license): License
    {
        return DB::transaction(function () use ($license) {
            if (!$license->status->canUnrevoke()) {
                throw new InvalidTransitionException(
                    class_basename($license->status::class),
                    'unrevoke'
                );
            }

            // onUnrevoke hook: transition to inactive
            $license->status = new InactiveState($license);
            $license->save();

            AuditLogger::licenseUpdated($license, [
                'event' => 'license.unrevoked',
                'severity' => 'info',
                'result' => 'success',
            ]);

            return $license;
        });
    }
}
